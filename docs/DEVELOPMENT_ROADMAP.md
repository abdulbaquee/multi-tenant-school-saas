# DEVELOPMENT ROADMAP

Version: 1.0
Status: Draft

> This document is the **canonical phase sequence** for the project. All other
> documents (PROJECT_GOVERNANCE.md, MODULE_SPECIFICATIONS.md, CHANGELOG.md) must
> follow this phase order. If any document conflicts on phase ordering, this
> document wins.

## Project Information

Project Title:

Multi-Tenant School Administration Management SaaS Platform

Project Type:

MCA Major Project

Technology Stack:

* Laravel 13 (Installed)
* PHP 8.4
* MySQL 8
* Bootstrap 5 (Installed)
* Laravel Breeze (Installed)
* Native Laravel Multi-Tenancy (school_id + Global Scopes)
* Chart.js (Planned)
* Git & GitHub

Architecture:

Single Database Multi-Tenant SaaS

---

# Project Status

Current Phase:

Phase 7 — Attendance Readiness Review

Progress:

Phase 5 Academic Structure and Phase 6 Student Management are completed and
release-approved. The initial Phase 7 readiness review scored 5/10 and required
documentation remediation. The Attendance operational boundary, tenant and
Teacher scope, Enrollment-derived roster, bulk and correction rules, retention,
privacy, and testing requirements are now documented. The Phase 7 readiness
review must be rerun before migrations or models are created.

Repository:

Initialized

Laravel Installation:

Completed

Documentation Setup:

Completed

---

# Phase 0 — Planning & Governance

Status: Completed

## Deliverables

* Project Scope
* Technology Stack Selection
* Repository Setup
* Documentation Structure
* Governance Documentation
* Development Roadmap
* Testing Strategy

---

# Phase 1 — Architecture & Database Design

Status: Completed

## Objectives

Design the foundation of the application before development begins.

## Deliverables

* Database Design Document
* Entity Relationship Diagram (ERD)
* System Architecture Diagram
* Module Relationships
* Tenant Isolation Design

## Expected Output

A complete database blueprint for application development.

---

# Phase 2 — Authentication & User Management

Status: Completed

## Features

* Laravel Breeze Setup: Completed
* Login: Completed
* Logout: Completed
* Password Reset: Completed
* User Profile Foundation: Completed
* User Management Foundation: Completed
* Dashboard and Navigation Foundation: Completed

## Deliverables

* Authentication Foundation: Completed
* User Management Foundation: Completed
* Authenticated Application Shell: Completed
* Phase 2 Automated Feature Tests: Completed

---

# Phase 3 — Multi-Tenant Foundation

Status: Completed

## Features

* School Registration: Completed
* School Management: Completed
* School Settings: Completed
* Tenant Middleware: Core lifecycle completed
* Tenant Context Resolution: Completed
* Tenant Data Isolation: Core scope and trait foundation completed; future modules adopt the pattern during their phases
* Security Remediation: Completed
* Activity and Audit Recording Foundation: Completed for implemented workflows

## Deliverables

* Schools Module
* School Settings Foundation
* Multi-Tenant Infrastructure

---

# Phase 4 — Roles & Permissions

Status: Completed

## Features

* Fixed Role Directory: Completed
* Effective Permission Views: Completed
* Constrained School-Role Mapping Management: Completed
* Authorization Policies: Completed for current modules
* Access Matrix: Canonical configuration and seed synchronization implemented
* Mapping Activity and Audit Evidence: Completed

## Roles

* Super Admin
* School Admin
* Teacher
* Accountant

## Deliverables

* RBAC Module

---

# Phase 5 — Academic Structure

Status: Completed — Release Approved

## Features

* Academic Years
* Academic Terms
* Minimal Teacher Profiles
* Classes
* Sections
* Subjects

Core schema, tenant-aware models, relationships, and Teacher Profile user
identity safeguards: Completed.

Academic Year and Academic Term directories, forms, lifecycle services,
Policies, tenant-safe routes, UI, activity/audit evidence, and tests: Completed.

Teacher Profile identity linking, profile fields, lifecycle services, retained
history, tenant-safe UI, activity/audit evidence, and tests: Completed.

Class directory, forms, role-aware assigned-Teacher reads, dependency-safe
lifecycle services, tenant-safe routes, UI, activity/audit evidence, and tests:
Completed.

Section and Subject directories, forms, optional Teacher assignments, retained
lifecycle services, role-aware Teacher reads, tenant-safe routes, UI,
activity/audit evidence, and tests: Completed.

Academic Structure exports are deferred to the reporting phase and do not block
the Phase 5 implementation gate.

Student registration and Student Enrollment remain Phase 6.

## Deliverables

* Academic Structure Module

---

# Phase 6 — Student Management

Status: Completed — Release Approved

## Features

* Student Registration
* Student Profiles
* Student Enrollment
* Student Search
* Private Student Photos

Student Reports and exports are deferred to Phase 10 Reporting.

The Student and Enrollment migration, tenant-aware models, inverse
relationships, immutable identity safeguards, and focused schema/isolation
tests are completed.

Student registration, own-tenant School Admin management, assigned-record
Teacher reads, school-selected Super Admin reads, lifecycle controls, private
photos, privacy-safe logs, and read-only Enrollment history are completed.

Initial Enrollment creation, immutable placement, retained completion,
transaction-coupled Student transfer, transaction-coupled graduation,
privacy-safe logging, and denied-role/tenant enforcement are completed.

## Deliverables

* Student Management Module

---

# Phase 7 — Attendance Management

Status: Design Remediated — Readiness Rerun Pending

## Features

* Daily and Bulk Attendance Entry
* Attendance History and Search
* Authorized Attendance Corrections
* Monthly Operational Summaries
* Attendance Reports, Exports, and Analytics Deferred to Phase 10

## Deliverables

* Attendance Module

---

# Phase 8 — Fee Management

Status: Pending

## Features

* Fee Categories
* Fee Structures
* Fee Collection
* Receipts
* Financial Reports

## Deliverables

* Fee Management Module

---

# Phase 9 — Examination Management

Status: Pending

## Features

* Examination Setup
* Marks Entry
* Grade Calculation
* Result Processing
* Report Cards

## Deliverables

* Examination Module

---

# Phase 10 — Reports, Analytics & System Operations

Status: Pending

## Features

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Dashboard Analytics
* Activity Log Screens
* Audit Trail Screens
* Backup Management and Private Backup History

## Deliverables

* Reporting System
* Analytics Dashboard
* Activity and Audit Review Screens
* Backup Management Module

---

# Phase 11 — Testing & Quality Assurance

Status: Pending

## Testing Types

* Unit Testing
* Feature Testing
* Integration Testing
* Security Testing
* Tenant Isolation Testing

## Deliverables

* Test Cases
* Test Reports

---

# Phase 12 — Deployment & Documentation

Status: Pending

## Deliverables

* Deployment Guide
* Installation Guide
* User Manual
* Screenshots
* Database Documentation

---

# Phase 13 — MCA Final Submission

Status: Pending

## Deliverables

* Source Code
* Project Report
* PPT Presentation
* Database Schema
* Screenshots Package
* Viva Preparation Notes

## Expected Outcome

Project ready for final evaluation and submission.

---

# Success Criteria

* Fully functional multi-tenant SaaS application
* Secure tenant isolation
* Role-based access control
* Academic workflow management
* Comprehensive reporting
* Complete MCA documentation
* Successful deployment

---

# Next Immediate Task

Rerun the Phase 7 Attendance Management readiness-review prompt. Do not create
Attendance migrations or models until the review approves implementation.
