#!/usr/bin/env python3
"""Build MCA final project presentation (PPTX + PDF) for Qollabb upload."""

from __future__ import annotations

import io
import textwrap
from pathlib import Path

from PIL import Image
from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.util import Inches, Pt
from reportlab.lib import colors
from reportlab.lib.pagesizes import landscape
from reportlab.lib.units import inch
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas

BASE = Path(__file__).resolve().parent
REPO = BASE.parent.parent
SCREENSHOTS = REPO / "docs" / "mca-screenshots"
DIAGRAMS = REPO / "docs" / "mca-diagrams"

PROJECT_TITLE = "Multi-Tenant School Administration\nManagement SaaS Platform"
PROJECT_SUBTITLE = "School Portal"
STUDENT_NAME = "Mohammed Abdul Baquee"
ENROLLMENT_NO = "O24MCA110215"
MENTOR = "Ms. Kashish Gupta"
UNIVERSITY = "Chandigarh University"
ORGANIZATION = "Regent Digitech Private Limited, Noida"
ACADEMIC_YEAR = "2025–26"
LIVE_URL = "https://schoolportal.pagescorch.com"
GITHUB_URL = "https://github.com/abdulbaquee/multi-tenant-school-saas"

OUT_PPTX = BASE / "MCA-Final-Presentation.pptx"
OUT_PDF = BASE / "MCA-Final-Presentation.pdf"

# 16:9 widescreen
SLIDE_W = Inches(13.333)
SLIDE_H = Inches(7.5)

NAVY = RGBColor(0x1E, 0x3A, 0x5F)
BLUE = RGBColor(0x25, 0x63, 0xEB)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
DARK = RGBColor(0x1F, 0x29, 0x37)
MUTED = RGBColor(0x64, 0x74, 0x8B)

PDF_NAVY = colors.HexColor("#1E3A5F")
PDF_BLUE = colors.HexColor("#2563EB")
PDF_DARK = colors.HexColor("#1F2937")
PDF_MUTED = colors.HexColor("#64748B")
PDF_LIGHT = colors.HexColor("#F8FAFC")

# PDF page size (16:9 inches)
PDF_W, PDF_H = landscape((10 * inch, 5.625 * inch))


def compress_image(path: Path, max_width: int = 1600) -> io.BytesIO:
    img = Image.open(path)
    if img.mode in ("RGBA", "P"):
        img = img.convert("RGB")
    if img.width > max_width:
        ratio = max_width / img.width
        img = img.resize((max_width, int(img.height * ratio)), Image.Resampling.LANCZOS)
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=82, optimize=True)
    buf.seek(0)
    return buf


def set_slide_bg(slide, rgb: RGBColor) -> None:
    fill = slide.background.fill
    fill.solid()
    fill.fore_color.rgb = rgb


def add_header_bar(slide, title: str) -> None:
    bar = slide.shapes.add_shape(1, Inches(0), Inches(0), SLIDE_W, Inches(1.05))
    bar.fill.solid()
    bar.fill.fore_color.rgb = NAVY
    bar.line.fill.background()
    tf = bar.text_frame
    tf.text = title
    p = tf.paragraphs[0]
    p.font.size = Pt(28)
    p.font.bold = True
    p.font.color.rgb = WHITE
    p.alignment = PP_ALIGN.LEFT
    tf.margin_left = Inches(0.45)
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE


def add_bullets(slide, bullets: list[str], left=0.55, top=1.35, width=12.2, height=5.8, size=20):
    box = slide.shapes.add_textbox(Inches(left), Inches(top), Inches(width), Inches(height))
    tf = box.text_frame
    tf.word_wrap = True
    for i, bullet in enumerate(bullets):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.text = bullet
        p.level = 0
        p.font.size = Pt(size)
        p.font.color.rgb = DARK
        p.space_after = Pt(8)
        p.bullet = True


def add_image(slide, path: Path | None, left, top, width, height):
    if path and path.exists():
        slide.shapes.add_picture(str(path), Inches(left), Inches(top), width=Inches(width))


def add_notes(slide, notes: str) -> None:
    slide.notes_slide.notes_text_frame.text = notes


