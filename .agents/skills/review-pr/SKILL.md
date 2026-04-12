---
name: review-pr
description: Review a pull request by posting inline comments on specific files and a summary comment. Each comment is labelled as a Suggestion, Recommendation, or Critical issue. Use when reviewing PRs for code quality, security, and correctness. Covers Laravel, Filament, Livewire, and Pest-specific patterns.
---

# Review PR

Review a pull request by posting inline comments directly on changed files, then posting a summary comment. Each finding is classified by severity so the author knows exactly what must be fixed vs. what is optional.

**Requires**: GitHub CLI (`gh`) authenticated and available.

## Severity Levels

Every review comment MUST use exactly one of these severity labels:

| Level | Emoji | Meaning | Blocks merge? |
|-------|-------|---------|---------------|
| **Suggestion** | 🔵 | Nice-to-have. Style preferences, minor readability improvements, or alternative approaches. | No |
| **Recommendation** | 🟡 | Should be addressed. Performance improvements, better patterns, missing edge cases that are unlikely but worth considering. | No |
| **Critical** | 🔴 | Must be fixed before merge. Security vulnerabilities, runtime errors, data loss risks, breaking changes, or missing test coverage for critical paths. | Yes |

## Process

### Step 1: Identify the PR

Determine the PR to review. If a PR number or URL is provided, use that. Otherwise, check the current branch:

```bash
gh pr view --json number,url,headRefName,baseRefName,title,author
```

If no PR is found, stop and inform the user.

### Step 2: Fetch the PR Diff

Get the full list of changed files and the diff:

```bash
# List changed files
gh pr diff --name-only

# Get the full diff
gh pr diff
```

If the diff is large, read each changed file individually to ensure you see every changed line. Do NOT skip files.

### Step 3: Read Changed Files in Full Context

For each changed file, read the **full file** (not just the diff hunks) so you understand the surrounding context. Understanding the full context is critical — a change may look fine in isolation but be wrong in context.

Also use `search-docs` (Laravel Boost MCP) to verify any patterns you're unsure about against current Laravel/Filament/Livewire documentation.

### Step 4: Review the Changes

Apply the review checklist below to every changed file. For each issue found, note:

1. The file path and line number
2. The severity level (Suggestion / Recommendation / Critical)
3. A clear, actionable description
4. A code example showing the fix (when helpful)

### Step 5: Post Inline Comments

Post each finding as an inline comment on the PR using the GitHub API:

```bash
gh api repos/{owner}/{repo}/pulls/{pr_number}/comments \
  -f body="🔴 **Critical**: Missing authorization check.

This action allows any team member to remove other members without checking permissions. Add a Gate check:

\`\`\`php
Gate::authorize('removeMember', \$team);
\`\`\`" \
  -f commit_id="$(gh pr view {pr_number} --json headRefOid --jq '.headRefOid')" \
  -f path="app/Filament/Pages/TeamSettings.php" \
  -F line=45 \
  -f side="RIGHT"
```

**Important notes on inline comments:**

- Use `side: "RIGHT"` for lines added/modified in the PR (most comments)
- Use `side: "LEFT"` only when commenting on removed lines
- The `line` number must correspond to the line number in the diff hunk, NOT the absolute file line number — use the diff output to determine the correct line
- For multi-line comments, use `start_line` and `line` together with `start_side` and `side`

**Getting the correct commit SHA:**

```bash
COMMIT_SHA=$(gh pr view {pr_number} --json headRefOid --jq '.headRefOid')
```

### Step 6: Post Summary Comment

After all inline comments are posted, post a summary comment on the PR:

```bash
gh pr comment {pr_number} --body "$(cat <<'EOF'
## Code Review Summary

**Overall**: ✅ Recommend Approve / ⚠️ Approve with suggestions / ❌ Changes requested

### Issues Found

| Severity | Count |
|----------|-------|
| 🔴 Critical | X |
| 🟡 Recommendation | Y |
| 🔵 Suggestion | Z |

### Key Observations

- <main theme or pattern observed>
- <another observation>

### Items for Senior Review

- <any schema changes, new dependencies, or architectural decisions that need senior input>

---
*Reviewed by Claude using the review-pr skill*
EOF
)"
```

**Decision logic for overall assessment:**

- **✅ Recommend Approve**: No Critical issues, at most minor Suggestions
- **⚠️ Approve with suggestions**: No Critical issues, but Recommendations worth addressing
- **❌ Changes requested**: One or more Critical issues that must be fixed

### Step 7: DO NOT Approve or Request Changes

**IMPORTANT: Claude must NEVER submit a formal review approval or request-changes status.** Only a human developer can approve or request changes on a PR. Claude's role is limited to posting inline comments and the summary comment.

Do NOT run:

- `gh pr review --approve`
- `gh pr review --request-changes`

The summary comment's "Overall" field is Claude's **recommendation**, not a binding review action.

## Review Checklist

Apply these checks to every changed file.

### Runtime Errors

- Potential null access (missing `?->` or `??` operators)
- Unhandled exceptions or missing error boundaries
- Type mismatches or unsafe casts
- Off-by-one errors, boundary conditions

### Laravel & PHP

Before reviewing Laravel code, read the relevant rules from the `laravel-best-practices` skill at `.agents/skills/laravel-best-practices/rules/`. Key rule files to consult based on what the PR touches:

