# MCA Final Report — ChatGPT Generation Prompts

Use these prompts in **ChatGPT Plus** after uploading the repository (or key
`docs/` files). ChatGPT cannot replace the official university DOCX template,
screenshots, or signatures — it drafts chapter text you paste into Word, then
export to PDF for Qollabb.

**Qollabb upload limits:** Report PDF max 20 MB · Presentation max 20 MB ·
Optional video max 20 MB.

---

## Workflow: ChatGPT → Word → PDF → Qollabb

1. Upload source files to ChatGPT (see file list below).
2. Run **Master Prompt** once so ChatGPT understands the project.
3. Run **one chapter prompt at a time**; save each chapter output to Word.
4. Paste into the official **MCA Project Report Template (DOCX)** from Chandigarh University.
5. Insert screenshots from `mca-screenshots/` and diagram exports manually.
6. Auto-generate Table of Contents, List of Figures, List of Tables in Word.
7. Write **Abstract last** (use Abstract prompt).
8. Fill preliminary pages: cover, bonafide, declaration (sign), acknowledgement.
9. Export **PDF** from Word (File → Export → PDF).
10. Verify PDF is under 20 MB and readable.
11. Upload to Qollabb **Final Report Submission** with GitHub + live demo URLs.

**Live demo URL:** `https://schoolportal.pagescorch.com`  
**GitHub URL:** `https://github.com/abdulbaquee/multi-tenant-school-saas`  
**Product brand:** School Portal

---

## Files to upload to ChatGPT (priority order)

Upload these from the repository before running prompts:

1. `docs/MCA_SUBMISSION_MASTER_PLAN.md`
2. `docs/MCA_REPORT_NOTES.md`
3. `docs/PROJECT_OVERVIEW.md`
4. `docs/PROJECT_CONSTITUTION.md`
5. `docs/MODULE_SPECIFICATIONS.md`
6. `docs/SYSTEM_ARCHITECTURE.md`
7. `docs/TENANCY_DESIGN.md`
8. `docs/DATABASE_DESIGN.md`
9. `docs/ER_DIAGRAM.md`
10. `docs/SCREEN_FLOW.md`
11. `docs/UI_UX_DESIGN_SYSTEM.md`
12. `docs/TESTING_STRATEGY.md`
13. `docs/DEPLOYMENT_GUIDE.md`
14. `docs/INSTALLATION_GUIDE.md`
15. `docs/DECISIONS_LOG.md`
16. `docs/CHANGELOG.md` (sections 1.2.x and current status)

Optional: upload `docs/PROJECT_GOVERNANCE.md` for phase evidence.

---

## MASTER PROMPT (run once first)

Copy everything between the lines:

---START MASTER PROMPT---

You are an academic technical writer helping prepare an MCA Major Project Report for Chandigarh University (Centre for Distance & Online Education), submitted through the Qollabb portal.

## Project identity (use exactly — do not invent)

- **Official project title:** Multi-Tenant School Administration Management SaaS Platform
- **Product / deployment brand:** School Portal
- **Program:** Master of Computer Applications (MCA)
- **University:** Chandigarh University
- **Assigned organization:** Regent Digitech Private Limited, Noida
- **Qollabb mentor:** Kashish Gupta
- **Student placeholders (I will fill manually):** [STUDENT NAME], [ENROLLMENT NUMBER], [PLACE], [DATE]
- **Technology stack:** Laravel 13, PHP 8.4, MySQL 8, Blade, Bootstrap 5, native Laravel multi-tenancy (school_id — no Stancl/Spatie packages)
- **Architecture:** Layered monolithic SaaS, single-database multi-tenant
- **Live demo:** https://schoolportal.pagescorch.com
- **GitHub:** https://github.com/abdulbaquee/multi-tenant-school-saas
- **Production server:** OVH VPS, Ubuntu 22.04, Nginx 1.18.0, PHP 8.4.21, MySQL 8.0.46
- **Test evidence:** 394 automated tests passing (PHPUnit feature + unit suites)
- **Roles:** Super Admin, School Admin, Teacher, Accountant (fixed RBAC)
- **Demo schools:** SHA, SHB, SHC (Springdale High A/B/C)
- **Submission deadline:** 2026-07-05

