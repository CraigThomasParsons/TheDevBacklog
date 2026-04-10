# DevBacklog Thoughts Log

## 2026-02-18 - Mason Run State dashboard

- Intent:
  Add a visible Mason execution state so sprint progress can be inspected without reading daemon logs.
- What changed:
  Added `GET /api/mason/run-state` for machine-readable state.
  Added `GET /mason/state` for a human-readable dashboard.
  Added `Mason State` link in the main navigation.
  Snapshot includes current sprint selection, status counts, WIP, current in-progress story, next ready queue, enabler progress, and last task update time.
- Why:
  This supports the "Mason selects one story at a time and keeps running until sprint is done" workflow with immediate operational visibility.
- Test results:
  Route checks passed for both new endpoints.
  PHP syntax checks passed for new classes.
  `php artisan test` has one pre-existing failing example test (expects `/` 200 but app now redirects to `/sprints` with 302).

## 2026-02-18 - Start Sprint control and heartbeat

- Intent:
  Make Mason execution explicit and observable so users can start sprint execution and verify liveness quickly.
- What changed:
  Added `mason_run_controls` table and singleton model.
  Added `POST /mason/state/start` (web) + `POST /api/mason/run-state/start`.
  Added `POST /api/mason/run-state/heartbeat` for Mason daemon updates.
  Extended run-state snapshot with run control state and heartbeat freshness.
  Updated Mason State page with `Start Sprint` button and runner control panel.
- Why:
  Mason may be active in the background but looked idle from UI; explicit start intent + heartbeat removes ambiguity.
- Follow-up:
  Added `Start Sprint` action directly to Current Sprint Board header for fast access.
  Added `Stop Sprint` action, disabled repeated starts while running, and a visible sprint runner status bar on Current Sprint Board (running/stopped + heartbeat + last status message).

## 2026-02-18 - Task plan progress visibility

- Intent:
  Make story task plans reflect real execution progress instead of staying `queued`.
- What changed:
  Added API endpoint `POST /api/stories/{story}/tasks/{externalTaskId}/state`.
  Mason can now mark each task as `in_progress` before execution and `completed`/`failed` after execution.
- Why:
  Operators need to see visible movement inside ticket task plans while Mason works.

## 2026-02-18 - Story comments for Mason handoff/status

- Intent:
  Add shared story comments so Mason can narrate work progress, blockers, and handoff points directly in the ticket.
- What changed:
  Added `story_comments` table and `StoryComment` model.
  Added human comment form on story page (`POST /stories/{story}/comments`) with optional name (defaults to Anonymous).
  Added API endpoints for automation:
  `GET /api/stories/{story}/comments`
  `POST /api/stories/{story}/comments`
- Why:
  A visible thread in the ticket enables cross-LLM handoffs and preserves execution context where humans already review story work.

## 2026-02-18 - Queued second sprint (non-interfering)

- Intent:
  Queue next sprint planning work without changing the active sprint execution.
- What changed:
  Created draft sprint `Sprint 1 - Platform Enablers` (id `8`) and epic `Sprint 1 - Platform Enablers for Containerized DB Workflow` (id `7`) under project `Agile Medieval Peasant Board`.
  Added 4 Mason enabler stories focused on Docker Compose + DB/test workflow readiness.
- Why:
  This prepares infrastructure/testability work as a dedicated sprint while leaving Sprint 0 active and untouched.

## 2026-02-18 - Sprint completion with automatic carryover

- Intent:
  Allow closing a sprint while preserving unfinished work by moving it into the next sprint.
- What changed:
  Added `POST /sprints/{sprint}/complete`.
  Added `Complete Sprint` button on sprint detail page.
  Completion behavior:
  closes current sprint (`closed` status),
  moves unfinished stories to next draft/ready sprint,
  creates a carryover draft sprint when none exists,
  resets moved stories to `ready` status.
- Why:
  Supports end-of-sprint workflow without losing WIP and avoids manual story re-assignment.

## 2026-02-18 - Freeze semantics fix (membership lock, not board lock)

- Intent:
  Align frozen sprint behavior with expected workflow: no story add/remove, but execution movement allowed.
- What changed:
  Removed frozen guard from board update endpoint.
  Kept board payload validation requiring the same story set, which still prevents add/remove via board API.
  Updated Current Sprint and Sprint Show copy to clarify freeze behavior.
- Why:
  Teams still need to move stories across To Do/In Progress/In Review/Done during execution after sprint scope is locked.

## 2026-02-18 - Mason blocker chat visibility

- Intent:
  Let Mason ask for help when blocked and make it obvious to humans where to respond.
- What changed:
  Story page now shows an explicit banner when latest Mason blocker comment has no human reply.
  Human replies in story comments are treated as unblock signals for Mason.
- Why:
  This creates a practical human/LLM handoff path in the same ticket comment thread.

## 2026-02-18 - Mason Chat tab (Livewire)

- Intent:
  Provide a dedicated chat surface to ask Mason what it is doing and whether it is blocked.
- What changed:
  Installed Livewire and added `Mason Chat` nav tab/page (`/mason/chat`).
  Added `mason_chat_messages` storage + API endpoints:
  `GET /api/mason/chat/messages`
  `GET /api/mason/chat/inbox`
  `POST /api/mason/chat/messages`
  Livewire panel auto-refreshes every 2 seconds for near-real-time conversation.
- Why:
  A direct operator chat channel is easier than digging through story comments during execution.

## 2026-02-18 - Mason Chat realtime via Laravel Reverb

- Intent:
  Upgrade Mason Chat from polling-only refresh to websocket-driven updates.
- What changed:
  Installed Laravel Reverb and added `config/broadcasting.php` + `config/reverb.php`.
  Added `MasonChatMessageCreated` broadcast event on the `mason-chat` channel.
  Wired chat message creation (human/API) to dispatch the broadcast event.
  Added a `reverb` service to Docker Compose and `/app` websocket proxy in nginx.
  Updated Mason Chat frontend to subscribe with Pusher client and trigger Livewire refresh on event receipt.
  Kept `wire:poll` as a fallback if websocket transport is unavailable.
- Why:
  Operators get immediate message visibility without waiting for polling intervals, while preserving resilience.

## 2026-02-18 - Mason provider switch control (Codex/Claude/Gemini/Goose)

- Intent:
  Allow operators to switch Mason execution provider without editing Mason config files manually.
- What changed:
  Added `provider_override` to `mason_run_controls`.
  Added provider catalog config in `config/mason.php`.
  Extended run-state snapshot and API payload with provider options + current override.
  Added UI control on Current Sprint and Mason State pages to set provider mode.
  Added API endpoint `POST /api/mason/run-state/provider` and web endpoint `POST /mason/state/provider`.
- Why:
  When one provider is slow or rate-limited, operators can switch Mason to another provider immediately from DevBacklog.
