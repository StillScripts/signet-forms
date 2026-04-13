<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Enums\TeamRole;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
        ], published: true, successHeading: 'Thanks for reaching out!', successMessage: 'We\'ll get back to you within 24 hours.');

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
        ], published: true, successHeading: 'You\'re registered!', successMessage: 'Check your email for confirmation details.');

        // Sample submissions for the contact form
        Submission::factory()->create(['form_id' => $contactForm->id, 'data' => ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'message' => 'I love your product!']]);
        Submission::factory()->create(['form_id' => $contactForm->id, 'data' => ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'message' => 'Can I get a demo?']]);

        // Sample submissions for the feedback form
        Submission::factory()->create(['form_id' => $feedbackForm->id, 'data' => ['name' => 'Alice Brown', 'rating' => 'excellent', 'comments' => 'Great experience overall.']]);

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
        ?string $successHeading = null,
        ?string $successMessage = null,
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
            'success_heading' => $successHeading,
            'success_message' => $successMessage,
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
