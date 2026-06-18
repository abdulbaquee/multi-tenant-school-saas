# Cursor Technology Governance Bridge

Technology governance is now defined by `AGENTS.md` and the authoritative documents it references.

This file exists only so Cursor can discover the governance model.

## Current Approved Direction

Follow `AGENTS.md` for the complete hierarchy.

Key constraints:

* Laravel 13
* PHP 8.4
* MySQL 8
* Blade templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js
* Laravel Breeze
* Native Laravel Multi-Tenancy using `school_id`

## Do Not Introduce

Do not introduce Stancl Tenancy, Spatie Multitenancy, microservices, CQRS, Event Sourcing, React, Vue, Inertia, Livewire, Tailwind CSS, or Alpine.js unless an approved decision is recorded in `docs/DECISIONS_LOG.md`.

If a technology recommendation conflicts with `AGENTS.md`, `AGENTS.md` wins.
