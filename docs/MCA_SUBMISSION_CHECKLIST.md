# MCA Submission Checklist

Official deadline: **2026-07-05**  
Internal finish target: **2026-07-04**  
Project: Multi-Tenant School Administration Management SaaS Platform

Use this checklist after Phase 10 release approval. Mark items as you complete
them.

## Current Portal Status

| Item | Status |
| ---- | ------ |
| Qollabb milestones | 5 / 6 (payment integration marked complete) |
| Remaining milestone | Deploy and Test Application |
| Final report upload | Pending |
| Evaluation | Pending |

## Week 1 — QA And Deployment Prep (now → 29 Jun)

### Engineering

- [ ] Run full suite: `php artisan test` (target: 394 passed)
- [ ] Run Pint and `composer validate`
- [ ] Two-browser tenant demo: SHA vs SHB (screenshot)
- [ ] Four-role smoke script: Super Admin, School Admin, Teacher, Accountant
- [ ] Capture 25+ sanitized screenshots per `MCA_REPORT_NOTES.md`
- [ ] Export database schema dump for report appendix

### Cloud deployment

- [ ] Provision VPS + MySQL 8 (see `DEPLOYMENT_GUIDE.md`)
- [ ] Configure production `.env` (`APP_DEBUG=false`, HTTPS)
- [ ] `composer install --no-dev`, `npm run build`, `migrate --seed`
- [ ] Post-deploy smoke tests on live URL
- [ ] Record live URL: `https://schoolportal.pagescorch.com` in Qollabb

### Report drafting (parallel — 25% time daily)

- [ ] Chapters 1–3 draft from `PROJECT_OVERVIEW.md`, `MODULE_SPECIFICATIONS.md`
- [ ] Chapter 4 from `ER_DIAGRAM.md`, `DATABASE_DESIGN.md`
- [ ] Start figure list (architecture, ER, DFD, use case)

## Week 2 — Report, Presentation, Milestone 6 (30 Jun → 4 Jul)

### Report

- [ ] Chapter 5 — implementation modules + screenshots
- [ ] Chapter 6 — testing strategy + 394-test evidence table
- [ ] Chapter 7 — results, performance notes, deployment screenshots
- [ ] Chapter 8 — conclusion and future work
- [ ] Chapter 9 — 20+ APA references
- [ ] Preliminary pages: cover, bonafide, declaration, acknowledgement, abstract
- [ ] Appendices: installation guide, deployment guide, DB dump reference
- [ ] Abstract 150–250 words (write last)
- [ ] Export PDF < 20 MB

### Presentation

- [ ] 12–15 slides per `MCA_SUBMISSION_MASTER_PLAN.md` §15
- [ ] Export PDF < 20 MB; keep editable PPT source

### Viva preparation

- [ ] Viva question bank: tenancy, RBAC, security, testing, deployment
- [ ] Rehearse 15-minute live demo script
- [ ] Optional backup video < 20 MB

### Qollabb

- [ ] Mark **Deploy and Test Application** milestone complete
- [ ] Upload final report PDF
- [ ] Upload presentation PDF/PPT
- [ ] Add GitHub URL: `https://github.com/abdulbaquee/multi-tenant-school-saas`
- [ ] Add live demo HTTPS URL: `https://schoolportal.pagescorch.com`
- [ ] Click final Submit before deadline

## Milestone 6 Evidence Package

Attach or reference when marking the milestone:

| Evidence | Location |
| -------- | -------- |
| Live URL | Qollabb link field |
| GitHub repo | Same submission form |
| Test summary | `php artisan test` output screenshot |
| Tenant isolation | Two-school side-by-side screenshots |
| Deployment stack | Chapter 7 + `DEPLOYMENT_GUIDE.md` |
| Installation steps | `INSTALLATION_GUIDE.md` appendix |

## Report Chapter Quick Map

| Chapter | Primary repo sources |
| ------- | ------------------- |
| 1 Introduction | `PROJECT_OVERVIEW.md`, constitution |
| 2 Literature | External papers + Laravel/MySQL docs |
| 3 Analysis | `MODULE_SPECIFICATIONS.md`, `SCREEN_FLOW.md` |
| 4 Design | `ER_DIAGRAM.md`, `DATABASE_DESIGN.md`, `TENANCY_DESIGN.md` |
| 5 Implementation | `CHANGELOG.md`, `DECISIONS_LOG.md`, screenshots |
| 6 Testing | `TESTING_STRATEGY.md`, 394-test result |
| 7 Results | Screenshots, deployment metrics |
| 8 Conclusion | Limitations, future work |
| 9 References | APA register |
| 10 Appendices | This guide, deployment guide, SQL dump |

## Feature Freeze Rules (from 2 Jul)

- No new modules
- Fix only: security defects, broken deployment, report errors, release blockers
- Tag release before each fix: `git tag v1.0.0-mca-submission`

## Mentor Clarifications (ask by 29 Jun)

- [ ] A4 vs Letter and final margin rule
- [ ] One abstract or two in template
- [ ] GitHub URL alone vs live URL required
- [ ] Exact cutoff time on 2026-07-05
- [ ] Plagiarism report requirement

## Definition Of Done

Submission is complete when:

1. Live HTTPS demo works for all four roles.
2. Qollabb shows **6 / 6** milestones.
3. Report PDF and presentation are uploaded.
4. Viva rehearsal script is ready.
5. Repository `main` matches deployed commit.
