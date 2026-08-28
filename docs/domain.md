# Domain Boundaries (Hard Lines)

You now have 3 bounded contexts:

## 1️⃣ WritersRoom (Product Domain)

Owns:

* Epics
* Stories
* Sprint
* Sprint goal
* Story status

Emits events:

* `SprintCreated`
* `StoryAddedToSprint`
* `SprintReady`

Does NOT:

* Know about Tasks
* Know about Runs
* Know about Confidence

---

## 2️⃣ DevBacklog (Execution Domain)

Owns:

* Tasks
* task_queue
* Runs
* Claim protocol
* Attempt counter

Listens for:

* `SprintReady`
* `StoryApproved`

Emits:

* `TaskCompleted`
* `RunCreated`
* `TaskExhaustedRetries`

Does NOT:

* Calculate confidence
* Decide final success

---

## 3️⃣ QAQueue (Verification Domain)

Owns:

* Evaluation
* Failure taxonomy
* Confidence math
* Escalation rules

Listens for:

* `RunCreated`

Emits:

* `TaskRetryRequested`
* `TaskSucceeded`
* `TaskEscalated`

Does NOT:

* Decompose tasks
* Modify story scope

---

# Enforcing Boundaries (This Is How You Avoid the Mess)

Inside a single Laravel app:

```
App/
  Domains/
    WritersRoom/
    DevBacklog/
    QAQueue/
```

Each domain gets:

* Its own Models
* Its own Services
* Its own Event listeners
* Its own namespace

And most importantly:

🚫 No direct cross-domain model calls.

Only events.



Okay.

Now we’re talking like an architect instead of someone just spinning up repos 😄

You’re not wrong about inter-dependencies. They *will* creep in if boundaries aren’t enforced. But the solution isn’t “more apps.” It’s **clear contracts and event boundaries**.

And I love the correction about sprints having one goal. That’s not a small tweak — that changes the shape of the system.

Let’s rewrite your pipeline correctly and cleanly.

---

# The Real Flow (Corrected)

```
Story marked Ready
   ↓
Piper creates Sprint
   - Sprint has ONE goal
   - Stories selected to support that goal
   ↓
Mason decomposes Stories → Tasks
   ↓
Tasks enter task_queue
   ↓
Goose executes (one task at a time)
   ↓
Run saved
   ↓
QAQueue evaluates
   ↓
If retry → requeue task
If success → mark task done
If low confidence → escalate to Mason or human
```

That’s a real dev org loop.

---

# Option C — But Done Properly

You chose Option C (event-driven internal modules).

Good. That’s the right move if you’re serious.

The trick is this:

> Separate by **domain**, not by deployment.

Even if later those domains run on 3 machines.

---

# Domain Boundaries (Hard Lines)

You now have 3 bounded contexts:

## 1️⃣ WritersRoom (Product Domain)

Owns:

* Epics
* Stories
* Sprint
* Sprint goal
* Story status

Emits events:

* `SprintCreated`
* `StoryAddedToSprint`
* `SprintReady`

Does NOT:

* Know about Tasks
* Know about Runs
* Know about Confidence

---

## 2️⃣ DevBacklog (Execution Domain)

Owns:

* Tasks
* task_queue
* Runs
* Claim protocol
* Attempt counter

Listens for:

* `SprintReady`
* `StoryApproved`

Emits:

* `TaskCompleted`
* `RunCreated`
* `TaskExhaustedRetries`

Does NOT:

* Calculate confidence
* Decide final success

---

## 3️⃣ QAQueue (Verification Domain)

Owns:

* Evaluation
* Failure taxonomy
* Confidence math
* Escalation rules

Listens for:

* `RunCreated`

Emits:

* `TaskRetryRequested`
* `TaskSucceeded`
* `TaskEscalated`

Does NOT:

* Decompose tasks
* Modify story scope

---

# The 3 Computer Idea (This Is Actually Smart)

You said:

> At some point it would make sense to have 3 separate computers.

That’s not crazy.

Imagine:

* Machine 1 → WritersRoom (Product brain)
* Machine 2 → Dev executor (Goose runner)
* Machine 3 → QA evaluator (Vera node)

They communicate via:

* Shared DB
  or
* Message bus (Redis, RabbitMQ, Postgres LISTEN/NOTIFY)

That’s basically simulating a real dev team physically.

The key is:

> Even if they run on one machine now, they must behave like they’re remote.

That’s how you avoid interdependencies.

---

# Enforcing Boundaries (This Is How You Avoid the Mess)

Inside a single Laravel app:

```
App/
  Domains/
    WritersRoom/
    DevBacklog/
    QAQueue/
```

Each domain gets:

* Its own Models
* Its own Services
* Its own Event listeners
* Its own namespace

And most importantly:

🚫 No direct cross-domain model calls.

Only events.

---

# The Automated Loop (Clean Version)

Let’s formalize it.

```
[SprintGoalDefined]
   ↓
Stories structured
   ↓
Sprint marked Ready
   ↓
DevBacklog generates Tasks
   ↓
Task queued
   ↓
Goose executes
   ↓
RunCreated event
   ↓
QAQueue evaluates
   ↓
Verdict emitted
   ↓
If retry → TaskRequeued
If success → TaskClosed
If low confidence → EscalationRequested
```

Every arrow is an event.

No direct calls.

---

# Important Architectural Rule

Each domain should expose:

```
Public API:
  - Commands (what can be requested)
  - Events (what it emits)

Internal:
  - Services
  - Logic
  - DB schema
```

If you follow that strictly, you can:

* Run them in one app
* Split them later
* Run them on 3 machines
* Or containerize them

Without rewriting everything.
