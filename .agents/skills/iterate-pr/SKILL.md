---
name: iterate-pr
description: Continuously fix CI failures and address review feedback on a pull request until all checks pass. Use after creating a PR when CI fails, or when the user says "fix CI", "iterate on PR", or wants to get a PR to green. Handles Pint lint failures, Pest test failures, and build issues.
---

# Iterate PR

Monitor a PR's CI checks and fix failures until everything passes.

## Process

### 1. Check Current Status

```bash
gh pr checks
```

If checks are still running, wait:
```bash
gh pr checks --watch --fail-fast
```

### 2. Diagnose Failures

For each failing check, get the actual logs:

**Lint failure (Pint):**
```bash
gh run view <run-id> --log-failed
```
Then fix with:
```bash
vendor/bin/pint --dirty --format agent
```

**Test failure (Pest):**
```bash
gh run view <run-id> --log-failed
```
Read the failure output carefully. Common causes:
- Missing migration in test setup
- Incorrect factory state
- Filament panel context not initialised (needs `$this->setUpFilamentPanel()`)
- Assertion on wrong data shape

Run the failing test locally to confirm and iterate:
```bash
php artisan test --compact --filter=TestName
```

**Build failure (npm):**
```bash
gh run view <run-id> --log-failed
```
Fix and verify locally:
```bash
npm run build
```

### 3. Fix and Push

After fixing locally:

1. Run the relevant checks locally to confirm the fix:
   ```bash
   vendor/bin/pint --dirty --format agent
   php artisan test --compact --filter=AffectedTest
   ```

2. Stage and commit the fix:
   ```bash
   git add -A
   git commit -m "[TASK-<number>] Fix CI: brief description of fix"
   ```
   Use the existing PR task key. Task key format is defined by `/task-management`. Use the `/commit` skill's attribution rules if AI attribution is needed.

3. Push and watch:
   ```bash
   git push
   gh pr checks --watch --fail-fast
   ```

### 4. Repeat

If checks fail again, diagnose and fix. Continue until all checks pass.

## When to Stop and Ask

- The same check fails **3 or more times** after fixes — something fundamental might be wrong
- Test failures are in code you didn't touch — could be a flaky test or unrelated breakage
- CI requires secrets or environment variables you don't have access to
- The failure is in a matrix job for a PHP version that behaves differently from local assumptions

## Review Feedback

If the PR has review comments:

```bash
gh pr view --comments
```

Address reviewer feedback alongside CI fixes. Commit review fixes separately from CI fixes so the reviewer can see what changed in response to their comments.
