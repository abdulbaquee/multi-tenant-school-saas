# Cursor Rules Bridge

This repository uses `AGENTS.md` as the canonical AI governance system.

Cursor must treat this file as a compatibility bridge only.

## Required Cursor Behavior

Before editing, read:

1. `AGENTS.md`
2. The nearest nested `AGENTS.md` for the files being edited:
   - `app/AGENTS.md`
   - `database/AGENTS.md`
   - `tests/AGENTS.md`
   - `docs/AGENTS.md`
   - `resources/AGENTS.md`

## Precedence

If this bridge conflicts with any `AGENTS.md` file, `AGENTS.md` wins.

If project documents conflict, follow the precedence defined in `AGENTS.md`.

## Project Guardrails

Always preserve:

* Native Laravel Multi-Tenancy using `school_id`
* Tenant isolation through global scopes, `BelongsToTenant`, middleware, policies, and services
* Thin controllers
* Service layer business logic
* Form Request validation
* Policy-based authorization
* Blade templates and Bootstrap 5

Do not reintroduce duplicated Cursor-only governance rules.