def title_slide(prs: Presentation) -> None:
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    set_slide_bg(slide, NAVY)

    box = slide.shapes.add_textbox(Inches(0.8), Inches(1.2), Inches(11.5), Inches(5.5))
    tf = box.text_frame
    tf.word_wrap = True
    lines = [
        (PROJECT_TITLE, 34, True, WHITE),
        (PROJECT_SUBTITLE, 26, False, RGBColor(0xBF, 0xDB, 0xFE)),
        ("", 12, False, WHITE),
        (f"Submitted by: {STUDENT_NAME}", 20, False, WHITE),
        (f"Enrollment No.: {ENROLLMENT_NO}", 18, False, RGBColor(0xE2, 0xE8, 0xF0)),
        ("", 10, False, WHITE),
        (UNIVERSITY, 20, True, WHITE),
        (f"Project Mentor: {MENTOR}", 18, False, RGBColor(0xE2, 0xE8, 0xF0)),
        (ORGANIZATION, 18, False, RGBColor(0xE2, 0xE8, 0xF0)),
        (f"Academic Year: {ACADEMIC_YEAR}", 16, False, RGBColor(0xCB, 0xD5, 0xE1)),
        ("MCA Major Project — Qollabb Submission", 16, False, RGBColor(0xCB, 0xD5, 0xE1)),
    ]
    for i, (text, size, bold, color) in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.text = text
        p.font.size = Pt(size)
        p.font.bold = bold
        p.font.color.rgb = color
        p.space_after = Pt(6)

    add_notes(
        slide,
        "Introduce yourself, the project title School Portal, university, mentor, and organization. "
        "Mention this is a cloud-based multi-tenant school administration SaaS built with Laravel 13.",
    )


def content_slide(
    prs: Presentation,
    title: str,
    bullets: list[str],
    image: Path | None = None,
    image_width: float = 5.6,
    notes: str = "",
    bullet_size: int = 19,
):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    set_slide_bg(slide, WHITE)
    add_header_bar(slide, title)

    if image and image.exists():
        text_w = 12.2 - image_width - 0.35
        add_bullets(slide, bullets, width=text_w, size=bullet_size)
        add_image(slide, image, 12.2 - image_width + 0.15, 1.25, image_width, 5.9)
    else:
        add_bullets(slide, bullets, size=bullet_size)

    if notes:
        add_notes(slide, notes)