| PR touches | Read rule file |
|------------|---------------|
| Models, relationships, scopes | `eloquent.md` |
| Database queries, joins, subqueries | `advanced-queries.md`, `db-performance.md` |
| Migrations | `migrations.md` |
| Controllers, routes | `routing.md`, `architecture.md` |
| Form validation | `validation.md` |
| Authorization, auth guards | `security.md` |
| Blade templates | `blade-views.md` |
| Caching | `caching.md` |
| Jobs, queues | `queue-jobs.md` |
| Events, notifications | `events-notifications.md` |
| Error handling | `error-handling.md` |
| HTTP client usage | `http-client.md` |
| Scheduled commands | `scheduling.md` |
| Config or environment | `config.md` |
| Collections | `collections.md` |
| Tests | `testing.md` |
| Code style | `style.md` |

Apply the conventions from those rule files when reviewing. Flag deviations as 🟡 Recommendation or 🔴 Critical depending on severity.

Additionally, always check for:

- **Mass assignment**: Are `$fillable` or `$guarded` correctly set? Any `Model::create($request->all())`?
- **N+1 queries**: Missing eager loading (`with()`, `load()`) in relationships accessed in loops or views?
- **Query safety**: String interpolation in `DB::raw()`, `whereRaw()`, or `selectRaw()`?
- **Validation**: Inputs validated with Form Requests or inline rules before use?
- **Authorization**: `Gate::allows()`, `$this->authorize()`, or policy checks before state changes?
- **Transactions**: Multiple related writes wrapped in `DB::transaction()`?
- **Missing indexes**: Columns used in `where`/`orderBy` clauses without indexes?
- **File uploads**: MIME type validation (not just extension)? Storage path sanitized?
- **Migrations**: Rollback safe? Data integrity preserved? Index coverage for new columns?

### Filament-Specific

- **Tenant isolation**: Are pages/resources scoped to the current tenant?
- **Cross-tenant access**: Can a user from Team A access Team B's resources through Filament actions?
- **Policy checks**: Do table actions and header actions use `visible()` with Gate/policy checks?
- **Form validation**: Are Filament form inputs validated in the schema before the action runs?

### Livewire-Specific

- **Public property exposure**: Are public properties that shouldn't be user-modifiable protected with `#[Locked]`?
- **Action validation**: Do Livewire actions validate before persisting?
- **Unescaped output**: Any `{!! !!}` in Blade templates without justification?

### Security (OWASP)

- SQL injection, command injection, template injection
- XSS — all outputs properly escaped?
- CSRF — state-changing operations protected?
- Authentication checks on all protected operations?
- Authorization/IDOR — access control verified, not just auth?
- Race conditions — TOCTOU in read-then-write patterns?
- Information disclosure — error messages, logs, debug output?
- Secrets in code or logs?

### Performance

- Unbounded operations or missing pagination
- Unnecessary allocations or repeated queries
- Large payloads without limits

### Backwards Compatibility

- Breaking API changes without migration path
- Database migrations that could cause downtime
- Changed method signatures without updating callers
- Removed public methods or routes still in use

### Test Coverage

- New functionality without corresponding Pest tests
- Critical paths without test coverage
- Tests that don't assert meaningful behavior
- Missing edge case coverage
- Factory states used correctly?

### Design & Architecture

- Does the change align with existing patterns? Check sibling files.
- Are there simpler approaches?
- Does it introduce unnecessary coupling?
- Single responsibility — do new classes/methods do one clear job?
- Can new behaviour be tested without excessive setup?

## Common Patterns to Flag

```php
// 🔴 Critical: SQL injection
User::whereRaw("email = '{$request->email}'");
// Fix: User::where('email', $request->email);

// 🔴 Critical: Mass assignment
$team->update($request->all());
// Fix: $team->update($request->validated());

// 🔴 Critical: Missing authorization
public function destroy(Team $team) {
    $team->delete();
}
// Fix: Gate::authorize('delete', $team); $team->delete();

// 🟡 Recommendation: N+1 query
$teams = Team::all();
foreach ($teams as $team) {
    echo $team->owner->name; // N+1!
}
// Fix: Team::with('owner')->get();

// 🟡 Recommendation: Missing transaction
$team->memberships()->delete();
$team->delete();
// Fix: DB::transaction(function () use ($team) { ... });

// 🔵 Suggestion: Guard clause
public function handle(Request $request) {
    if ($request->has('team_id')) {
        // 20 lines of logic
    }
}
// Consider: if (!$request->has('team_id')) return; ...
```

## When There Are No Issues

If the PR looks good with no issues to flag:

```bash
gh pr comment {pr_number} --body "$(cat <<'EOF'
## Code Review Summary

**Overall**: ✅ Recommend Approve

No issues found. The changes are clean, well-tested, and follow established patterns.

*A developer should review the above and submit a formal approval.*
EOF
)"
```

## Guidelines

- **Be direct**: State the issue clearly. Don't hedge with "you might want to consider..."
- **Be actionable**: Every comment should tell the author exactly what to do
- **Include code**: Show the fix when it's not obvious
- **Don't nitpick**: Skip stylistic preferences that Pint handles
- **Don't invent issues**: If the code is clean, say so. A short review is a good review
- **Respect existing patterns**: Flag deviations from codebase conventions, not personal preferences
- **Verify before commenting**: Read the full file context — the issue may already be handled elsewhere
- **Group related issues**: If the same pattern appears in multiple places, mention it once with all locations
- **Use `search-docs`**: Verify Laravel/Filament/Livewire best practices against current docs before flagging
- **Pragmatism**: Match the depth of feedback to the risk — auth, migrations, and tenant isolation deserve more scrutiny than a renamed variable