## Authority rules

1. Use uploaded repository documentation as the **only** source of truth for features, modules, database tables, security design, and test counts.
2. Do **not** invent modules, APIs, packages, or test results not evidenced in the docs.
3. Do **not** claim production payment gateway integration — only sandbox fee transaction workflow.
4. Do **not** claim parent portal, mobile app, SMS, or AI features as implemented.
5. School Admin **may** access System Operations for Activity Logs and Audit Trail (own school); Backup Management is Super Admin only.
6. Cite diagrams as placeholders: `[FIGURE 4.1: ER Diagram — insert from docs/ER_DIAGRAM.md]`.
7. Cite screenshots as placeholders: `[SCREENSHOT: School Admin Dashboard — insert image]`.
8. Write in formal academic English, third person, past tense for completed work.
9. Target **70–85 pages** total and **20,000–25,000 words** for main chapters (excluding references and appendices).
10. Use APA 7th edition for references in Chapter 9.
11. Minimum **20 references** (target 25): prefer IEEE papers, official Laravel/PHP/MySQL/OWASP docs, books — no Wikipedia as primary source.

## Report structure (Chandigarh University / Qollabb)

Preliminary pages: Cover, Bonafide Certificate, Declaration, Acknowledgement, Abstract (150–250 words, write last), TOC, List of Figures, List of Tables, List of Abbreviations.

Chapters:
1. Introduction (6 pages)
2. Literature Review / System Study (9 pages)
3. System Analysis (9 pages)
4. System Design (12 pages)
5. System Implementation (15 pages)
6. Testing & Validation (8 pages)
7. Results & Discussion (5 pages)
8. Conclusion & Future Enhancements (4 pages)
9. References (APA)
10. Appendices (installation guide, deployment guide, schema reference, GitHub link)

## Output format for each chapter

When I request a chapter, output:

- Chapter number and title
- Numbered sections and subsections (e.g., 3.1, 3.1.1)
- Full prose paragraphs (not bullet-only summaries)
- Tables where specified (markdown tables)
- Figure/table captions with numbering
- `[FIGURE X.Y: description]` and `[SCREENSHOT: description]` placeholders
- Word count at end of chapter
- List of figures/tables introduced in that chapter

Confirm you understand. Do not write any chapter yet — wait for my chapter-specific prompt.

---END MASTER PROMPT---

---

## CHAPTER PROMPTS (run one at a time after Master Prompt)

### Chapter 1 — Introduction

---START CHAPTER 1 PROMPT---

Write **Chapter 1 — Introduction** (~6 pages, ~1,800 words).

Sections: 1.1 Background, 1.2 Problem Statement, 1.3 Objectives, 1.4 Scope (in-scope and out-of-scope), 1.5 Existing System, 1.6 Proposed System (School Portal), 1.7 Technology Overview.

Sources: PROJECT_OVERVIEW.md, PROJECT_CONSTITUTION.md, MCA_SUBMISSION_MASTER_PLAN.md.

Objectives must match the 8–9 objectives in PROJECT_OVERVIEW. Explicitly state out-of-scope items: parent/student portals, mobile app, production payment gateway, SMS, custom roles.

End with word count and figure/table list (if any).

---END CHAPTER 1 PROMPT---

### Chapter 2 — Literature Review

---START CHAPTER 2 PROMPT---

Write **Chapter 2 — Literature Review / System Study** (~9 pages, ~2,700 words).

Sections: 2.1 School Management Systems, 2.2 SaaS and Multi-Tenant Architectures, 2.3 Web Frameworks (Laravel), 2.4 Database Systems (MySQL 8), 2.5 Web Application Security, 2.6 Software Development Life Cycle, 2.7 Comparative Analysis, 2.8 Research Gap.

