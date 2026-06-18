# DECISIONS LOG

Version: 2.0

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Framework:
Laravel 13

Language:
PHP 8.4

Database:
MySQL 8

Purpose:

This document records major architectural, technical, security, and design decisions made during the project lifecycle.

The goal is to provide traceability, justification, and historical context for all important decisions.

---

# DECISION-001

Date:
2026-06-18

Title:
Use Laravel 13 As Primary Framework

Status:
Approved

Decision:

The application will be developed using Laravel 13.

Reason:

* Latest stable Laravel release
* Modern architecture
* Strong ecosystem
* Built-in authentication support
* Excellent documentation
* Industry adoption

Alternatives Considered:

* CodeIgniter 4
* Symfony
* Slim Framework

Outcome:

Laravel 13 selected.

---

# DECISION-002

Date:
2026-06-18

Title:
Use PHP 8.4

Status:
Approved

Decision:

The application will use PHP 8.4.

Reason:

* Improved performance
* Modern language features
* Long-term maintainability
* Better type safety

Alternatives Considered:

* PHP 8.2
* PHP 8.3

Outcome:

PHP 8.4 selected.

---

# DECISION-003

Date:
2026-06-18

Title:
Use MySQL 8 As Database

Status:
Approved

Decision:

The application database will be MySQL 8.

Reason:

* Widely adopted
* Easy deployment
* Excellent Laravel support
* Strong documentation
* Suitable for MCA project scope

Alternatives Considered:

* PostgreSQL
* MariaDB
* SQL Server

Outcome:

MySQL 8 selected.

---

# DECISION-004

Date:
2026-06-18

Title:
Adopt Single Database Multi-Tenant Architecture

Status:
Approved

Decision:

All schools will share one database and one schema.

Tenant isolation will be implemented using:

school_id

Reason:

* Simpler implementation
* Easier maintenance
* Lower infrastructure complexity
* Suitable for MCA project scope

Alternatives Considered:

* Database Per Tenant
* Schema Per Tenant

Outcome:

Single Database Multi-Tenant Architecture selected.

---

# DECISION-005

Date:
2026-06-18

Title:
Use Stancl Tenancy

Status:
Approved

Decision:

Stancl Tenancy will be used as the multi-tenancy foundation.

Reason:

* Laravel-focused
* Well documented
* Production-proven
* Simplifies tenant management

Alternatives Considered:

* Custom Tenant Logic
* Spatie Multitenancy

Outcome:

Stancl Tenancy selected.

---

# DECISION-006

Date:
2026-06-18

Title:
Use Laravel Breeze For Authentication

Status:
Approved

Decision:

Authentication will be implemented using Laravel Breeze.

Reason:

* Lightweight
* Official Laravel package
* Easy customization
* Suitable for educational projects

Alternatives Considered:

* Laravel Jetstream
* Laravel Fortify
* Custom Authentication

Outcome:

Laravel Breeze selected.

---

# DECISION-007

Date:
2026-06-18

Title:
Use Bootstrap 5 As UI Framework

Status:
Approved

Decision:

Bootstrap 5 will be the only UI framework.

Reason:

* Fast development
* Responsive by default
* Consistent components
* Easy maintenance

Alternatives Considered:

* Tailwind CSS
* Material UI
* Premium Admin Templates

Outcome:

Bootstrap 5 selected.

---

# DECISION-008

Date:
2026-06-18

Title:
Adopt Layered Monolithic Architecture

Status:
Approved

Decision:

The application will follow a layered monolithic architecture.

Layers:

* Presentation Layer
* Controller Layer
* Service Layer
* Model Layer
* Database Layer

Reason:

* Easier development
* Suitable for MCA scope
* Simpler deployment
* Easier debugging

Alternatives Considered:

* Microservices
* Modular Monolith

Outcome:

Layered Monolith selected.

---

# DECISION-009

Date:
2026-06-18

Title:
Use Service Layer Pattern

Status:
Approved

Decision:

Business logic will reside in services.

Reason:

* Thin controllers
* Better maintainability
* Easier testing
* Cleaner architecture