SLIDES = [
    {
        "title": "Problem Statement",
        "bullets": [
            "Schools often rely on paper registers, spreadsheets, and disconnected tools.",
            "The same student data is entered repeatedly, causing duplication and inconsistency.",
            "Manual attendance, fee, and examination work is slow and error-prone.",
            "Reporting requires collecting data from many sources with little automation.",
            "Weak access control and limited audit evidence increase security risk.",
            "Multi-school organizations lack a single secure, tenant-aware platform.",
        ],
        "notes": "Explain why fragmented administration is a real operational problem and why a centralized SaaS approach is needed.",
    },
    {
        "title": "Objectives and Scope",
        "bullets": [
            "Build a multi-tenant SaaS platform for school administration workflows.",
            "Enforce tenant isolation and role-based access for four fixed roles.",
            "Deliver 15 modules: auth, schools, users, academics, students, attendance, fees, exams, reports, dashboards, logs, backups.",
            "Support Super Admin, School Admin, Teacher, and Accountant responsibilities.",
            "Demonstrate engineering practice: layered architecture, services, policies, tests, deployment.",
            "Out of scope: parent/student portals, mobile app, live payment gateway, custom roles.",
        ],
        "notes": "Highlight that scope is intentionally focused for MCA demonstrability while covering core school operations end to end.",
    },
    {
        "title": "Existing vs Proposed System",
        "bullets": [
            "Existing: manual registers, spreadsheets, separate fee and exam files.",
            "Existing: slow retrieval, human calculation errors, weak accountability.",
            "Proposed: School Portal — one secure web application for all core workflows.",
            "Proposed: structured academic setup, student records, and enrollment tracking.",
            "Proposed: attendance entry, sandbox fee collection, examination and report cards.",
            "Proposed: dashboards, operational reports, activity log, audit trail, backup evidence.",
        ],
        "image": SCREENSHOTS / "00_Landing-page.png",
        "image_width": 5.2,
        "notes": "Contrast manual fragmentation with the centralized proposed system and show the live landing page branding.",
    },
    {
        "title": "Architecture and Native Multi-Tenancy",
        "bullets": [
            "Layered monolith: Blade UI → routes/middleware → controllers → services → models → MySQL.",
            "Native Laravel tenancy using school_id — no external tenancy package.",
            "TenantContext middleware, global scopes, BelongsToTenant, policies, and services.",
            "Single application and shared schema; each school is a logically isolated tenant.",
            "Super Admin (school_id NULL) uses authorized platform paths only.",
            "Stack: Laravel 13, PHP 8.4, MySQL 8, Bootstrap 5, Git/GitHub.",
        ],
        "image": DIAGRAMS / "figure-4-1-system-architecture.png",
        "image_width": 5.4,
        "notes": "Walk through the architecture diagram and explain why native school_id tenancy was chosen for clarity and MCA evaluation.",
    },
    {
        "title": "Database and ER Design",
        "bullets": [
            "MySQL 8 relational database with shared-schema multi-tenant design.",
            "28 tables covering platform, tenant, academic, student, attendance, fee, exam, and operations data.",
            "Tenant-owned records linked through school_id with indexes and foreign keys.",
            "restrictOnDelete() preserves academic, financial, and audit history.",
            "Normalization reduces duplication across students, enrollments, and transactions.",
            "ER design supports reporting, RBAC, and tenant isolation testing.",
        ],
        "image": DIAGRAMS / "figure-4-2-er-diagram.png",
        "image_width": 5.5,
        "notes": "Use the ER diagram to explain major entities and how school_id connects tenant data.",
    },
    {
        "title": "Roles and Authorization (RBAC)",
        "bullets": [
            "Super Admin: platform dashboard, school management, platform reports, backups.",
            "School Admin: own-school settings, users, academics, students, attendance, fees, exams, reports, logs.",
            "Teacher: assigned attendance, marks entry, and scoped report-card access.",
            "Accountant: fee collection, receipts, outstanding balances, financial reports.",
            "Authorization via Laravel Policies, Gates, middleware, and Form Request validation.",
            "Fixed four-role model keeps permissions testable and secure.",
        ],
        "image": DIAGRAMS / "figure-3-3-use-case.png",
        "image_width": 5.0,
        "notes": "Explain RBAC boundaries and why custom roles were excluded from MCA scope.",
    },
    {
        "title": "Core Academic and Student Modules",
        "bullets": [
            "Academic structure: years, terms, classes, sections, subjects, teacher profiles.",
            "Student registration, profile management, photo handling, and enrollment.",
            "School Admin dashboard for operational visibility and navigation.",
            "Teacher dashboard for assigned class and attendance workflows.",
            "Attendance entry with date/class/section filtering and summary views.",
            "Module design keeps controllers thin and business rules in services.",
        ],
        "image": SCREENSHOTS / "01_overview-dashboard-school-admin.png",
        "image_width": 5.3,
        "notes": "Demonstrate academic setup and student management as the foundation for attendance, fees, and examinations.",
    },
    {
        "title": "Attendance, Fees, and Examinations",
        "bullets": [
            "Attendance: daily entry, review, and reporting by class and date.",
            "Fees: structures, collection, receipts, outstanding balances (sandbox transactions only).",
            "Examinations: exam setup, marks entry, grade scales, result processing.",
            "Report cards: printable academic outputs with assignment-scoped teacher access.",
            "Workflows use service-layer transactions and policy checks.",
            "Activity and audit evidence recorded for sensitive operations.",
        ],
        "image": SCREENSHOTS / "02_fees-collect-accountant.png",
        "image_width": 5.2,
        "notes": "Clarify that fee payments are sandbox-only, not a live Razorpay/Stripe integration.",
    },
    {
        "title": "Reports, Analytics, and System Operations",
        "bullets": [
            "Reports hub: Student, Attendance, Fee, Examination, School, and User reports.",
            "Role-specific dashboards with Chart.js summaries where applicable.",
            "Activity Log: user action evidence for operational monitoring.",
            "Audit Trail: data-change history for accountability.",
            "Backup Management: Super Admin backup operation records and export evidence.",
            "System Operations separated from the operational Reports hub by design.",
        ],
        "image": SCREENSHOTS / "02_reports-accountant.png",
        "image_width": 5.2,
        "notes": "Important viva point: Activity/Audit/Backup are System Operations, not report hub categories.",
    },
    {
        "title": "Security and Tenant Isolation",
        "bullets": [
            "Authentication, CSRF protection, session security, and server-side validation.",
            "Policies and Gates enforce module-level and record-level authorization.",
            "Tenant isolation via school_id scopes — never trust client-supplied tenant keys.",
            "Private storage for student photos; controlled access to sensitive records.",
            "Automated tenant-isolation tests plus two-browser SHA/SHB smoke validation.",
            "Cross-tenant data exposure treated as a critical defect.",
        ],
        "image": SCREENSHOTS / "tenant-isolation-sha-students.png",
        "image_width": 5.0,
        "notes": "Show that Springdale High A and B cannot see each other's student lists.",
    },
    {
        "title": "Testing and Validation Results",
        "bullets": [
            "394 automated tests with 3,312 assertions in the Laravel test suite.",
            "Feature tests for workflows; policy tests for RBAC denials.",
            "Tenant-isolation, security, and rollback scenarios covered.",
            "Manual smoke testing across four roles on the deployed application.",
            "Pint, migrations, seeders, and release gates used during development.",
            "System considered ready for MCA report evidence and live demonstration.",
        ],
        "image": SCREENSHOTS / "php-artisan-test-394-passed.png",
        "image_width": 5.4,
        "notes": "Quote the verified test totals from the repository — do not use outdated numbers.",
    },
    {
        "title": "Cloud Deployment and Live Demo",
        "bullets": [
            "Deployed cloud-hosted School Portal for evaluator access.",
            "Live URL: schoolportal.pagescorch.com (HTTPS).",
            "Demo schools seeded for multi-tenant demonstration (SHA, SHB, SHC).",
            "GitHub repository available for source and documentation review.",
            "Installation, deployment, and smoke-test guides included in project docs.",
            "Supports Qollabb live-demo and viva evaluation requirements.",
        ],
        "image": SCREENSHOTS / "01_overview-dashboard-super-admin.png",
        "image_width": 5.2,
        "notes": "Offer to log in live as Super Admin or School Admin and walk through tenant isolation.",
    },
    {
        "title": "Achievements, Limitations, and Future Work",
        "bullets": [
            "Achieved: full MCA scope — 15 modules, native multi-tenancy, RBAC, reports, ops evidence.",
            "Achieved: documented architecture, testing strategy, and deployment package.",
            "Limitation: sandbox fee workflow only — no production payment gateway.",
            "Limitation: no parent/student portals, mobile app, SMS, or custom roles.",
            "Future: parent portal, notifications, live payments, richer analytics, API integrations.",
            "Future: performance tuning and extended operational monitoring as schools scale.",
        ],
        "notes": "Be honest about limitations — evaluators appreciate clear scope boundaries and realistic future roadmap.",
    },
    {
        "title": "Conclusion and Thank You",
        "bullets": [
            "School Portal centralizes multi-school administration in one secure SaaS platform.",
            "Native school_id tenancy, RBAC, and layered architecture support maintainability.",
            "Core academic, attendance, fee, examination, and reporting workflows are implemented.",
            "394 tests and live deployment provide strong MCA submission evidence.",
            f"Live demo: {LIVE_URL}",
            f"Source code: {GITHUB_URL}",
            "Thank you — questions welcome.",
        ],
        "bullet_size": 18,
        "notes": "Close with confidence, invite questions, and be ready to demonstrate login and tenant isolation live.",
    },
]