Include:
- **Table 2.1:** Comparison of Traditional Manual System vs Standalone School Software vs Proposed Multi-Tenant SaaS
- **Table 2.2:** Multi-tenancy models comparison (single DB with tenant key vs separate DB per tenant)
- At least 8 in-text APA citations (use real citable sources: Laravel docs, MySQL docs, OWASP, SaaS/multi-tenant academic papers — mark uncertain citations as `[VERIFY APA]`)

Do not copy Wikipedia paragraphs. End with word count and draft reference list for sources cited in this chapter.

---END CHAPTER 2 PROMPT---

### Chapter 3 — System Analysis

---START CHAPTER 3 PROMPT---

Write **Chapter 3 — System Analysis** (~9 pages, ~2,700 words).

Sections: 3.1 Functional Requirements (all 15 modules from MODULE_SPECIFICATIONS.md), 3.2 Non-Functional Requirements (security, performance, scalability, maintainability, usability), 3.3 User Requirements by role (Super Admin, School Admin, Teacher, Accountant — use a table), 3.4 Feasibility Study (technical, economic, operational), 3.5 System Architecture Overview, 3.6 Data Flow Diagram Level 0, 3.7 Data Flow Diagram Level 1, 3.8 Use Case Overview.

For DFD and use case sections: describe the diagram in text and add placeholders:
- `[FIGURE 3.1: DFD Level 0]`
- `[FIGURE 3.2: DFD Level 1]`
- `[FIGURE 3.3: Use Case Diagram]`

Base functional requirements on MODULE_SPECIFICATIONS.md and SYSTEM_ARCHITECTURE.md only.

---END CHAPTER 3 PROMPT---

### Chapter 4 — System Design

---START CHAPTER 4 PROMPT---

Write **Chapter 4 — System Design** (~12 pages, ~3,600 words).

Sections: 4.1 System Architecture, 4.2 ER Diagram and Database Overview (28 tables), 4.3 Key Table Structures (users, schools, students, student_enrollments, attendances, student_fees, exams, exam_results — describe columns at high level), 4.4 Multi-Tenancy Design (school_id, TenantContext, global scopes, policies), 4.5 RBAC Design (permission matrix summary table), 4.6 Module Design Summary, 4.7 UI and Screen Flow, 4.8 UML Design (class, sequence, activity).

Include placeholders:
- `[FIGURE 4.1: System Architecture Diagram]`
- `[FIGURE 4.2: ER Diagram]`
- `[FIGURE 4.3: Class Diagram]`
- `[FIGURE 4.4: Sequence Diagram — Authentication and Tenant Context]`
- `[FIGURE 4.5: Activity Diagram — Fee Collection or Attendance Entry]`

Sources: DATABASE_DESIGN.md, ER_DIAGRAM.md, TENANCY_DESIGN.md, UI_UX_DESIGN_SYSTEM.md, SCREEN_FLOW.md.

Include **Table 4.1:** Role-Permission summary (condensed from MODULE_SPECIFICATIONS).

---END CHAPTER 4 PROMPT---

### Chapter 5 — System Implementation

---START CHAPTER 5 PROMPT---

Write **Chapter 5 — System Implementation** (~15 pages, ~4,500 words).

Sections:
5.1 Development Environment and Tools
5.2 Project Directory Structure (brief — no full tree dump)
5.3 Authentication and Profile Management
5.4 School Management and School Settings
5.5 User Management and RBAC
5.6 Academic Structure Module
5.7 Student Management Module
5.8 Attendance Management Module
5.9 Fee Management Module (include sandbox transaction — not production gateway)
5.10 Examination and Report Card Module
5.11 Reporting, Analytics, and System Operations
5.12 Security Implementation (CSRF, validation, policies, private file storage)
5.13 Version Control with Git and GitHub
5.14 Production Deployment (OVH VPS, schoolportal.pagescorch.com)

