---
name: create-pr
description: Create a pull request with a comprehensive description targeting the correct base branch. Use when the user is ready to open a PR for their current branch. Handles pushing, determining the correct target branch, and writing a PR description with context from the changes.
---

# Create PR

Push the current branch and create a pull request with a well-structured description.

## Prerequisites

Before creating a PR, ensure all changes are committed. If there are uncommitted changes, run the `/commit` skill first.

```bash
git status --porcelain
```

If the output shows uncommitted changes, invoke `/commit` before proceeding.

## Process

### Step 1: Verify Branch State

```bash
git status
git log <base-branch>..HEAD --oneline
```

Determine the target branch from the branch prefix:

| Branch prefix | Target |
|---------------|--------|
| `fix/` on `main` | `main` (production hotfixes only) |
| Everything else (`feat/`, `fix/`, `ref/`, `chore/`, `perf/`, `style/`, `docs/`, `test/`, `ci/`, `build/`, `meta/`, `license/`) | `develop` |

Ensure:
- All changes are committed
- Branch is up to date with remote
- Changes are rebased on the base branch if needed

### Step 2: Analyze Changes

Review what will be included in the PR:

```bash
# See all commits that will be in the PR
git log <base-branch>..HEAD

# See the full diff
git diff <base-branch>...HEAD
```

Read the actual diff to write the description — don't guess what changed. Understand the scope and purpose of all changes before writing.

### Step 3: Push the Branch

```bash
git push -u origin HEAD
```

### Step 4: Write the PR Description

Structure the description as:

```markdown
<brief description of what the PR does>

<why these changes are being made — the motivation>

<alternative approaches considered, if any>

<any additional context reviewers need>

TASK-42
```

**Do include:**
- Clear explanation of what and why
- The task key for traceability
- Context that isn't obvious from the code
- Notes on specific areas that need careful review

**Do NOT include:**
- "Test plan" sections or checkbox lists of manual testing steps
- Redundant summaries of the diff (reviewers can read the code)

### Step 5: Create the PR

Create as a draft PR so CI can run before requesting review:

```bash
gh pr create --draft --base develop --title "<type>(scope): Description — TASK-42" --body "$(cat <<'EOF'
<description body here>

TASK-42
EOF
)"
```

(Use `--base main` for production hotfix branches.)

## PR Title Format

`<type>(scope): Description — TASK-<number>`

The type prefix should match the branch type:

- `feat(teams): Add role management — TASK-42`
- `fix(auth): Handle null user on login redirect — TASK-15`
- `ref(notifications): Extract to shared service — TASK-30`
- `chore(ci): Update PHP matrix to 8.4 — TASK-51`
- `docs(api): Add endpoint documentation — TASK-60`

Task key format comes from `/task-management` and is the source of truth.

If the branch has a single commit, use that commit's subject line (with the type prefix) as the PR title. For multi-commit branches, write a title that summarises the overall change.

## PR Description Examples

### Feature PR

```markdown
Add Slack notifications for team invitation events

When a team member is invited, accepted, or removed, we now send
notifications to the team's configured Slack channel. This keeps team
admins informed without checking the app.

Previously considered email-only notifications, but Slack threading
gives better visibility for teams that are already active there.

TASK-42
```

### Bug Fix PR

```markdown
Handle null user in team settings membership query

The team settings page could crash when a membership record referenced
a soft-deleted user, causing a 500 on the members table. This adds
proper null handling and filters out orphaned memberships.

Found while investigating TASK-15.

TASK-15
```

### Refactor PR

```markdown
Extract team permission checks into dedicated policy methods

Moves inline Gate checks from TeamSettings into named policy methods.
No behavior change — same authorization logic, better testability.

Prepares for TASK-30 which adds granular permission management.

TASK-30
```

## Editing Existing PRs

Use `gh api` to update PRs after creation:

```bash
# Update PR description
gh api -X PATCH repos/{owner}/{repo}/pulls/PR_NUMBER -f body="$(cat <<'EOF'
Updated description here
EOF
)"

# Update PR title
gh api -X PATCH repos/{owner}/{repo}/pulls/PR_NUMBER -f title='feat(scope): New title — TASK-42'

# Update both
gh api -X PATCH repos/{owner}/{repo}/pulls/PR_NUMBER \
  -f title='feat(scope): New title — TASK-42' \
  -f body='New description'
```

## Notes

- If the user hasn't run `/lint` yet, suggest it before creating the PR — Pint failures will block CI.
- Always include the task key in both the title and body so it's easy to trace back.
- One PR per task — don't bundle unrelated changes.
- Smaller PRs get faster, better reviews. If the diff is large, consider whether it can be split.
