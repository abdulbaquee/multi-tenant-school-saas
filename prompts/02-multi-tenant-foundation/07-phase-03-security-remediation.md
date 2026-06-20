# Phase 3 Security Remediation

## Role

Act as a senior Laravel security architect, SaaS architect, database architect,
testing specialist, and MCA project reviewer for this repository.

## Objective

Resolve the Phase 3 security-review findings before Phase 4 by hardening
password workflows, revoking sessions after administrative resets, introducing
the documented append-only activity and audit foundations for implemented
workflows, and closing the identified security-test and documentation gaps.

## Execution Mode

Implementation. Modify only authentication and user-management security,
activity/audit recording foundations, directly related tests, configuration,
and security/status documentation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`
- `prompts/00-governance/02-security-review.md`

## Scope

Implement:

- One canonical password rule matching the documented minimum, mixed-case, and
  numeric requirements across user creation, administrative reset, profile
  password update, and forgot-password reset.
- Protection against administrators resetting their own password through User
  Management without current-password confirmation.
- Session and remember-token revocation after an administrator resets another
  user's password.
- Generic, request-throttled forgot-password responses that do not disclose
  whether an account exists.
- `activity_logs` and `audit_logs` migrations and append-only models matching
  `DATABASE_DESIGN.md` exactly.
- A small recording service that derives school/user/request context and strips
  sensitive values.
- Activity recording for login and logout.
- Activity and audit recording for implemented user, school, and School Settings
  mutations.
- Security regression tests for password paths, session revocation, reset-link
  enumeration/throttling, CSRF, escaped output, query binding, and immutable,
  tenant-aware logs.
- Roadmap placement, production session guidance, testing evidence, and
  changelog updates.

## Out Of Scope

Do not implement:

- Activity Log or Audit Log list/detail screens.
- Student, attendance, fee, examination, report, export, or backup modules.
- Student-photo or backup-file storage.
- Third-party auditing, RBAC, security, or tenancy packages.
- New roles, permissions, or navigation entries.
- Hard deletion or log-editing workflows.
- Changes to `composer.json` or package installation.

## Constraints

- Follow the documented `activity_logs` and `audit_logs` dictionaries exactly.
- Logs are append-only and expose no update/delete service or route.
- `school_id` comes from the affected tenant or explicit Platform context, never
  from request input.
- Platform events may use `school_id = NULL`; tenant events require a school.
- Never log passwords, password confirmations, reset tokens, remember tokens,
  session payloads, secrets, or full sensitive request payloads.
- Audit only changed, approved model attributes and preserve old/new values.
- Existing Policies, Gates, Form Requests, tenant context, and service checks
  remain mandatory.
- An administrator may reset another authorized user's password, but not their
  own through User Management.
- Administrative password reset revokes the target user's sessions and remember
  token in the same transaction.
- The forgot-password endpoint returns the same user-facing response for known
  and unknown addresses and is rate limited.
- Preserve Laravel-native authentication and hashing.

## Required Workflow

1. Inspect current authentication, password, session, user, school, settings,
   migration, logging, and test patterns.
2. Define and apply the canonical Laravel password rule.
3. Block self-reset through User Management and revoke sessions after authorized
   administrative resets.
4. Make forgot-password responses generic and add route throttling.
5. Add documented activity/audit tables, immutable models, and a focused
   recording service.
6. Record current login/logout and implemented mutation workflows.
7. Add allowed, denied, privacy, immutability, tenant, CSRF, escaping, and query
   safety tests.
8. Run targeted tests, migrations from a clean database, full tests, Pint,
   route inspection, and dependency audits.
9. Update roadmap, security guidance, testing evidence, and changelog.
10. Rerun `prompts/00-governance/02-security-review.md`.

## Deliverables

- Consistent password security across all password-setting paths.
- Safe administrative password-reset and session-revocation behavior.
- Generic throttled forgot-password workflow.
- Append-only activity and audit recording foundation.
- Security regression suite and measured evidence.
- Updated security and phase documentation.
- Completed Phase 3 security-review result.

## Review Requirements

Verify:

- Weak passwords are rejected consistently.
- Current-password confirmation remains mandatory for self-service changes.
- User Management cannot be used for self-password reset.
- Administrative resets revoke only the target user's active sessions and
  remember token.
- Forgot-password responses do not disclose account existence and throttling is
  enforced.
- Logs capture actor, school, action/event, subject, timestamp, IP, user agent,
  and approved old/new values where applicable.
- Sensitive credential and token fields never enter logs.
- Log records cannot be updated or deleted through Eloquent.
- Cross-tenant and Platform log ownership is correct.
- CSRF protection, Blade escaping, and bound-query behavior have explicit tests.
- Existing authentication, authorization, tenant-isolation, file-storage,
  school, user, and settings tests remain green.

## Acceptance Criteria

The prompt is complete when:

- All high- and medium-risk findings from the Phase 3 security review are fixed.
- Required security regression tests pass.
- A clean migration run creates constraints matching the data dictionary.
- The complete application suite passes.
- Pint, route inspection, and dependency audits pass.
- Documentation identifies the future Activity/Audit screens separately from
  the implemented recording foundation.
- The security review reports no remaining critical, high, or medium finding
  for implemented modules.

## Stop Conditions

Stop and report instead of guessing if:

- The documented activity/audit schema cannot support required context.
- A remediation requires changing the canonical permission matrix.
- A security control requires a third-party package or alternative tenancy
  architecture.
- Sensitive data would need to be logged to satisfy a requirement.

## Required Final Response

Provide:

- Files modified.
- Security controls implemented.
- Activity/audit coverage and privacy controls.
- Test, migration, formatting, route, and dependency-audit results.
- Security-review score and decision.
- Exact next Phase 3 review gate.
