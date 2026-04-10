# Phase Sprint Map — Portfolio Production Deploy

Date: 2026-03-22

## Overview

This plan takes the Laravel-based Portfolio and ElasticGun systems from local
development to a live, secure, auto-deploying production environment using
Laravel Forge, DigitalOcean, Nginx, MySQL/MariaDB, and GitHub.

## Sprint Index

1.  Sprint1:  DigitalOcean Droplet And Forge Account Setup
2.  Sprint2:  Server Provisioning Via Forge
3.  Sprint3:  Domain And DNS Configuration
4.  Sprint4:  GitHub Repository And Forge Deployment Wiring
5.  Sprint5:  Environment Configuration And Secrets Management
6.  Sprint6:  Database Setup And Migrations
7.  Sprint7:  WordPress Integration In Production
8.  Sprint8:  Multi-Site Handling (Portfolio And ElasticGun)
9.  Sprint9:  SSL And Security Hardening
10. Sprint10: CI/CD Auto-Deploy On Git Push
11. Sprint11: Monitoring, Logging, And Alerting
12. Sprint12: Backups And Disaster Recovery
13. Sprint13: Final Polish, Smoke Tests, And Public Launch
14. Sprint14: API-First Content Sync Cutover (Post-Launch)

## Status Notes

- 2026-03-22: Plan created. All sprints marked `not-started`.
- 2026-03-23: Added Sprint14 as a post-launch backlog item to replace direct DB-over-SSH content push with an authenticated API-first sync path.
- 2026-03-23: Recovered production runtime after routing/env drift; both sites now pass apex HTTPS and `www` redirect checks.
- 2026-03-23: Completed mail delivery stabilization on both sites using verified-domain sender addresses and deterministic env configuration.
- 2026-03-23: Sprint10 (CI/CD auto-deploy on Git push) explicitly deferred by user until end-of-sprint stabilization phase.
- 2026-03-24: Verified-domain sending confirmed through UI on both sites; contact forms now return success and deliver full message bodies.