def build_pptx() -> Presentation:
    prs = Presentation()
    prs.slide_width = SLIDE_W
    prs.slide_height = SLIDE_H

    title_slide(prs)
    for spec in SLIDES:
        content_slide(
            prs,
            spec["title"],
            spec["bullets"],
            image=spec.get("image"),
            image_width=spec.get("image_width", 5.6),
            notes=spec.get("notes", ""),
            bullet_size=spec.get("bullet_size", 19),
        )
    return prs


def pdf_draw_header(c: canvas.Canvas, title: str) -> None:
    c.setFillColor(PDF_NAVY)
    c.rect(0, PDF_H - 0.72 * inch, PDF_W, 0.72 * inch, fill=1, stroke=0)
    c.setFillColor(colors.white)
    c.setFont("Helvetica-Bold", 20)
    c.drawString(0.45 * inch, PDF_H - 0.5 * inch, title)


def pdf_draw_bullets(c: canvas.Canvas, bullets: list[str], x: float, y: float, max_w: float, size: int = 11):
    c.setFillColor(PDF_DARK)
    c.setFont("Helvetica", size)
    line_h = size + 4
    for bullet in bullets:
        wrapped = textwrap.wrap(f"• {bullet}", width=62 if max_w < 5.5 * inch else 95)
        for line in wrapped:
            if y < 0.45 * inch:
                break
            c.drawString(x, y, line)
            y -= line_h
        y -= 2


