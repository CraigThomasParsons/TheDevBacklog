**“How do we turn a human agile backlog into a machine-consumable execution queue without losing rigor?”**

Let’s design this cleanly so **Mason plans**, **Goose executes**, and **Vera judges** — using **Laravel queues as the nervous system**, 

(not just a job runner)

---

# The Backlog (Agent-Native, Agile-Compatible)

## Mental model (important)

You are **not** putting Goose directly on a raw queue.

You are creating:

> **A structured backlog that *humans and agents* can both consume safely.**

Think: Jira backlog × deterministic pipeline × local agents.

---

## Backlog layers (do NOT flatten these)

```
Epics
  ↓
Stories
  ↓
Tasks   ← Mason emits these
  ↓
Runs    ← Goose executes these
```

Only **Tasks** go into the execution queue.

---

## Core database tables (Laravel-first)

### 1. `backlog_items` (planning layer)

This is your **human / Mason space**.

```sql
backlog_items
-------------
id
type            ENUM('epic','story','task')
parent_id       NULLABLE (epic → story → task)
title
description
constraints     JSON
acceptance_criteria JSON
priority        INT
status          ENUM(
                  'backlog',
                  'ready',
                  'blocked',
                  'done'
               )
created_by      ENUM('human','mason')
```

Rules:

* Goose **never** reads this table
* Mason **writes tasks** here
* Humans can edit epics/stories

---

### 2. `task_queue` (the sprint stack)

This is the **unassigned work stack** — exactly like agile.

```sql
task_queue
----------
task_id         FK(backlog_items.id)
state           ENUM(
                  'queued',
                  'claimed',
                  'running',
                  'awaiting_verification',
                  'retry_pending',
                  'completed',
                  'escalated'
               )
attempt         INT DEFAULT 0
max_attempts    INT DEFAULT 3
locked_by       VARCHAR   -- goose instance id
locked_at       TIMESTAMP
```

Rules:

* Only `status = ready` tasks enter here
* This is what Goose polls
* One task = one execution unit

---

### 3. `task_runs` (execution history)

Immutable, append-only.

```sql
task_runs
---------
id
task_id
run_number
executor        ENUM('goose','human')
started_at
ended_at
actions_log     JSON
artifacts_path  VARCHAR
exit_status     ENUM('success','failure')
```

This is **evidence**, not decisions.

---

### 4. `task_evaluations` (Vera truth layer)

```sql
task_evaluations
----------------
task_id
run_number
verdict         ENUM(
                  'success',
                  'retry',
                  'fail',
                  'ambiguous',
                  'invalid_task',
                  'escalated'
               )
failure_type    NULLABLE
confidence      FLOAT
confidence_delta FLOAT
guidance        JSON
evaluated_at
```

Only Vera writes here.

---

## How Goose “takes a ticket” (exactly like a dev)

### Claim protocol (atomic)

```sql
UPDATE task_queue
SET
  state = 'claimed',
  locked_by = :goose_id,
  locked_at = NOW()
WHERE
  state = 'queued'
ORDER BY priority DESC, task_id ASC
LIMIT 1;
```

If no rows updated → Goose sleeps.

💡 This mirrors:

> “Developer pulls next ticket from backlog”

---

## Goose execution contract (strict)

Once Goose claims a task:

1. Fetch task details (`backlog_items`)
2. Increment attempt
3. Transition → `running`
4. Execute **once**
5. Write `task_runs`
6. Transition → `awaiting_verification`
7. STOP

Goose **never decides success**.

---

## Vera’s role in the backlog

Vera watches:

```
state = awaiting_verification
```

Then:

* Reads task
* Reads run artifacts
* Applies confidence math
* Writes `task_evaluations`
* Updates `task_queue.state`:

| Verdict      | Next state                 |
| ------------ | -------------------------- |
| success      | completed                  |
| retry        | retry_pending              |
| ambiguous    | completed (low confidence) |
| invalid_task | escalated                  |
| fail         | escalated                  |

---

## Retry flow (clean + boring)

```
retry_pending
   ↓ (Piper gate / delay / backoff)
queued
```

Rules:

* Attempt++
* Retry guidance attached
* Same task_id, new run_number
* Confidence carries forward

---

## Why this backlog design works

### Agile-compatible

* Looks like Jira to humans
* Has backlog, ready, done
* Supports priority & sprint selection

### Agent-safe

* No hidden state
* Atomic claiming
* Deterministic retries
* Full audit trail

### Resume-grade

You can *truthfully* say:

> “Designed an agent-driven backlog system using Laravel queues, separating planning, execution, and verification with confidence-based retries.”

That’s not fluff — that’s architecture.

---

## Key rule (tattoo this mentally)

> **Backlog is intent.
> Queue is commitment.
> Run is evidence.
> Evaluation is truth.**

Never mix them.

---

## TL;DR

* Mason fills the backlog
* Ready tasks enter `task_queue`
* Goose pulls exactly one task like a dev
* Vera judges outcomes + confidence
* Retries are explicit and bounded
* Laravel queues become an *agent sprint system*

If you want next, we can:

* Write the **Laravel Eloquent models**
* Define the **Goose poller loop**
* Add **human override / manual claim**
* Design **sprint windows** for agents
* Or wire this into **Piper UI**

You’re basically building **Agile for machines** — and doing it right.
