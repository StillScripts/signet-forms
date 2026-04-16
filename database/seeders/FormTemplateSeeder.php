<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Enums\FormTemplateCategory;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            FormTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'Contact Us',
                'slug' => 'contact-us',
                'description' => 'A simple contact form for visitors to reach out with enquiries or messages.',
                'category' => FormTemplateCategory::General,
                'icon' => 'heroicon-o-envelope',
                'schema' => $this->schema([
                    $this->field('text-input', 'name', ['label' => 'Full Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true, 'placeholder' => 'you@example.com']),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number', 'placeholder' => '+1 (555) 000-0000']),
                    $this->field('text-input', 'subject', ['label' => 'Subject', 'is_required' => true]),
                    $this->field('textarea', 'message', ['label' => 'Message', 'is_required' => true, 'column_span' => 2]),
                ]),
            ],
            [
                'name' => 'Feedback Survey',
                'slug' => 'feedback-survey',
                'description' => 'Collect feedback from customers or users about your product or service.',
                'category' => FormTemplateCategory::Feedback,
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'schema' => $this->schema([
                    $this->field('text-input', 'name', ['label' => 'Your Name']),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'placeholder' => 'you@example.com']),
                    $this->field('select', 'rating', ['label' => 'How would you rate us?', 'is_required' => true, 'options' => [
                        ['label' => 'Excellent', 'value' => 'excellent'],
                        ['label' => 'Good', 'value' => 'good'],
                        ['label' => 'Average', 'value' => 'average'],
                        ['label' => 'Poor', 'value' => 'poor'],
                    ]]),
                    $this->field('textarea', 'what_went_well', ['label' => 'What went well?', 'column_span' => 2]),
                    $this->field('textarea', 'improvements', ['label' => 'What could be improved?', 'column_span' => 2]),
                    $this->field('radio-group', 'recommend', ['label' => 'Would you recommend us?', 'options' => [
                        ['label' => 'Yes', 'value' => 'yes'],
                        ['label' => 'Maybe', 'value' => 'maybe'],
                        ['label' => 'No', 'value' => 'no'],
                    ]]),
                ]),
            ],
            [
                'name' => 'Event Registration',
                'slug' => 'event-registration',
                'description' => 'Register attendees for conferences, workshops, or other events.',
                'category' => FormTemplateCategory::Events,
                'icon' => 'heroicon-o-calendar-days',
                'schema' => $this->schema([
                    $this->field('text-input', 'first_name', ['label' => 'First Name', 'is_required' => true]),
                    $this->field('text-input', 'last_name', ['label' => 'Last Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email', 'is_required' => true]),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number']),
                    $this->field('select', 'ticket_type', ['label' => 'Ticket Type', 'is_required' => true, 'options' => [
                        ['label' => 'General Admission', 'value' => 'general'],
                        ['label' => 'VIP', 'value' => 'vip'],
                        ['label' => 'Student', 'value' => 'student'],
                    ]]),
                    $this->field('textarea', 'dietary_requirements', ['label' => 'Dietary Requirements', 'column_span' => 2]),
                    $this->field('checkbox', 'terms', ['label' => 'I agree to the terms and conditions', 'is_required' => true]),
                ]),
            ],
            [
                'name' => 'Job Application',
                'slug' => 'job-application',
                'description' => 'Accept job applications with contact details, cover letter, and resume upload.',
                'category' => FormTemplateCategory::Business,
                'icon' => 'heroicon-o-briefcase',
                'schema' => $this->schema([
                    $this->field('text-input', 'full_name', ['label' => 'Full Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number', 'is_required' => true]),
                    $this->field('select', 'position', ['label' => 'Position Applied For', 'is_required' => true, 'options' => [
                        ['label' => 'Software Engineer', 'value' => 'software-engineer'],
                        ['label' => 'Designer', 'value' => 'designer'],
                        ['label' => 'Project Manager', 'value' => 'project-manager'],
                        ['label' => 'Other', 'value' => 'other'],
                    ]]),
                    $this->field('textarea', 'cover_letter', ['label' => 'Cover Letter', 'is_required' => true, 'column_span' => 2]),
                    $this->field('file-upload', 'resume', ['label' => 'Resume / CV', 'is_required' => true]),
                ]),
            ],
            [
                'name' => 'Patient Intake',
                'slug' => 'patient-intake',
                'description' => 'Multi-page patient intake covering personal details, medical history, insurance, and consent. Ideal for save-and-resume.',
                'category' => FormTemplateCategory::Healthcare,
                'icon' => 'heroicon-o-heart',
                'schema' => $this->multiPageSchema([
                    [
                        'title' => 'Personal Details',
                        'heading' => 'Tell us about yourself',
                        'subheading' => 'We use these details to create your patient record.',
                        'submit_button_text' => 'Continue',
                        'fields' => [
                            $this->field('text-input', 'first_name', ['label' => 'First Name', 'is_required' => true]),
                            $this->field('text-input', 'last_name', ['label' => 'Last Name', 'is_required' => true]),
                            $this->field('date-picker', 'date_of_birth', ['label' => 'Date of Birth', 'is_required' => true]),
                            $this->field('select', 'gender', ['label' => 'Gender', 'options' => [
                                ['label' => 'Female', 'value' => 'female'],
                                ['label' => 'Male', 'value' => 'male'],
                                ['label' => 'Non-binary', 'value' => 'non-binary'],
                                ['label' => 'Prefer not to say', 'value' => 'prefer-not-to-say'],
                            ]]),
                            $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                            $this->field('text-input', 'phone', ['label' => 'Phone Number', 'is_required' => true]),
                            $this->field('textarea', 'home_address', ['label' => 'Home Address', 'is_required' => true, 'column_span' => 2]),
                            $this->field('text-input', 'emergency_contact_name', ['label' => 'Emergency Contact Name', 'is_required' => true]),
                            $this->field('text-input', 'emergency_contact_phone', ['label' => 'Emergency Contact Phone', 'is_required' => true]),
                        ],
                    ],
                    [
                        'title' => 'Medical History',
                        'heading' => 'Your health history',
                        'subheading' => 'Your clinician will review this before your appointment.',
                        'submit_button_text' => 'Continue',
                        'fields' => [
                            $this->field('textarea', 'medical_conditions', ['label' => 'Current or Past Medical Conditions', 'column_span' => 2]),
                            $this->field('textarea', 'medications', ['label' => 'Current Medications and Dosages', 'column_span' => 2]),
                            $this->field('textarea', 'allergies', ['label' => 'Known Allergies (medications, foods, environmental)', 'column_span' => 2]),
                            $this->field('textarea', 'surgeries', ['label' => 'Past Surgeries or Hospitalisations', 'column_span' => 2]),
                            $this->field('textarea', 'family_history', ['label' => 'Relevant Family Medical History', 'column_span' => 2]),
                            $this->field('radio-group', 'smoker', ['label' => 'Do you smoke?', 'options' => [
                                ['label' => 'Never', 'value' => 'never'],
                                ['label' => 'Former smoker', 'value' => 'former'],
                                ['label' => 'Current smoker', 'value' => 'current'],
                            ]]),
                            $this->field('radio-group', 'alcohol', ['label' => 'Alcohol consumption', 'options' => [
                                ['label' => 'None', 'value' => 'none'],
                                ['label' => 'Occasional', 'value' => 'occasional'],
                                ['label' => 'Regular', 'value' => 'regular'],
                            ]]),
                        ],
                    ],
                    [
                        'title' => 'Insurance',
                        'heading' => 'Insurance details',
                        'subheading' => 'Have your insurance card handy.',
                        'submit_button_text' => 'Continue',
                        'fields' => [
                            $this->field('text-input', 'insurance_provider', ['label' => 'Insurance Provider', 'is_required' => true]),
                            $this->field('text-input', 'policy_number', ['label' => 'Policy Number', 'is_required' => true]),
                            $this->field('text-input', 'group_number', ['label' => 'Group Number']),
                            $this->field('text-input', 'policy_holder', ['label' => 'Policy Holder Name', 'is_required' => true]),
                            $this->field('date-picker', 'policy_start_date', ['label' => 'Policy Start Date']),
                            $this->field('file-upload', 'insurance_card', ['label' => 'Insurance Card (front and back)', 'column_span' => 2]),
                        ],
                    ],
                    [
                        'title' => 'Consent',
                        'heading' => 'Consent and acknowledgements',
                        'subheading' => 'Please review and confirm the following before submitting.',
                        'submit_button_text' => 'Submit Intake',
                        'fields' => [
                            $this->field('checkbox', 'consent_treatment', ['label' => 'I consent to medical treatment by the clinic staff.', 'is_required' => true, 'column_span' => 2]),
                            $this->field('checkbox', 'consent_privacy', ['label' => 'I have read and agree to the privacy policy.', 'is_required' => true, 'column_span' => 2]),
                            $this->field('checkbox', 'consent_data_sharing', ['label' => 'I consent to sharing my records with other healthcare providers when clinically necessary.', 'column_span' => 2]),
                            $this->field('text-input', 'signature', ['label' => 'Electronic Signature (type full name)', 'is_required' => true, 'column_span' => 2]),
                            $this->field('date-picker', 'signed_on', ['label' => 'Signed Date', 'is_required' => true]),
                        ],
                    ],
                ]),
            ],
            [
                'name' => 'Volunteer Sign-Up',
                'slug' => 'volunteer-sign-up',
                'description' => 'Recruit volunteers with their availability, skills, and background check consent.',
                'category' => FormTemplateCategory::Nonprofit,
                'icon' => 'heroicon-o-hand-raised',
                'schema' => $this->schema([
                    $this->field('text-input', 'name', ['label' => 'Full Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number']),
                    $this->field('select', 'availability', ['label' => 'Availability', 'is_required' => true, 'options' => [
                        ['label' => 'Weekdays', 'value' => 'weekdays'],
                        ['label' => 'Weekends', 'value' => 'weekends'],
                        ['label' => 'Evenings', 'value' => 'evenings'],
                        ['label' => 'Flexible', 'value' => 'flexible'],
                    ]]),
                    $this->field('textarea', 'skills', ['label' => 'Relevant Skills', 'column_span' => 2]),
                    $this->field('textarea', 'experience', ['label' => 'Previous Volunteer Experience', 'column_span' => 2]),
                    $this->field('checkbox', 'background_check', ['label' => 'I consent to a background check', 'is_required' => true]),
                ]),
            ],
            [
                'name' => 'Course Evaluation',
                'slug' => 'course-evaluation',
                'description' => 'Gather student feedback on courses and instructors.',
                'category' => FormTemplateCategory::Education,
                'icon' => 'heroicon-o-academic-cap',
                'schema' => $this->schema([
                    $this->field('text-input', 'course_name', ['label' => 'Course Name', 'is_required' => true]),
                    $this->field('text-input', 'instructor', ['label' => 'Instructor Name', 'is_required' => true]),
                    $this->field('select', 'rating', ['label' => 'Overall Rating', 'is_required' => true, 'options' => [
                        ['label' => '5 - Excellent', 'value' => '5'],
                        ['label' => '4 - Good', 'value' => '4'],
                        ['label' => '3 - Average', 'value' => '3'],
                        ['label' => '2 - Below Average', 'value' => '2'],
                        ['label' => '1 - Poor', 'value' => '1'],
                    ]]),
                    $this->field('textarea', 'most_valuable', ['label' => 'Most valuable part of the course', 'column_span' => 2]),
                    $this->field('textarea', 'least_valuable', ['label' => 'Least valuable part of the course', 'column_span' => 2]),
                    $this->field('textarea', 'suggestions', ['label' => 'Suggestions for improvement', 'column_span' => 2]),
                    $this->field('radio-group', 'recommend', ['label' => 'Would you recommend this course?', 'options' => [
                        ['label' => 'Yes', 'value' => 'yes'],
                        ['label' => 'No', 'value' => 'no'],
                    ]]),
                ]),
            ],
            [
                'name' => 'Public Comment',
                'slug' => 'public-comment',
                'description' => 'Accept public comments on proposed regulations, projects, or policies.',
                'category' => FormTemplateCategory::Government,
                'icon' => 'heroicon-o-building-library',
                'schema' => $this->schema([
                    $this->field('text-input', 'name', ['label' => 'Full Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'organisation', ['label' => 'Organisation (if applicable)']),
                    $this->field('select', 'topic', ['label' => 'Topic', 'is_required' => true, 'options' => [
                        ['label' => 'Proposed Regulation', 'value' => 'regulation'],
                        ['label' => 'Infrastructure Project', 'value' => 'infrastructure'],
                        ['label' => 'Policy Change', 'value' => 'policy'],
                        ['label' => 'Other', 'value' => 'other'],
                    ]]),
                    $this->field('textarea', 'comment', ['label' => 'Your Comment', 'is_required' => true, 'column_span' => 2]),
                    $this->field('file-upload', 'supporting_docs', ['label' => 'Supporting Documents']),
                ]),
            ],
            [
                'name' => 'Bug Report',
                'slug' => 'bug-report',
                'description' => 'Collect detailed bug reports from users or testers.',
                'category' => FormTemplateCategory::Business,
                'icon' => 'heroicon-o-bug-ant',
                'schema' => $this->schema([
                    $this->field('text-input', 'reporter_name', ['label' => 'Your Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('select', 'severity', ['label' => 'Severity', 'is_required' => true, 'options' => [
                        ['label' => 'Critical - System down', 'value' => 'critical'],
                        ['label' => 'High - Major feature broken', 'value' => 'high'],
                        ['label' => 'Medium - Minor issue', 'value' => 'medium'],
                        ['label' => 'Low - Cosmetic', 'value' => 'low'],
                    ]]),
                    $this->field('textarea', 'steps_to_reproduce', ['label' => 'Steps to Reproduce', 'is_required' => true, 'column_span' => 2]),
                    $this->field('textarea', 'expected_behaviour', ['label' => 'Expected Behaviour', 'is_required' => true, 'column_span' => 2]),
                    $this->field('textarea', 'actual_behaviour', ['label' => 'Actual Behaviour', 'is_required' => true, 'column_span' => 2]),
                ]),
            ],
            [
                'name' => 'Workshop Registration',
                'slug' => 'workshop-registration',
                'description' => 'Register participants for workshops with session preferences.',
                'category' => FormTemplateCategory::Events,
                'icon' => 'heroicon-o-presentation-chart-bar',
                'schema' => $this->schema([
                    $this->field('text-input', 'first_name', ['label' => 'First Name', 'is_required' => true]),
                    $this->field('text-input', 'last_name', ['label' => 'Last Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'organisation', ['label' => 'Organisation']),
                    $this->field('text-input', 'role', ['label' => 'Job Title / Role']),
                    $this->field('select', 'workshop_choice', ['label' => 'Preferred Workshop', 'is_required' => true, 'options' => [
                        ['label' => 'Introduction to Design Thinking', 'value' => 'design-thinking'],
                        ['label' => 'Advanced Data Analysis', 'value' => 'data-analysis'],
                        ['label' => 'Leadership Skills', 'value' => 'leadership'],
                        ['label' => 'Project Management', 'value' => 'project-management'],
                    ]]),
                    $this->field('textarea', 'special_requirements', ['label' => 'Special Requirements or Accessibility Needs', 'column_span' => 2]),
                ]),
            ],
            [
                'name' => 'Donation Pledge',
                'slug' => 'donation-pledge',
                'description' => 'Accept donation pledges with amount, frequency, and dedication options.',
                'category' => FormTemplateCategory::Nonprofit,
                'icon' => 'heroicon-o-gift',
                'schema' => $this->schema([
                    $this->field('text-input', 'donor_name', ['label' => 'Full Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number']),
                    $this->field('number', 'amount', ['label' => 'Donation Amount ($)', 'is_required' => true, 'min' => 1]),
                    $this->field('radio-group', 'frequency', ['label' => 'Donation Frequency', 'is_required' => true, 'options' => [
                        ['label' => 'One-time', 'value' => 'one-time'],
                        ['label' => 'Monthly', 'value' => 'monthly'],
                        ['label' => 'Yearly', 'value' => 'yearly'],
                    ]]),
                    $this->field('textarea', 'dedication', ['label' => 'Dedication or Special Instructions', 'column_span' => 2]),
                ]),
            ],
            [
                'name' => 'Permit Application',
                'slug' => 'permit-application',
                'description' => 'Accept permit applications with project details and supporting documents.',
                'category' => FormTemplateCategory::Government,
                'icon' => 'heroicon-o-clipboard-document-check',
                'schema' => $this->schema([
                    $this->field('text-input', 'applicant_name', ['label' => 'Applicant Name', 'is_required' => true]),
                    $this->field('text-input', 'email', ['label' => 'Email Address', 'is_required' => true]),
                    $this->field('text-input', 'phone', ['label' => 'Phone Number', 'is_required' => true]),
                    $this->field('textarea', 'address', ['label' => 'Property Address', 'is_required' => true, 'column_span' => 2]),
                    $this->field('select', 'permit_type', ['label' => 'Permit Type', 'is_required' => true, 'options' => [
                        ['label' => 'Building Permit', 'value' => 'building'],
                        ['label' => 'Demolition Permit', 'value' => 'demolition'],
                        ['label' => 'Renovation Permit', 'value' => 'renovation'],
                        ['label' => 'Special Event Permit', 'value' => 'event'],
                    ]]),
                    $this->field('textarea', 'project_description', ['label' => 'Project Description', 'is_required' => true, 'column_span' => 2]),
                    $this->field('date-picker', 'start_date', ['label' => 'Proposed Start Date', 'is_required' => true]),
                    $this->field('file-upload', 'supporting_docs', ['label' => 'Supporting Documents']),
                ]),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function schema(array $fields): array
    {
        return ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => $fields,
        ]]];
    }

    /**
     * Build a multi-page schema. Each page may specify title, heading, subheading,
     * submit_button_text, and fields; all are optional except fields.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    private function multiPageSchema(array $pages): array
    {
        return ['pages' => array_map(fn (array $page) => [
            'id' => fake()->uuid(),
            'title' => $page['title'] ?? null,
            'heading' => $page['heading'] ?? null,
            'subheading' => $page['subheading'] ?? null,
            'submit_button_text' => $page['submit_button_text'] ?? null,
            'fields' => $page['fields'] ?? [],
        ], $pages)];
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