def pdf_add_image(c: canvas.Canvas, path: Path, x: float, y: float, w: float, h: float) -> None:
    if not path.exists():
        return
    data = compress_image(path, max_width=1200)
    c.drawImage(ImageReader(data), x, y, width=w, height=h, preserveAspectRatio=True, anchor="nw")


def build_pdf() -> None:
    c = canvas.Canvas(str(OUT_PDF), pagesize=(PDF_W, PDF_H))

    # Title slide
    c.setFillColor(PDF_NAVY)
    c.rect(0, 0, PDF_W, PDF_H, fill=1, stroke=0)
    c.setFillColor(colors.white)
    c.setFont("Helvetica-Bold", 24)
    c.drawString(0.7 * inch, PDF_H - 1.4 * inch, "Multi-Tenant School Administration")
    c.drawString(0.7 * inch, PDF_H - 1.85 * inch, "Management SaaS Platform")
    c.setFont("Helvetica", 18)
    c.setFillColor(colors.HexColor("#BFDBFE"))
    c.drawString(0.7 * inch, PDF_H - 2.25 * inch, "School Portal")
    c.setFillColor(colors.HexColor("#E2E8F0"))
    c.setFont("Helvetica", 13)
    y = PDF_H - 3.0 * inch
    for line in [
        f"Submitted by: {STUDENT_NAME}",
        f"Enrollment No.: {ENROLLMENT_NO}",
        UNIVERSITY,
        f"Project Mentor: {MENTOR}",
        ORGANIZATION,
        f"Academic Year: {ACADEMIC_YEAR}",
        "MCA Major Project — Qollabb Submission",
    ]:
        c.drawString(0.7 * inch, y, line)
        y -= 0.28 * inch
    c.showPage()

    for spec in SLIDES:
        c.setFillColor(PDF_LIGHT)
        c.rect(0, 0, PDF_W, PDF_H, fill=1, stroke=0)
        pdf_draw_header(c, spec["title"])

        image = spec.get("image")
        bullets = spec["bullets"]
        if image and image.exists():
            text_w = 4.6 * inch
            img_x = 5.35 * inch
            img_w = 4.35 * inch
            img_h = 4.55 * inch
            pdf_draw_bullets(c, bullets, 0.45 * inch, PDF_H - 1.05 * inch, text_w)
            pdf_add_image(c, image, img_x, 0.55 * inch, img_w, img_h)
        else:
            pdf_draw_bullets(c, bullets, 0.45 * inch, PDF_H - 1.05 * inch, PDF_W)

        c.showPage()

    c.save()


def main() -> None:
    prs = build_pptx()
    prs.save(str(OUT_PPTX))
    build_pdf()

    pptx_mb = OUT_PPTX.stat().st_size / (1024 * 1024)
    pdf_mb = OUT_PDF.stat().st_size / (1024 * 1024)
    print(f"Built PPTX: {OUT_PPTX} ({pptx_mb:.2f} MB)")
    print(f"Built PDF:  {OUT_PDF} ({pdf_mb:.2f} MB)")
    if pptx_mb > 20 or pdf_mb > 20:
        print("WARNING: One or more files exceed the 20 MB Qollabb limit.")
    print("Student details are set in build_mca_presentation.py (STUDENT_NAME, ENROLLMENT_NO).")


if __name__ == "__main__":
    main()
