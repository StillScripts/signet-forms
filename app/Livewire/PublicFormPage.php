<?php

namespace App\Livewire;

use App\Enums\FieldComponentRenderTarget;
use App\Mail\SubmissionResumeLink;
use App\Models\Form as FormModel;
use App\Models\Submission;
use App\Models\Team;
use App\Services\FieldComponentBuilder;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PublicFormPage extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    protected const RESUME_WINDOW_DAYS = 30;

    protected const SAVE_RATE_LIMIT_PER_MINUTE = 5;

    protected const SAVE_RATE_LIMIT_PER_HOUR = 20;

    #[Locked]
    public FormModel $formRecord;

    #[Locked]
    public Team $team;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public bool $submitted = false;

    #[Locked]
    public ?string $submissionId = null;

    #[Locked]
    public ?int $resumedPageIndex = null;

    #[Locked]
    public bool $wasResumedFromToken = false;

    public bool $showSaveForm = false;

    public bool $saveEmailSent = false;

    public bool $alreadySubmitted = false;

    #[Locked]
    public bool $embedded = false;

    #[Validate('required|email|max:255')]
    public string $saveEmail = '';

    public function mount(Team $team, string $formSlug, ?string $token = null): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->team = $team;

        $formRecord = FormModel::where('slug', $formSlug)
            ->whereHas('project', fn ($query) => $query->where('team_id', $team->id))
            ->firstOrFail();

        abort_unless($formRecord->is_published, 404);

        $this->formRecord = $formRecord;
        $this->embedded = request()->boolean('embed');

        if ($token !== null) {
            $this->loadDraft($token);

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $pages = $this->formRecord->schema['pages'] ?? [];

        if (count($pages) <= 1) {
            return $this->buildSinglePageForm($schema, $pages[0] ?? []);
        }

        return $this->buildMultiPageForm($schema, $pages);
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        if ($this->submissionId) {
            $submission = Submission::where('id', $this->submissionId)
                ->where('form_id', $this->formRecord->id)
                ->where('is_draft', true)
                ->first();

            if ($submission === null) {
                $this->alreadySubmitted = true;

                return;
            }

            $submission->update([
                'data' => $formData,
                'is_draft' => false,
                'resume_token' => null,
                'resume_token_expires_at' => null,
                'resume_page_index' => null,
                'respondent_email' => $this->extractRespondentEmail($formData) ?? $submission->respondent_email,
                'respondent_name' => $this->extractRespondentName($formData) ?? $submission->respondent_name,
            ]);
        } else {
            Submission::create([
                'form_id' => $this->formRecord->id,
                'data' => $formData,
                'metadata' => $this->requestMetadata(),
                'form_version' => $this->formRecord->latestVersion()?->version,
                'respondent_email' => $this->extractRespondentEmail($formData),
                'respondent_name' => $this->extractRespondentName($formData),
            ]);
        }

        $this->submitted = true;
    }

    public function openSaveForm(): void
    {
        $this->showSaveForm = true;
        $this->saveEmailSent = false;
    }

    public function cancelSave(): void
    {
        $this->showSaveForm = false;
        $this->resetValidation('saveEmail');
    }

    public function saveProgress(?int $currentPageIndex = null): void
    {
        $this->validateOnly('saveEmail');

        if ($this->isSaveRateLimited()) {
            $this->addError('saveEmail', 'Too many attempts. Please try again later.');

            return;
        }

        $this->hitSaveRateLimiters();

        $rawState = $this->form->getRawState();
        $formData = is_array($rawState) ? $rawState : [];

        $submission = $this->submissionId
            ? Submission::where('id', $this->submissionId)
                ->where('form_id', $this->formRecord->id)
                ->where('is_draft', true)
                ->first()
            : null;

        if ($this->submissionId && $submission === null) {
            $this->alreadySubmitted = true;

            return;
        }

        if ($submission === null) {
            $submission = Submission::create([
                'form_id' => $this->formRecord->id,
                'data' => $formData,
                'metadata' => $this->requestMetadata(),
                'form_version' => $this->formRecord->latestVersion()?->version,
                'respondent_email' => $this->saveEmail,
                'respondent_name' => $this->extractRespondentName($formData),
                'is_draft' => true,
                'resume_token' => Submission::generateResumeToken(),
                'resume_token_expires_at' => Carbon::now()->addDays(self::RESUME_WINDOW_DAYS),
                'resume_page_index' => $currentPageIndex,
            ]);

            $this->submissionId = $submission->id;
        } else {
            $updates = [
                'data' => $formData,
                'respondent_name' => $this->extractRespondentName($formData) ?? $submission->respondent_name,
                'resume_token_expires_at' => Carbon::now()->addDays(self::RESUME_WINDOW_DAYS),
                'resume_page_index' => $currentPageIndex,
            ];

            if ($submission->respondent_email === null) {
                $updates['respondent_email'] = $this->extractRespondentEmail($formData) ?? $this->saveEmail;
            }

            $submission->update($updates);
        }

        Mail::to($this->saveEmail)->queue(new SubmissionResumeLink($submission->fresh()));

        $this->saveEmailSent = true;
    }

    protected function saveRateLimiterKeys(): array
    {
        $ip = request()->ip() ?? 'unknown';
        $email = strtolower(trim($this->saveEmail));
        $identifier = $email !== '' ? $ip.'|'.$email : $ip;

        return [
            'minute' => 'public-form:save:minute:'.sha1($identifier),
            'hour' => 'public-form:save:hour:'.sha1($identifier),
        ];
    }

    protected function isSaveRateLimited(): bool
    {
        $keys = $this->saveRateLimiterKeys();

        return RateLimiter::tooManyAttempts($keys['minute'], self::SAVE_RATE_LIMIT_PER_MINUTE)
            || RateLimiter::tooManyAttempts($keys['hour'], self::SAVE_RATE_LIMIT_PER_HOUR);
    }

    protected function hitSaveRateLimiters(): void
    {
        $keys = $this->saveRateLimiterKeys();

        RateLimiter::hit($keys['minute'], 60);
        RateLimiter::hit($keys['hour'], 3600);
    }

    public function render(): View
    {
        return view('livewire.public-form-page')
            ->layout('layouts.public');
    }

    protected function loadDraft(string $token): void
    {
        $submission = Submission::where('form_id', $this->formRecord->id)
            ->where('resume_token', $token)
            ->where('is_draft', true)
            ->first();

        abort_unless($submission && $submission->isResumable(), 404);

        $this->submissionId = $submission->id;
        $this->resumedPageIndex = $submission->resume_page_index;
        $this->wasResumedFromToken = true;
        $this->saveEmail = (string) $submission->respondent_email;
        $this->data = is_array($submission->data) ? $submission->data : [];
        $this->form->fill($this->data);
    }

    protected function buildSinglePageForm(Schema $schema, array $page): Schema
    {
        $fields = $page['fields'] ?? [];
        $components = $this->buildFieldComponents($fields);

        return $schema
            ->components($components)
            ->columns(2)
            ->statePath('data');
    }

    protected function buildMultiPageForm(Schema $schema, array $pages): Schema
    {
        $steps = [];

        foreach ($pages as $index => $page) {
            $fields = $page['fields'] ?? [];
            $components = $this->buildFieldComponents($fields);

            $label = $page['title'] ?: 'Step '.($index + 1);

            $step = Step::make($label)
                ->schema($components)
                ->columns(2);

            if (! empty($page['subheading'])) {
                $step->description($page['subheading']);
            }

            $steps[] = $step;
        }

        $startStep = $this->resumedPageIndex !== null
            ? min($this->resumedPageIndex + 1, count($steps))
            : 1;

        return $schema
            ->components([
                Wizard::make($steps)
                    ->startOnStep($startStep)
                    ->submitAction(view('livewire.partials.submit-button', [
                        'label' => $this->getSubmitLabel(),
                    ])),
            ])
            ->statePath('data');
    }

    /**
     * @return array<SchemaComponent>
     */
    protected function buildFieldComponents(array $fields): array
    {
        return collect($fields)
            ->map(fn (array $field) => $this->buildFilamentComponent($field))
            ->filter()
            ->values()
            ->all();
    }

    protected function buildFilamentComponent(array $field): ?SchemaComponent
    {
        return app(FieldComponentBuilder::class)->buildFilamentComponent($field, FieldComponentRenderTarget::PublicForm);
    }

    public function getSubmitLabel(): string
    {
        $pages = $this->formRecord->schema['pages'] ?? [];
        $lastPage = end($pages);

        if (! empty($lastPage['submit_button_text'])) {
            return $lastPage['submit_button_text'];
        }

        return 'Submit';
    }

    public function isMultiPage(): bool
    {
        return count($this->formRecord->schema['pages'] ?? []) > 1;
    }

    public function isResumed(): bool
    {
        return $this->wasResumedFromToken && $this->submissionId !== null;
    }

    /**
     * @return array<string, ?string>
     */
    protected function requestMetadata(): array
    {
        return [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRespondentEmail(array $data): ?string
    {
        return $data['email'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRespondentName(array $data): ?string
    {
        if (! empty($data['name'])) {
            return $data['name'];
        }

        $parts = array_filter([
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
        ]);

        return $parts ? implode(' ', $parts) : null;
    }
}