For each module section (5.3–5.11): 2–3 paragraphs on workflow + 1 `[SCREENSHOT: ...]` placeholder.

Include exactly **3 short code snippets** (max 15 lines each) from the architecture:
1. Tenant scope or TenantContext usage
2. Policy or Gate authorization example
3. Service-layer workflow example

Label as Figure 5.x. Sources: CHANGELOG.md, DECISIONS_LOG.md, DEPLOYMENT_GUIDE.md, implementation docs.

---END CHAPTER 5 PROMPT---

### Chapter 6 — Testing & Validation

---START CHAPTER 6 PROMPT---

Write **Chapter 6 — Testing & Validation** (~8 pages, ~2,400 words).

Sections: 6.1 Testing Strategy, 6.2 Test Environment, 6.3 Automated Testing (394 tests), 6.4 Feature and Integration Testing, 6.5 Security Testing, 6.6 Tenant Isolation Testing, 6.7 Manual Smoke Testing (four roles + two-browser SHA/SHB test), 6.8 Test Results Summary.

Include:
- **Table 6.1:** Test types and tools (PHPUnit, SQLite :memory:, artisan test)
- **Table 6.2:** Test case table with at least 15 rows — columns: Test ID, Module, Input/Precondition, Expected Output, Actual Output, Status (use Pass for documented outcomes)
- `[SCREENSHOT: php artisan test — 394 passed]`
- `[SCREENSHOT: Tenant isolation — SHA vs SHB side by side]`

Source: TESTING_STRATEGY.md, PROJECT_GOVERNANCE.md phase gate evidence.

---END CHAPTER 6 PROMPT---

### Chapter 7 — Results & Discussion

---START CHAPTER 7 PROMPT---

Write **Chapter 7 — Results & Discussion** (~5 pages, ~1,500 words).

Sections: 7.1 System Outputs Overview, 7.2 Dashboard and Analytics Results, 7.3 Module Operation Results, 7.4 Multi-Tenant Isolation Results, 7.5 Cloud Deployment Results, 7.6 Benefits vs Traditional Process, 7.7 Limitations.

Include **Table 7.1:** Traditional Process vs School Portal comparison.

Include **Table 7.2:** Production deployment stack (OVH, Ubuntu 22.04, Nginx, PHP, MySQL, HTTPS URL).

Reference live URL and smoke-test evidence. Add 6–8 `[SCREENSHOT: ...]` placeholders mapped to modules.

---END CHAPTER 7 PROMPT---

### Chapter 8 — Conclusion

---START CHAPTER 8 PROMPT---

Write **Chapter 8 — Conclusion & Future Enhancements** (~4 pages, ~1,200 words).

Sections: 8.1 Summary, 8.2 Objective Achievement (map each Chapter 1 objective to evidence), 8.3 Technical Learning Outcomes, 8.4 Business/Operational Benefits, 8.5 Limitations, 8.6 Future Enhancements (parent portal, student portal, mobile app, production payment gateway, SMS/email notifications, advanced analytics — as future work only).

Be honest about MCA scope boundaries. End with word count.

---END CHAPTER 8 PROMPT---

### Chapter 9 — References

---START CHAPTER 9 PROMPT---

Compile **Chapter 9 — References** in APA 7th edition format.

Merge all `[VERIFY APA]` and in-text citations from Chapters 2–8 into one consolidated list of **at least 25 references**.

Categories to include:
- Laravel 13 documentation
- PHP 8.4 documentation
- MySQL 8 reference manual
- Bootstrap 5 documentation
- OWASP security guidance
- Multi-tenant SaaS architecture sources (academic or IEEE)
- School ERP / education management systems sources
- Software engineering / SDLC textbooks or papers

Mark any reference you cannot verify with `[VERIFY BEFORE SUBMIT]`.

---END CHAPTER 9 PROMPT---

### Chapter 10 — Appendices

---START CHAPTER 10 PROMPT---

Write **Chapter 10 — Appendices** outline and content summaries (not full code dump).

