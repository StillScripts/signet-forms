---
name: deslop
description: Clean up AI-generated code to match the project's conventions and remove common AI artifacts. Use after implementing a feature with AI assistance, or when code feels over-engineered or overly commented. Removes unnecessary defensive coding, excessive comments, and style inconsistencies that AI tools tend to introduce.
---

# Deslop

Review and clean up AI-generated code on the current branch to match project conventions.

## What to Look For

**Excessive comments:**
- Remove comments that just restate what the code does (`// Create the team` above `Team::create()`)
- Keep PHPDoc blocks for method signatures — the project convention is to use PHPDoc over inline comments
- Only retain inline comments for genuinely complex logic

**Over-defensive code:**
- Unnecessary null checks when the type system or framework already guarantees non-null
- Try/catch blocks that catch and re-throw without adding value
- Redundant `isset()` or `empty()` checks on properties that are always set

**Type and style drift:**
- Functions missing return type declarations (project convention: always declare)
- Missing parameter type hints
- `protected` where `private` would suffice (or vice versa — match siblings)
- Inconsistent use of PHP 8 features (e.g., mixing old-style constructors with constructor promotion)

**Over-engineering:**
- Abstract classes or interfaces for things that only have one implementation
- Service classes for logic that fits naturally in an Action or Model method
- Unnecessary DTOs when a simple array or Eloquent model works fine

**Laravel-specific slop:**
- Using `env()` outside of config files (should use `config()`)
- Manual route URL construction instead of `route()` helper
- Raw queries where Eloquent methods exist
- Importing facades that aren't used

## Process

1. **Get the diff:**
   ```bash
   git diff develop...HEAD
   ```
   (Use `main` for hotfix branches.)

2. **Review each changed file** against the criteria above, checking sibling files to understand the existing style.

3. **Apply fixes** directly — don't just report them. This skill is about cleaning, not reviewing.

4. **Run Pint** after cleanup to ensure formatting is consistent:
   ```bash
   vendor/bin/pint --dirty --format agent
   ```

5. **Report** a brief 1-3 sentence summary of what was cleaned up.

## Notes

- When in doubt about whether something is slop or intentional, check how similar code is written elsewhere in the project. Match the existing style.
- Don't remove things that serve a purpose even if they look like slop — e.g., explicit type casts that handle edge cases in Filament form data.
- This skill pairs well with `/find-bugs` — run deslop first to clean up the code, then find-bugs to check for actual issues.
