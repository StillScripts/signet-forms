# Tasks

## Active

## Waiting On

## Someday

- [ ] **[TASK-5] Form templates** - Allow users to create a form from a preset template (e.g., Contact Us, Feedback Survey, Registration) instead of starting blank. Templates provide pre-configured fields that users can customise after creation.

## Done

- [x] **[TASK-6] Add top-level Forms sidebar navigation** - Add a top-level "Forms" item in the sidebar (below Projects) showing all forms across all projects in the current team. Table includes a Project column. Clicking a form navigates to the existing nested view. Create action requires project selection.
- [x] **[TASK-4] Add visual form builder page** - Build a custom Livewire page for the form builder with three-panel interface: component palette (left), canvas with drag-to-reorder (center), field settings (right). Includes Builder/Preview tabs, multi-column layout support, undo/redo, and save functionality. Fields stored as JSON in the Form model's `fields` column.
- [x] **[TASK-3] Add Form model and Filament resource under Projects** - Introduce the Form model belonging to Project with name, slug, description, fields (JSON), is_published. Includes migration, factory, policy, TeamPermission entries, Filament CRUD resource scoped under projects, and tests.
- [x] **[TASK-2] Add Projects to the application** - Introduce the Project model as an organizational layer between Teams and Forms. Teams own Projects, Projects will eventually own Forms. Includes model, migration, factory, Filament resource, policy, and tests.
- [x] **[TASK-1] Set up feature documentation workflow** - Add docs/ directory structure, integrate doc-coauthoring, product-brainstorming, and write-spec skills into the development workflow so every feature gets a spec with clear reasoning. Eventually these docs will power a doc site.