Outcome:

Service Layer Architecture adopted.

---

# DECISION-010

Date:
2026-06-18

Title:
Use Role-Based Access Control

Status:
Approved

Decision:

Access control will be role based.

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Reason:

* Easy to understand
* Easy to implement
* Matches school workflows

Outcome:

RBAC selected.

---

# DECISION-011

Date:
2026-06-18

Title:
Use Academic Structure Module

Status:
Approved

Decision:

Academic Years, Terms, Classes, Sections, and Subjects will be grouped under a single Academic Structure module.

Reason:

* Cleaner navigation
* Better organization
* Easier maintenance

Outcome:

Academic Structure Module created.

---

# DECISION-012

Date:
2026-06-18

Title:
Implement Activity Logging

Status:
Approved

Decision:

All critical business actions will be logged.

Examples:

* Login
* Logout
* Attendance Entry
* Fee Collection
* Marks Entry

Reason:

* Auditability
* Accountability
* Reporting

Outcome:

Activity Logging mandatory.

---

# DECISION-013

Date:
2026-06-18

Title:
Implement Audit Trail

Status:
Approved

Decision:

Data modifications will be recorded.

Tracked Information:

* Old Values
* New Values
* User
* Timestamp

Reason:

* Accountability
* Traceability
* Security

Outcome:

Audit Trail mandatory.

---

# DECISION-014

Date:
2026-06-18

Title:
Use Soft Deletes

Status:
Approved

Decision:

Business entities will use Soft Deletes.

Applies To:

* Students
* Teachers
* Users
* Classes
* Subjects
* Exams

Reason:

* Prevent accidental data loss
* Improve recovery capability

Outcome:

Soft Deletes enabled.

---

# DECISION-015

Date:
2026-06-18

Title:
No Parent Portal In MVP

Status:
Approved

Decision:

Parent Portal excluded from initial release.

Reason:

* Reduce project complexity
* Focus on core academic workflows
* Meet MCA timeline

Outcome:

Deferred to future enhancement.

---

# DECISION-016

Date:
2026-06-18

Title:
No Student Portal In MVP

Status:
Approved

Decision:

Student Portal excluded from initial release.

Reason:

* Reduce scope
* Prioritize administration modules

Outcome:

Deferred to future enhancement.

---

# DECISION-017

Date:
2026-06-18

Title:
Dark Mode Not Included

Status:
Approved

Decision:

Dark Mode excluded from MVP.

Reason:

* Additional UI complexity
* Increased testing effort
* Not required for evaluation

Outcome:

Future enhancement.

---

# DECISION-018

Date:
2026-06-19

Title:
Use Chart.js For Analytics

Status:
Approved

Decision:

Analytics visualizations will use Chart.js.

Reason:

* Lightweight
* Open source
* Easy Bootstrap integration

Alternatives Considered:

* ApexCharts
* Highcharts

Outcome:

Chart.js selected.

---

# DECISION-019

Date:
2026-06-19

Title:
Tenant Isolation Is Highest Priority Security Requirement

Status:
Approved

Decision:

All business data must be filtered using:

school_id

Reason:

* Core SaaS requirement
* Prevent cross-school access
* Maintain confidentiality

Outcome:

Mandatory validation in all modules.

---

# DECISION-020

Date:
2026-06-19

Title:
Documentation First Development Approach

Status:
Approved

Decision:

Complete architecture and design documentation before development.

Reason:

* Reduce rework
* Improve planning
* Improve MCA report quality
* Simplify implementation

Outcome:

Documentation phase completed before coding.

---

# DECISION CHANGE PROCESS

New decisions must include:

* Decision ID
* Date
* Title
* Status
* Decision
* Reason
* Alternatives Considered
* Outcome

No major architectural decision should be implemented without updating this document.

---

# CURRENT PROJECT STATUS

Architecture Decisions:
Completed

Security Decisions:
Completed

Technology Decisions:
Completed

Database Decisions:
Completed

UI/UX Decisions:
Completed

Development Decisions:
Completed

Project Ready For:
Implementation Phase
