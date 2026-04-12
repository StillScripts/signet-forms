---
name: create-branch
description: Create a new git branch with proper naming conventions. Use whenever starting work on a new task, feature, or bugfix. Handles pulling latest from the correct base branch and creating the branch with the right prefix.
---

# Checkout

Create a new branch following project conventions and prepare the local environment for development.

**Requires:** A task key in `TASK-<number>` format (e.g., `TASK-42`). If the user doesn't provide one, offer to create a task for them using the `/task-management` skill (which manages all tasks in `TASKS.md`). Do not create a branch without a task key.

## Branch Naming

Branches follow the pattern: `{type}/TASK-{number}-{slug}`

The slug should be kebab-case, ASCII-only, and ideally 3–6 words derived from the task description.

### Branch Types

Classify the branch based on the nature of the work:

| Type | Use when | Base branch |
|------|----------|-------------|
| `feat` | New functionality | `develop` |
| `fix` | Broken behavior now works | `develop` (or `main` for urgent production fixes) |
| `ref` | Behavior stays the same, structure changes | `develop` |
| `chore` | Maintenance of existing tooling/config | `develop` |
| `perf` | Same behavior, faster | `develop` |
| `style` | Visual or formatting only | `develop` |
| `docs` | Documentation only | `develop` |
| `test` | Tests only | `develop` |
| `ci` | CI/CD config | `develop` |
| `build` | Build system | `develop` |
| `meta` | Repo metadata | `develop` |
| `license` | License changes | `develop` |

When unsure: use `feat` for new things, `ref` for restructuring, `chore` for maintenance.

Only `fix` branches may use `main` as the base — and only for urgent production hotfixes. All other types branch from `develop`.

### Examples

- `feat/TASK-42-add-team-roles`
- `fix/TASK-15-login-redirect-loop`
- `ref/TASK-30-extract-notification-service`
- `chore/TASK-51-update-ci-matrix`
- `docs/TASK-60-api-endpoint-docs`

Task keys must follow `/task-management` as the source of truth.

## Process

1. **Require a task key.** If the user hasn't provided one, offer to create a task using the `/task-management` skill (reads/writes `TASKS.md`, allocates the next `TASK-<number>` key). Do not proceed without a key.

2. **Look up the task** in TASKS.md to get the description. Use it to generate the slug.

3. **Classify the branch type.** Infer from the task description. Ask the user only if genuinely ambiguous (e.g., a task that could be `feat` or `ref`).

4. **Determine the base branch:**
    - `fix` on `main` → only for urgent production hotfixes (ask the user to confirm)
    - Everything else → `develop`

5. **Checkout and pull latest from the base branch:**

    Always explicitly checkout the base branch first to avoid accidentally branching off an unrelated feature branch. This prevents mixing concerns between tasks.

    ```bash
    git checkout develop && git pull origin develop
    ```

    (or `main` for production hotfixes)

    > **Important:** Never create a new branch from another feature branch. Always branch from `develop` (or `main` for hotfixes). If you're currently on a different feature branch, switch to the base branch first. Stash or commit any uncommitted changes before switching.

6. **Create the branch from the base branch:**

    Verify you are on the correct base branch before creating:

    ```bash
    git branch --show-current  # Should output "develop" (or "main")
    git checkout -b feat/TASK-42-add-team-roles
    ```

7. **Confirm** the branch is ready: show the user the branch name, type, and which base it was created from.

## Collision Avoidance

Before creating the branch, check if the name already exists locally or remotely:

```bash
git branch --list 'feat/TASK-42-add-team-roles'
git ls-remote --heads origin 'feat/TASK-42-add-team-roles'
```

If it exists, append `-2`, `-3`, etc. until the name is unused:
`feat/TASK-42-add-team-roles-2`

## Uncommitted Changes

Before switching branches, check for uncommitted work:

```bash
git status --short
```

If there are changes:
1. Show the user what's uncommitted
2. Offer to stash (`git stash push -m "WIP before TASK-42"`) or commit before switching
3. Do not proceed until the working tree is clean or the user confirms

## Notes

- Always pull the latest base branch to minimise merge conflicts later.
- If the task description is vague, ask the user for a better slug rather than guessing.
