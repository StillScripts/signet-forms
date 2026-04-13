<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function setUpMultiPageTest(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id, 'schema' => null]);

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return [$user, $team, $project, $form];
}

// --- Page Initialisation ---

test('new form initialises with one empty page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    expect($pages)->toHaveCount(1)
        ->and($pages[0]['fields'])->toBe([])
        ->and($pages[0]['title'])->toBeNull()
        ->and($pages[0]['heading'])->toBeNull()
        ->and($pages[0]['subheading'])->toBeNull()
        ->and($pages[0]['submit_button_text'])->toBeNull();
});

test('active page id is set on mount', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component->assertSet('activePageId', $pages[0]['id']);
});

// --- Adding Pages ---

test('can add a new page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    expect($pages)->toHaveCount(2);
    $component->assertSet('activePageId', $pages[1]['id']);
});

test('adding a page sets unsaved changes', function () {
    [, , $project, $form] = setUpMultiPageTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage')
        ->assertSet('hasUnsavedChanges', true);
});

test('adding a page clears field selection', function () {
    [, , $project, $form] = setUpMultiPageTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addPage')
        ->assertSet('selectedFieldKey', null)
        ->assertCount('fields', 0);
});

// --- Removing Pages ---

test('can remove a page when multiple exist', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    $secondPageId = $pages[1]['id'];

    $component->call('removePage', $secondPageId);

    $pages = $component->get('pages');
    expect($pages)->toHaveCount(1);
});

test('cannot remove last remaining page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component->call('removePage', $pages[0]['id']);

    expect($component->get('pages'))->toHaveCount(1);
});

test('removing active page switches to first page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    $firstPageId = $pages[0]['id'];
    $secondPageId = $pages[1]['id'];

    $component
        ->call('switchPage', $secondPageId)
        ->assertSet('activePageId', $secondPageId)
        ->call('removePage', $secondPageId)
        ->assertSet('activePageId', $firstPageId);
});

// --- Switching Pages ---

test('can switch between pages', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addPage')
        ->call('addField', 'textarea');

    $pages = $component->get('pages');

    $component
        ->call('switchPage', $pages[0]['id'])
        ->assertCount('fields', 1);

    $fields = $component->get('fields');
    expect($fields[0]['type'])->toBe('text-input');
});

test('switching pages syncs fields back to current page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addField', 'textarea')
        ->call('addPage');

    $pages = $component->get('pages');
    expect($pages[0]['fields'])->toHaveCount(2);
});

// --- Page Metadata ---

test('can update page title', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component->call('updatePageData', $pages[0]['id'], 'title', 'Personal Info');

    $pages = $component->get('pages');
    expect($pages[0]['title'])->toBe('Personal Info');
});

test('can update page heading and subheading', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $pageId = $pages[0]['id'];

    $component
        ->call('updatePageData', $pageId, 'heading', 'Welcome')
        ->call('updatePageData', $pageId, 'subheading', 'Please fill out this form');

    $pages = $component->get('pages');
    expect($pages[0]['heading'])->toBe('Welcome')
        ->and($pages[0]['subheading'])->toBe('Please fill out this form');
});

test('can update submit button text', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component->call('updatePageData', $pages[0]['id'], 'submit_button_text', 'Send Message');

    $pages = $component->get('pages');
    expect($pages[0]['submit_button_text'])->toBe('Send Message');
});

test('updating page data sets unsaved changes', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component
        ->call('updatePageData', $pages[0]['id'], 'title', 'Step 1')
        ->assertSet('hasUnsavedChanges', true);
});

// --- Page Ordering ---

test('can move page up', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    $secondPageId = $pages[1]['id'];

    $component->call('movePage', $secondPageId, 'up');

    $pages = $component->get('pages');
    expect($pages[0]['id'])->toBe($secondPageId);
});

test('cannot move first page up', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    $firstPageId = $pages[0]['id'];

    $component->call('movePage', $firstPageId, 'up');

    $pages = $component->get('pages');
    expect($pages[0]['id'])->toBe($firstPageId);
});

// --- Submit Button Defaults ---

test('single page form defaults submit button to Submit', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $text = $component->instance()->getSubmitButtonText($pages[0], 0);

    expect($text)->toBe('Submit');
});

test('multi page form defaults non-last page to Continue', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    $pages = $component->get('pages');
    $firstText = $component->instance()->getSubmitButtonText($pages[0], 0);
    $lastText = $component->instance()->getSubmitButtonText($pages[1], 1);

    expect($firstText)->toBe('Continue')
        ->and($lastText)->toBe('Submit');
});

test('custom submit button text overrides default', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $component->call('updatePageData', $pages[0]['id'], 'submit_button_text', 'Send');

    $pages = $component->get('pages');
    $text = $component->instance()->getSubmitButtonText($pages[0], 0);
    expect($text)->toBe('Send');
});

// --- Multi-Page Detection ---

test('isMultiPage returns false for single page', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    expect($component->instance()->isMultiPage())->toBeFalse();
});

test('isMultiPage returns true for multiple pages', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addPage');

    expect($component->instance()->isMultiPage())->toBeTrue();
});

// --- Save/Load Multi-Page ---

test('save persists all pages in schema', function () {
    [, , $project, $form] = setUpMultiPageTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addPage')
        ->call('addField', 'textarea')
        ->call('save');

    $form->refresh();
    $pages = $form->schema['pages'];
    expect($pages)->toHaveCount(2)
        ->and($pages[0]['fields'])->toHaveCount(1)
        ->and($pages[0]['fields'][0]['type'])->toBe('text-input')
        ->and($pages[1]['fields'])->toHaveCount(1)
        ->and($pages[1]['fields'][0]['type'])->toBe('textarea');
});

test('mount restores multi-page schema', function () {
    [, , $project, $form] = setUpMultiPageTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addPage')
        ->call('addField', 'textarea')
        ->call('save');

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    expect($pages)->toHaveCount(2);
    $component->assertCount('fields', 1);

    $fields = $component->get('fields');
    expect($fields[0]['type'])->toBe('text-input');
});

test('undo restores previous multi-page schema', function () {
    [, , $project, $form] = setUpMultiPageTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('save')
        ->call('addPage')
        ->call('addField', 'textarea')
        ->call('save');

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    expect($component->get('pages'))->toHaveCount(2);

    $component->call('undo');

    expect($component->get('pages'))->toHaveCount(1);
    $component->assertCount('fields', 1);
});

// --- Page Label ---

test('page label shows title when set', function () {
    [, , $project, $form] = setUpMultiPageTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $pages = $component->get('pages');
    $label = $component->instance()->getPageLabel($pages[0], 0);
    expect($label)->toBe('Page 1');

    $component->call('updatePageData', $pages[0]['id'], 'title', 'Contact Details');

    $pages = $component->get('pages');
    $label = $component->instance()->getPageLabel($pages[0], 0);
    expect($label)->toBe('Contact Details');
});
