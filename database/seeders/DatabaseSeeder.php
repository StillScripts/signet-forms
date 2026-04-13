<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Enums\SubmissionStatus;
use App\Enums\TeamRole;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use App\ValueObjects\FormSettings;
use App\ValueObjects\Settings\ConfirmationSettings;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FormTemplateSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $exampleTeam = Team::factory()->create([
            'name' => 'Example',
            'slug' => 'example',
        ]);

        $miscTeam = Team::factory()->create([
            'name' => 'Miscellaneous',
            'slug' => 'miscellaneous',
        ]);

        $exampleTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);
        $miscTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->switchTeam($exampleTeam);

        // Example team: two projects with forms
        $websiteProject = Project::factory()->create([
            'team_id' => $exampleTeam->id,
            'name' => 'Website',
            'slug' => 'website',
            'description' => 'Main company website',
        ]);

        $eventsProject = Project::factory()->create([
            'team_id' => $exampleTeam->id,
            'name' => 'Events',
            'slug' => 'events',
            'description' => 'Event management forms',
        ]);

        $contactForm = $this->createForm($websiteProject, 'Contact Us', [
            $this->field('text-input', 'name', ['label' => 'Full Name', 'is_required' => true]),
            $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true, 'placeholder' => 'you@example.com']),
            $this->field('textarea', 'message', ['label' => 'Message', 'is_required' => true, 'column_span' => 2]),
        ], published: true, settings: new FormSettings(
            confirmation: new ConfirmationSettings(
                heading: 'Thanks for reaching out!',
                message: 'We\'ll get back to you within 24 hours.',
            ),
        ));

        $feedbackForm = $this->createForm($websiteProject, 'Feedback Survey', [
            $this->field('text-input', 'name', ['label' => 'Your Name']),
            $this->field('select', 'rating', ['label' => 'How would you rate us?', 'is_required' => true, 'options' => [
                ['label' => 'Excellent', 'value' => 'excellent'],
                ['label' => 'Good', 'value' => 'good'],
                ['label' => 'Average', 'value' => 'average'],
                ['label' => 'Poor', 'value' => 'poor'],
            ]]),
            $this->field('textarea', 'comments', ['label' => 'Additional Comments', 'column_span' => 2]),
        ], published: true);

        $registrationForm = $this->createForm($eventsProject, 'Event Registration', [
            $this->field('text-input', 'first_name', ['label' => 'First Name', 'is_required' => true]),
            $this->field('text-input', 'last_name', ['label' => 'Last Name', 'is_required' => true]),
            $this->field('text-input', 'email', ['label' => 'Email', 'is_required' => true]),
            $this->field('select', 'ticket_type', ['label' => 'Ticket Type', 'is_required' => true, 'options' => [
                ['label' => 'General Admission', 'value' => 'general'],
                ['label' => 'VIP', 'value' => 'vip'],
                ['label' => 'Student', 'value' => 'student'],
            ]]),
            $this->field('checkbox', 'terms', ['label' => 'I agree to the terms and conditions', 'is_required' => true]),
        ], published: true, settings: new FormSettings(
            confirmation: new ConfirmationSettings(
                heading: 'You\'re registered!',
                message: 'Check your email for confirmation details.',
            ),
        ));

        $sampleMetadata = [
            'ip_address' => '203.0.113.42',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'referer' => 'https://example.com/about',
        ];

        // Sample submissions for the contact form
        Submission::factory()->create([
            'form_id' => $contactForm->id,
            'data' => ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'message' => 'I love your product!'],
            'status' => SubmissionStatus::Approved,
            'metadata' => $sampleMetadata,
            'form_version' => 1,
            'respondent_email' => 'jane@example.com',
            'respondent_name' => 'Jane Smith',
        ]);
        Submission::factory()->create([
            'form_id' => $contactForm->id,
            'data' => ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'message' => 'Can I get a demo?'],
            'status' => SubmissionStatus::Pending,
            'metadata' => $sampleMetadata,
            'form_version' => 1,
            'respondent_email' => 'bob@example.com',
            'respondent_name' => 'Bob Wilson',
        ]);

        // Sample submissions for the feedback form
        Submission::factory()->create([
            'form_id' => $feedbackForm->id,
            'data' => ['name' => 'Alice Brown', 'rating' => 'excellent', 'comments' => 'Great experience overall.'],
            'status' => SubmissionStatus::InReview,
            'metadata' => $sampleMetadata,
            'form_version' => 1,
            'respondent_name' => 'Alice Brown',
            'assigned_reviewer_id' => $user->id,
        ]);

        // Miscellaneous team: one project with a draft form
        $internalProject = Project::factory()->create([
            'team_id' => $miscTeam->id,
            'name' => 'Internal',
            'slug' => 'internal',
            'description' => 'Internal team forms',
        ]);

        $this->createForm($internalProject, 'Employee Onboarding', [
            $this->field('text-input', 'full_name', ['label' => 'Full Name', 'is_required' => true]),
            $this->field('text-input', 'department', ['label' => 'Department', 'is_required' => true]),
            $this->field('date-picker', 'start_date', ['label' => 'Start Date', 'is_required' => true]),
            $this->field('toggle', 'laptop_required', ['label' => 'Requires Laptop']),
        ]);
    }

    /**
     * @param  array<array<string, mixed>>  $fields
     */
    private function createForm(
        Project $project,
        string $name,
        array $fields,
        bool $published = false,
        ?FormSettings $settings = null,
    ): Form {
        $schema = ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => $fields,
        ]]];

        $form = Form::factory()->create([
            'project_id' => $project->id,
            'name' => $name,
            'schema' => $schema,
            'is_published' => $published,
            'settings' => $settings,
        ]);

        FormVersion::factory()->create([
            'form_id' => $form->id,
            'version' => 1,
            'schema' => $schema,
        ]);

        return $form;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function field(string $type, string $key, array $data = []): array
    {
        $fieldType = FormFieldType::from($type);

        return [
            'type' => $type,
            'key' => $key,
            'sort' => 0,
            'data' => array_merge($fieldType->defaultData(), $data),
        ];
    }
}