- **Appendix A:** GitHub repository reference and folder structure summary
- **Appendix B:** Installation Guide (summarize from INSTALLATION_GUIDE.md)
- **Appendix C:** Deployment Guide (summarize from DEPLOYMENT_GUIDE.md v1.1 frozen record)
- **Appendix D:** Database schema overview (28 tables grouped by domain — from DATABASE_DESIGN.md)
- **Appendix E:** User manual quick-start by role (1 page per role)
- **Appendix F:** Selected source code listing (reference only — TenantContext, ReportPolicy, or BelongsToTenant — max 2 files, abbreviated)

Keep appendix prose concise; full guides are referenced from repo docs.

---END CHAPTER 10 PROMPT---

### Abstract (run last)

---START ABSTRACT PROMPT---

Write the **Abstract** only (150–250 words, single paragraph).

Must include: project title, objective, Laravel 13 / PHP 8.4 / MySQL 8 stack, native multi-tenancy with school_id, RBAC, core modules implemented, 394 tests, deployment at https://schoolportal.pagescorch.com, academic and practical significance.

Do not exceed 250 words. Count words at end.

---END ABSTRACT PROMPT---

### Acknowledgement

---START ACKNOWLEDGEMENT PROMPT---

Write a formal **Acknowledgement** (~200 words) thanking:
- Qollabb mentor Kashish Gupta
- Chandigarh University faculty
- Regent Digitech Private Limited, Noida
- Family and friends

Use placeholder [STUDENT NAME]. Formal academic tone.

---END ACKNOWLEDGEMENT PROMPT---

---

## PRESENTATION PROMPT (separate ChatGPT chat or after report)

---START PRESENTATION PROMPT---

Create a **12–15 slide** MCA project presentation outline for Qollabb (max 20 MB PDF/PPT).

Slides:
1. Title (project, student placeholders, university, mentor, organization)
2. Problem statement
3. Objectives and scope
4. Existing vs proposed system
5. Architecture and native multi-tenancy
6. Database / ER overview
7. Roles and RBAC
8. Core modules (academic, students, attendance)
9. Fees, examinations, report cards
10. Reports, analytics, logs, backups
11. Security and tenant isolation
12. Testing (394 tests) and manual QA
13. Cloud deployment — schoolportal.pagescorch.com
14. Achievements, limitations, future work
15. Conclusion and thank you

For each slide: title, 4–6 bullet points, speaker notes (2–3 sentences), and `[SCREENSHOT]` placeholder where needed.

Output as slide-by-slide text I can paste into PowerPoint or Google Slides.

---END PRESENTATION PROMPT---

---

## Qollabb Final Submission — link field text

Paste into **Link of Your Work**:

```
GitHub Repository: https://github.com/abdulbaquee/multi-tenant-school-saas
Live Demo (School Portal): https://schoolportal.pagescorch.com
```

---

## After ChatGPT generates text — your manual steps

1. **Verify** every fact against repository docs (test count, modules, URL).
2. **Replace** all `[FIGURE]` and `[SCREENSHOT]` placeholders with real images.
3. **Draw** missing diagrams (DFD, UML) in draw.io using docs as reference.
4. **Run** plagiarism check if required by mentor.
5. **Get** bonafide certificate and declaration signed.
6. **Export PDF** — Word: File → Save As → PDF, or Print → Save as PDF.
7. **Check** file size under 20 MB (compress images if needed).
8. **Upload** to Qollabb; verify preview; click Submit.

---

## Common ChatGPT mistakes to fix manually

- Inventing student enrollment number or guide name — use your real details only
- Claiming React/Vue/Livewire/Tailwind — project uses Blade + Bootstrap 5 only
- Claiming Stancl Tenancy — project uses native school_id tenancy
- Wrong test count — must be **394 tests**
- Saying School Admin cannot see System Operations — they can (Activity + Audit only)
- Claiming live Razorpay/Stripe — only sandbox fee workflow exists
