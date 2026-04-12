---
name: create-pr
description: Create a pull request with a comprehensive description targeting the correct base branch. Use when the user is ready to open a PR for their current branch. Handles pushing, determining the correct target branch, and writing a PR description with context from the changes.
---

# Create PR

Push the current branch and create a pull request with a well-structured description.

## Process

1. **Verify the branch is clean:**
   ```bash
   git status
   ```
   If there are uncommitted changes, ask the user whether to commit them first (using the `/commit` skill) or proceed without them.

2. **Determine the target branch:**
   - `feature/*` branches → target `develop`
   - `hotfix/*` branches → target `main`

3. **Push the branch:**
   ```bash
   git push -u origin HEAD
   ```

4. **Analyze the changes** to write the PR description:
   ```bash
   git log develop..HEAD --oneline
   git diff develop...HEAD --stat
   ```
   (Use `main` instead of `develop` for hotfix branches.)

5. **Create the PR:**
   ```bash
   gh pr create --base develop --title "[TASK-42] Add team role management" --body "$(cat <<'EOF'
   ## What does this do?

   [2-3 sentence summary of the changes and why they were made]

   ## How to test

   - [ ] Step-by-step instructions for manual testing
   - [ ] Note any specific test data or setup needed

   ## Changes

   - List of key files/areas modified
   - Any new models, migrations, or routes added

   ## Task

   TASK-42
   EOF
   )"
   ```

## PR Title Format

`[TASK-<number>] Imperative description` — mirrors the commit message format.

Task key format comes from `/task-management` and is the source of truth.

If the branch has a single commit, use that commit's subject line as the PR title. For multi-commit branches, write a title that summarises the overall change.

## Notes

- If the user hasn't run `/lint` yet, suggest it before creating the PR — Pint failures will block CI.
- Always include the task key in both the title and body so it's easy to trace back.
- Read the actual diff to write the description — don't guess what changed.
