# TECHNOLOGY GOVERNANCE RULES

Version: 1.0

Project:
Multi-Tenant School Administration Management SaaS Platform

Purpose:

Prevent outdated technology recommendations, hallucinated versions, deprecated practices, and inconsistent architecture decisions.

---

# SINGLE SOURCE OF TRUTH

Before suggesting any technology, architecture change, package, framework, version, or implementation approach:

Always verify against:

* PROJECT_CONSTITUTION.md
* DECISIONS_LOG.md
* SYSTEM_ARCHITECTURE.md
* PROJECT_OVERVIEW.md

These documents are authoritative.

---

# NO ASSUMPTION RULE

Never assume:

* Framework versions
* PHP versions
* Package versions
* Library versions
* Cursor model availability
* Hosting environments

If not explicitly documented:

State uncertainty.

Recommend verification.

Do not invent versions.

---

# TECHNOLOGY STACK AUTHORITY

Current approved stack:

Backend:

* Laravel 13
* PHP 8.4

Frontend:

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

Database:

* MySQL 8

Authentication:

* Laravel Breeze

Multi-Tenancy:

* Stancl Tenancy

Any recommendation conflicting with this stack requires explicit justification.

---

# LATEST VERSION RULE

When recommending:

* Packages
* Frameworks
* Libraries
* Tools
* IDE extensions
* Models

Prefer:

* Current stable release
* Official documentation
* Long-term support versions

Avoid:

* Deprecated packages
* Abandoned projects
* Legacy recommendations

---

# HALLUCINATION PREVENTION RULE

Never:

* Invent package versions
* Invent framework versions
* Invent release dates
* Invent model names
* Invent feature availability

If verification is required:

Say so explicitly.

---

# CURSOR MODEL SELECTION RULE

Do not recommend specific model names unless known to be currently available.

Instead:

* Prefer highest-capability reasoning model available for architecture reviews.
* Prefer strongest coding model available for implementation tasks.
* Prefer fastest quality model for routine edits.

Model recommendations must be treated as time-sensitive.

---

# DOCUMENT REVIEW RULE

When auditing documentation:

Check for:

* Outdated versions
* Contradictory architecture
* Deprecated packages
* Inconsistent terminology
* Invalid assumptions

Flag issues instead of silently accepting them.

---

# ARCHITECTURE CONSISTENCY RULE

Every recommendation must remain consistent with:

* Multi-Tenant SaaS architecture
* school_id tenant isolation
* Laravel 13
* PHP 8.4
* Bootstrap 5
* MySQL 8

Do not introduce architectural drift.

---

# FINAL RULE

Accuracy is more important than confidence.

If something cannot be verified:

State uncertainty.

Never fabricate technical facts.
