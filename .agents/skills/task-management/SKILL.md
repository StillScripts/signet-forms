---
name: task-management
description: Simple task management using a shared TASKS.md file. Reference this when the user asks about their tasks, wants to add/complete tasks, or needs help tracking commitments.
user-invocable: false
---

# Task Management

Tasks are tracked in a simple `TASKS.md` file that both you and the user can edit.

## Task Key Requirement

Every task must have a unique key in this format:

- `TASK-<number>` (for example: `TASK-1`, `TASK-42`, `TASK-105`)
- Prefix is always uppercase `TASK-`
- Number is a positive integer with no leading zeros required
- Never reuse old keys, even for completed/removed tasks

## File Location

**Always use `TASKS.md` in the current working directory.**

- If it exists, read/write to it
- If it doesn't exist, create it with the template below

## Dashboard Setup (First Run)

A visual dashboard is available for managing tasks and memory. **On first interaction with tasks:**

1. Check if `dashboard.html` exists in the current working directory
2. If not, copy it from `${CLAUDE_PLUGIN_ROOT}/skills/dashboard.html` to the current working directory
3. Inform the user: "I've added the dashboard. Run `/productivity:start` to set up the full system."

The task board:
- Reads and writes to the same `TASKS.md` file
- Auto-saves changes
- Watches for external changes (syncs when you edit via CLI)
- Supports drag-and-drop reordering of tasks and sections

## Format & Template

When creating a new TASKS.md, use this exact template (without example tasks):

```markdown
# Tasks

## Active
- [ ] **[TASK-1] Task title** - context, for whom, due date

## Waiting On
- [ ] **[TASK-2] Task title** - waiting on X since YYYY-MM-DD

## Someday
- [ ] **[TASK-3] Task title** - optional context

## Done
- [x] ~~[TASK-4] Completed task title~~ (YYYY-MM-DD)
```

Task format:
- `- [ ] **[TASK-123] Task title** - context, for whom, due date`
- Sub-bullets for additional details
- Completed: `- [x] ~~[TASK-123] Task title~~ (YYYY-MM-DD)`

### Key Allocation Rules

When adding a task:

1. Scan all sections (`Active`, `Waiting On`, `Someday`, `Done`) for existing `TASK-<number>` keys.
2. Pick the next available integer (`max + 1`).
3. Assign that key to the new task.
4. Preserve the key forever when moving between sections.

## How to Interact

**When user asks "what's on my plate" / "my tasks":**
- Read TASKS.md
- Summarize Active and Waiting On sections
- Highlight anything overdue or urgent

**When user says "add a task" / "remind me to":**
- Generate the next key using Key Allocation Rules
- Add to Active section with `- [ ] **[TASK-<n>] Task**` format
- Include context if provided (who it's for, due date)

**When user says "done with X" / "finished X":**
- Find the task (prefer matching by key if provided)
- Change `[ ]` to `[x]`
- Add strikethrough while preserving key: `~~[TASK-<n>] Task~~`
- Add completion date as `YYYY-MM-DD`
- Move to Done section

**When user asks "what am I waiting on":**
- Read the Waiting On section
- Note how long each item has been waiting

## Conventions

- **Bold** the task title for scannability
- Keep task key at the start of the title: `**[TASK-<n>] Title**`
- Include "for [person]" when it's a commitment to someone
- Include "due [date]" for deadlines
- Include "since [date]" for waiting items
- Sub-bullets for additional context
- Keep Done section for ~1 week, then clear old items
- If a user references a task by key, always use that key for all follow-up actions

## Extracting Tasks

When summarizing meetings or conversations, offer to add extracted tasks:
- Commitments the user made ("I'll send that over")
- Action items assigned to them
- Follow-ups mentioned

Ask before adding - don't auto-add without confirmation.
