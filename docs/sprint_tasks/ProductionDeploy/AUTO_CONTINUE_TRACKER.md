# Auto Continue Tracker — Portfolio Production Deploy

Generated: 2026-03-24

## Execution Order

1. Sprint1:  DigitalOcean Droplet And Forge Account Setup
2. Sprint2:  Server Provisioning Via Forge
3. Sprint3:  Domain And DNS Configuration
4. Sprint4:  GitHub Repository And Forge Deployment Wiring
5. Sprint5:  Environment Configuration And Secrets Management
6. Sprint6:  Database Setup And Migrations
7. Sprint7:  WordPress Integration In Production
8. Sprint8:  Multi-Site Handling (Portfolio And ElasticGun)
9. Sprint9:  SSL And Security Hardening
10. Sprint10: CI/CD Auto-Deploy On Git Push
11. Sprint11: Monitoring, Logging, And Alerting
12. Sprint12: Backups And Disaster Recovery
13. Sprint13: Final Polish, Smoke Tests, And Public Launch
14. Sprint14: API-First Content Sync Cutover (Post-Launch)

## Status Board

- Sprint1:  complete (2026-03-23)
- Sprint2:  complete (2026-03-23)
- Sprint3:  complete (2026-03-23)
- Sprint4:  complete (2026-03-23)
- Sprint5:  complete (2026-03-23)
- Sprint6:  ready
- Sprint7:  in-progress (WordPress production base URL and sync validation pending)
- Sprint8:  complete (2026-03-23)
- Sprint9:  in-progress (core HTTPS complete, remaining hardening backlog)
- Sprint10: deferred (execute at end of sprint sequence)
- Sprint11: not-started
- Sprint12: not-started
- Sprint13: not-started
- Sprint14: not-started

## Current Progress

- Sprint1 to Sprint4 completed and both Laravel sites are deployed in Forge.
- Sprint5 completed:
	- Production env values normalized for both sites.
	- Duplicate `.env` mail keys removed so runtime config is deterministic.
	- Cache rebuild workflow validated (`optimize:clear`, `config:cache`, `route:cache`, `view:cache`).
- Sprint8 completed:
	- Multi-site production routing repaired for apex and `www` aliases.
	- Both sites now serve correctly (apex 200, `www` 301 redirect).
	- Runtime path mismatch (`No input file specified`) resolved.
- Sprint9 progress:
	- HTTPS/TLS is active and valid for both apex domains and `www` redirects.
	- Remaining hardening tasks moved to backlog execution in Sprint9 checklist.
- Mail deliverability stabilization completed for both sites:
	- Contact form body propagation fixed.
	- Resend sandbox constraints handled and then moved to verified-domain sending.
	- `craigparsons.ca` uses `noreply@craigparsons.ca` and passes live probe.
	- `elasticgun.com` uses `noreply@elasticgun.com` and passes live probe.
	- UI confirmation captured: both contact forms now return success and deliver mail to inbox.
- Current blocker / open technical item:
	- WordPress production base URL still logs warnings on content fetch paths; Sprint7 remains in-progress until sync/runtime fetch is fully validated.
- User-directed sequencing change:
	- Sprint10 (CI/CD auto-deploy) intentionally deferred until end-of-sprint cleanup.
- Planned next sequence:
	1. Sprint7: finalize WordPress base URL + production sync validation on both sites.
	2. Sprint9: complete remaining security hardening checklist items.
	3. Sprint6 verification pass: explicitly re-run migration/state checks and record evidence.
	4. Sprint10: implement auto-deploy on push once sprint stabilization closes.
- Backlog item after public launch:
	1. Sprint14: replace DB-over-SSH production content push with signed API-first import endpoint and command flow.

## Per-Sprint Execution Rule

1. Complete sprints in order: Sprint1 → Sprint14.
2. After each sprint, mark it complete here with a date and summary note.
3. Record any blockers and their resolutions before moving to the next sprint.
4. Do not skip validation or security sprints (Sprint 9, 12, 13).
