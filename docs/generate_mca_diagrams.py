#!/usr/bin/env python3
"""Generate MCA report DFD, ER, and UML diagram PNGs from School Portal design docs."""

from __future__ import annotations

from pathlib import Path

import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import Circle, FancyArrowPatch, FancyBboxPatch, Polygon

OUT = Path(__file__).resolve().parent / "mca-diagrams"
OUT.mkdir(parents=True, exist_ok=True)

plt.rcParams.update({
    "font.family": "DejaVu Sans",
    "font.size": 9,
    "figure.facecolor": "white",
})


def save(fig: plt.Figure, name: str) -> Path:
    path = OUT / name
    fig.savefig(path, dpi=200, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    return path


def box(ax, xy, w, h, text, fc="#f8f9fa", ec="#212529", fontsize=8, lw=1.2):
    patch = FancyBboxPatch(
        xy, w, h,
        boxstyle="round,pad=0.02,rounding_size=0.02",
        linewidth=lw, edgecolor=ec, facecolor=fc,
    )
    ax.add_patch(patch)
    ax.text(xy[0] + w / 2, xy[1] + h / 2, text, ha="center", va="center", fontsize=fontsize, wrap=True)
    return patch


def circle_process(ax, center, radius, text):
    c = Circle(center, radius, fc="#e7f1ff", ec="#0d6efd", lw=1.5)
    ax.add_patch(c)
    ax.text(center[0], center[1], text, ha="center", va="center", fontsize=8, fontweight="bold")
    return c


def arrow(ax, p1, p2, label="", style="-|>", color="#495057"):
    arr = FancyArrowPatch(p1, p2, arrowstyle=style, mutation_scale=12, linewidth=1.1, color=color)
    ax.add_patch(arr)
    if label:
        mx, my = (p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2
        ax.text(mx, my + 0.08, label, ha="center", fontsize=7, color="#495057")


def actor(ax, xy, name):
    x, y = xy
    ax.plot([x, x], [y, y + 0.35], color="#212529", lw=1.5)
    ax.add_patch(Circle((x, y + 0.45), 0.12, fc="white", ec="#212529", lw=1.5))
    ax.plot([x - 0.2, x + 0.2], [y + 0.28, y + 0.28], color="#212529", lw=1.5)
    ax.plot([x, x - 0.18], [y + 0.28, y + 0.05], color="#212529", lw=1.5)
    ax.plot([x, x + 0.18], [y + 0.28, y + 0.05], color="#212529", lw=1.5)
    ax.text(x, y - 0.12, name, ha="center", fontsize=8, fontweight="bold")


def use_case(ax, xy, w, h, text):
    e = mpatches.Ellipse(xy, w, h, fc="#fff3cd", ec="#856404", lw=1.2)
    ax.add_patch(e)
    ax.text(xy[0], xy[1], text, ha="center", va="center", fontsize=7)


def dfd_level_0() -> Path:
    fig, ax = plt.subplots(figsize=(11, 6))
    ax.set_xlim(0, 11)
    ax.set_ylim(0, 6)
    ax.axis("off")
    ax.set_title("Figure 3.1 — DFD Level 0 (Context Diagram)\nSchool Portal", fontsize=11, fontweight="bold", pad=12)

    actor(ax, (1.0, 4.6), "Super\nAdmin")
    actor(ax, (1.0, 2.8), "School\nAdmin")
    actor(ax, (1.0, 1.0), "Teacher")
    actor(ax, (10.0, 2.8), "Accountant")

    circle_process(ax, (5.5, 3.0), 1.35, "School Portal\n(SaaS Web App)")

    store = FancyBboxPatch((4.6, 0.35), 1.8, 0.55, boxstyle="square,pad=0.02", fc="#d1e7dd", ec="#0f5132", lw=1.2)
    ax.add_patch(store)
    ax.text(5.5, 0.62, "D1 MySQL 8\nDatabase", ha="center", va="center", fontsize=8)

    for y in (4.75, 2.95, 1.15):
        arrow(ax, (1.2, y), (4.1, 3.0), "requests")
        arrow(ax, (4.1, 3.0), (1.2, y - 0.15), "responses / views")
    arrow(ax, (8.8, 2.95), (6.9, 3.0), "fee requests")
    arrow(ax, (6.9, 3.0), (8.8, 2.8), "receipts / reports")
    arrow(ax, (5.5, 1.65), (5.5, 0.95), "read / write tenant data")

    return save(fig, "figure-3-1-dfd-level-0.png")


def dfd_level_1() -> Path:
    fig, ax = plt.subplots(figsize=(12, 7))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 7)
    ax.axis("off")
    ax.set_title("Figure 3.2 — DFD Level 1\nSchool Portal Major Processes", fontsize=11, fontweight="bold", pad=12)

    actor(ax, (0.6, 5.8), "Users\n(4 roles)")
    box(ax, (9.8, 0.3), 1.8, 0.7, "D1 MySQL 8\nDatabase", fc="#d1e7dd", ec="#0f5132")

    processes = [
        (1.5, 5.2, 2.0, 0.75, "1.0\nAuthentication"),
        (4.2, 5.2, 2.0, 0.75, "2.0\nTenant Mgmt"),
        (7.0, 5.2, 2.2, 0.75, "3.0\nAcademic Ops"),
        (1.5, 3.3, 2.0, 0.75, "4.0\nFinancial Ops"),
        (4.2, 3.3, 2.0, 0.75, "5.0\nExamination"),
        (7.0, 3.3, 2.2, 0.75, "6.0\nReporting"),
        (4.2, 1.4, 2.6, 0.75, "7.0\nSystem Operations\n(Activity / Audit / Backup)"),
    ]
    for x, y, w, h, t in processes:
        box(ax, (x, y), w, h, t, fc="#e7f1ff", ec="#0d6efd")

    arrow(ax, (1.0, 5.5), (1.5, 5.55))
    for x in (2.5, 5.2, 8.1):
        arrow(ax, (x, 5.2), (x, 4.1))
    arrow(ax, (2.5, 3.3), (5.2, 2.15))
    arrow(ax, (8.1, 3.3), (6.0, 2.15))
    arrow(ax, (10.7, 3.65), (10.7, 1.0))
    arrow(ax, (5.5, 1.4), (9.8, 0.65), "persist")

    ax.text(6.0, 0.05, "All tenant-owned flows derive school_id from TenantContext (not user input)", ha="center", fontsize=7, style="italic")
    return save(fig, "figure-3-2-dfd-level-1.png")


def use_case_diagram() -> Path:
    fig, ax = plt.subplots(figsize=(12, 8))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 8)
    ax.axis("off")
    ax.set_title("Figure 3.3 — Use Case Diagram\nSchool Portal (Four Canonical Roles)", fontsize=11, fontweight="bold", pad=12)

    actor(ax, (0.7, 6.5), "Super\nAdmin")
    actor(ax, (0.7, 4.0), "School\nAdmin")
    actor(ax, (0.7, 1.5), "Teacher")
    actor(ax, (11.3, 4.0), "Accountant")

    shared = ["Login", "Logout", "Profile", "Dashboard"]
    for i, uc in enumerate(shared):
        use_case(ax, (3.5 + i * 1.5, 7.2), 1.2, 0.45, uc)

    super_uc = ["Manage Schools", "Platform Reports", "Backup Mgmt", "Activity / Audit"]
    for i, uc in enumerate(super_uc):
        use_case(ax, (2.8, 6.0 - i * 0.75), 2.0, 0.5, uc)

    admin_uc = ["School Settings", "Users / RBAC", "Students", "Attendance", "Fees Setup", "Examinations", "School Reports"]
    for i, uc in enumerate(admin_uc):
        use_case(ax, (5.5, 6.2 - i * 0.62), 2.4, 0.48, uc)

    teacher_uc = ["Mark Attendance", "Marks Entry", "View Students", "Class Reports"]
    for i, uc in enumerate(teacher_uc):
        use_case(ax, (5.5, 2.5 - i * 0.62), 2.2, 0.48, uc)

    acct_uc = ["Collect Fees", "Receipts", "Outstanding", "Fee Reports", "Sandbox Txn"]
    for i, uc in enumerate(acct_uc):
        use_case(ax, (8.8, 6.0 - i * 0.62), 2.2, 0.48, uc)

    ax.text(6.0, 0.25, "«include» authentication on all protected use cases; policies enforce tenant + role boundaries", ha="center", fontsize=7, style="italic")
    return save(fig, "figure-3-3-use-case.png")


def system_architecture() -> Path:
    fig, ax = plt.subplots(figsize=(8, 10))
    ax.set_xlim(0, 8)
    ax.set_ylim(0, 10)
    ax.axis("off")
    ax.set_title("Figure 4.1 — Layered System Architecture\nSchool Portal (Laravel 13 Monolith)", fontsize=11, fontweight="bold", pad=12)

    layers = [
        ("Presentation Layer", "Blade + Bootstrap 5 + Chart.js\n(resources/views)"),
        ("Routing Layer", "routes/web.php + middleware groups"),
        ("Middleware Layer", "auth · tenant · role · CSRF"),
        ("Controller Layer", "Thin HTTP controllers"),
        ("Validation Layer", "Form Requests"),
        ("Service Layer", "Business workflows + transactions"),
        ("Authorization Layer", "Policies + RBAC permissions"),
        ("Model Layer", "Eloquent ORM + BelongsToTenant + TenantScope"),
        ("Database Layer", "MySQL 8 — single DB, school_id tenancy"),
    ]
    y = 8.8
    for title, body in layers:
        box(ax, (1.0, y), 6.0, 0.85, f"{title}\n{body}", fc="#e7f1ff" if "Service" in title or "Model" in title else "#f8f9fa")
        if y > 1.2:
            arrow(ax, (4.0, y), (4.0, y - 0.15))
        y -= 1.0

    box(ax, (1.0, 0.2), 6.0, 0.55, "Browser (HTTPS) → Nginx → PHP-FPM 8.4", fc="#fff3cd", ec="#856404")
    arrow(ax, (4.0, 0.75), (4.0, 0.95))
    return save(fig, "figure-4-1-system-architecture.png")


def er_diagram_core() -> Path:
    fig, ax = plt.subplots(figsize=(13, 9))
    ax.set_xlim(0, 13)
    ax.set_ylim(0, 9)
    ax.axis("off")
    ax.set_title("Figure 4.2 — Core Entity Relationship Diagram\n28 Tables (logical groups)", fontsize=11, fontweight="bold", pad=12)

    entities = {
        "schools": (1.0, 7.2, "schools"),
        "school_settings": (3.5, 7.2, "school_settings"),
        "users": (6.2, 7.2, "users"),
        "roles": (8.8, 7.2, "roles"),
        "permissions": (11.0, 7.2, "permissions"),
        "academic_years": (1.0, 5.3, "academic_years"),
        "classes": (3.2, 5.3, "classes"),
        "sections": (5.4, 5.3, "sections"),
        "students": (7.6, 5.3, "students"),
        "student_enrollments": (10.0, 5.3, "student_enrollments"),
        "attendances": (1.0, 3.4, "attendances"),
        "fee_categories": (3.2, 3.4, "fee_categories"),
        "student_fees": (5.4, 3.4, "student_fees"),
        "fee_payments": (7.6, 3.4, "fee_payments"),
        "exams": (9.8, 3.4, "exams"),
        "exam_results": (11.5, 3.4, "exam_results"),
        "activity_logs": (2.5, 1.5, "activity_logs"),
        "audit_logs": (5.5, 1.5, "audit_logs"),
        "backup_logs": (8.5, 1.5, "backup_logs"),
    }
    for x, y, name in entities.values():
        box(ax, (x, y), 1.7, 0.55, name, fontsize=7)

    rels = [
        ((2.7, 7.45), (3.5, 7.45), "1:1"),
        ((2.7, 7.2), (6.2, 7.45), "1:N"),
        ((7.9, 7.45), (8.8, 7.45), "N:1"),
        ((9.5, 7.2), (6.2, 7.2), "school_id"),
        ((2.7, 5.55), (3.2, 5.55), "1:N"),
        ((5.0, 5.55), (5.4, 5.55), "1:N"),
        ((7.2, 5.55), (7.6, 5.55), "1:N"),
        ((9.3, 5.55), (10.0, 5.55), "1:N"),
        ((8.5, 5.3), (1.85, 3.95), "1:N"),
        ((9.3, 3.65), (11.5, 3.65), "1:N"),
    ]
    for p1, p2, label in rels:
        arrow(ax, p1, p2, label)

    ax.text(6.5, 0.45, "Tenant-owned tables include school_id · Platform tables: roles, permissions, role_permissions", ha="center", fontsize=8, style="italic")
    ax.text(0.5, 8.5, "Platform", fontsize=8, fontweight="bold", color="#0d6efd")
    ax.text(0.5, 6.0, "Academic / Students", fontsize=8, fontweight="bold", color="#0d6efd")
    ax.text(0.5, 4.1, "Attendance / Fees / Exams", fontsize=8, fontweight="bold", color="#0d6efd")
    ax.text(0.5, 2.2, "System", fontsize=8, fontweight="bold", color="#0d6efd")
    return save(fig, "figure-4-2-er-diagram.png")


def screen_flow() -> Path:
    fig, ax = plt.subplots(figsize=(11, 7))
    ax.set_xlim(0, 11)
    ax.set_ylim(0, 7)
    ax.axis("off")
    ax.set_title("Figure 4.3 — Screen Flow (Role-Based Navigation)\nSchool Portal", fontsize=11, fontweight="bold", pad=12)

    box(ax, (4.5, 6.0), 2.0, 0.6, "Login", fc="#fff3cd", ec="#856404")
    dashboards = [
        (0.6, 4.5, "Super Admin\nDashboard"),
        (3.3, 4.5, "School Admin\nDashboard"),
        (6.0, 4.5, "Teacher\nDashboard"),
        (8.7, 4.5, "Accountant\nDashboard"),
    ]
    for x, y, t in dashboards:
        box(ax, (x, y), 2.0, 0.75, t, fc="#e7f1ff", ec="#0d6efd")
        arrow(ax, (5.5, 6.0), (x + 1.0, y + 0.75))

    modules = [
        (0.4, 2.8, "Schools · Users · Backup\nReports · Analytics · Logs"),
        (3.1, 2.8, "Settings · Academic · Students\nAttendance · Fees · Exams"),
        (5.8, 2.8, "Attendance · Marks\nStudents · Reports"),
        (8.5, 2.8, "Fee Collection · Receipts\nOutstanding · Reports"),
    ]
    for (x, y, t), (dx, dy, _) in zip(modules, dashboards):
        box(ax, (x, y), 2.4, 1.0, t, fontsize=7)
        arrow(ax, (dx + 1.0, dy), (x + 1.2, y + 1.0))

    box(ax, (3.8, 0.5), 3.4, 0.65, "Max depth: 2 levels · Sidebar from RBAC · URL protected by policies", fc="#f8f9fa")
    return save(fig, "figure-4-3-screen-flow.png")


def class_diagram() -> Path:
    fig, ax = plt.subplots(figsize=(12, 8))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 8)
    ax.axis("off")
    ax.set_title("Figure 4.4 — Class Diagram (Core Eloquent Models)\nSchool Portal", fontsize=11, fontweight="bold", pad=12)

    classes = [
        (0.5, 6.2, "School", "id, name, code\nstatus"),
        (2.8, 6.2, "User", "email, school_id\nrole_id"),
        (5.1, 6.2, "Role", "code, name"),
        (7.2, 6.2, "Permission", "code, module"),
        (9.3, 6.2, "SchoolSetting", "timezone, grading"),
        (0.5, 4.0, "Student", "admission_no\nstatus"),
        (2.8, 4.0, "StudentEnrollment", "roll_no, year"),
        (5.1, 4.0, "ClassModel", "name"),
        (7.2, 4.0, "Section", "teacher_id"),
        (9.3, 4.0, "Attendance", "date, status"),
        (0.5, 1.8, "StudentFee", "balance, paid"),
        (2.8, 1.8, "FeePayment", "receipt_no"),
        (5.1, 1.8, "Exam", "name, term"),
        (7.2, 1.8, "ExamResult", "marks, grade"),
        (9.3, 1.8, "ReportCard", "summary"),
    ]
    for x, y, name, attrs in classes:
        box(ax, (x, y), 1.9, 0.95, f"{name}\n─────────\n{attrs}", fontsize=7, fc="#f8f9fa")

    arrows = [
        ((2.4, 6.65), (2.8, 6.65), "1..*"),
        ((4.7, 6.65), (5.1, 6.65)),
        ((2.4, 6.2), (0.5, 4.95), "1..*"),
        ((4.7, 4.45), (2.8, 4.45)),
        ((7.0, 4.45), (5.1, 4.45)),
        ((2.4, 4.45), (0.5, 2.75)),
        ((4.7, 2.25), (2.8, 2.25)),
        ((7.0, 2.25), (5.1, 2.25)),
        ((9.0, 2.25), (7.2, 2.25)),
    ]
    for item in arrows:
        arrow(ax, item[0], item[1], item[2] if len(item) > 2 else "")

    ax.text(6.0, 0.4, "«BelongsToTenant» on tenant-owned models · TenantScope global filter", ha="center", fontsize=8, style="italic")
    return save(fig, "figure-4-4-class-diagram.png")


def sequence_login() -> Path:
    fig, ax = plt.subplots(figsize=(11, 8))
    ax.set_xlim(0, 11)
    ax.set_ylim(0, 8)
    ax.axis("off")
    ax.set_title("Figure 4.5 — Sequence Diagram: Login & Tenant Context\nSchool Portal", fontsize=11, fontweight="bold", pad=12)

    lifelines = ["User", "Browser", "Auth", "TenantCtx\nMiddleware", "Policy /\nService", "MySQL"]
    xs = [1.0, 2.8, 4.6, 6.4, 8.2, 9.8]
    for x, name in zip(xs, lifelines):
        box(ax, (x - 0.55, 7.0), 1.1, 0.5, name, fontsize=7)
        ax.plot([x, x], [0.5, 7.0], "--", color="#adb5bd", lw=1)

    steps = [
        (1.0, 2.8, 6.3, "submit credentials"),
        (2.8, 4.6, 6.0, "authenticate"),
        (4.6, 4.6, 5.7, "validate user + school status"),
        (4.6, 6.4, 5.4, "resolve Platform or Tenant context"),
        (6.4, 8.2, 5.1, "authorize route / record"),
        (8.2, 9.8, 4.8, "scoped query (school_id)"),
        (9.8, 2.8, 4.5, "dashboard response"),
        (6.4, 6.4, 1.0, "clear context end of request"),
    ]
    for i, (x1, x2, y, label) in enumerate(steps):
        color = "#0d6efd" if i % 2 == 0 else "#198754"
        ax.annotate("", xy=(x2, y), xytext=(x1, y), arrowprops=dict(arrowstyle="-|>", color=color, lw=1.2))
        ax.text((x1 + x2) / 2, y + 0.08, label, ha="center", fontsize=6.5)

    ax.text(5.5, 0.25, "Super Admin: school_id = NULL · School users: school_id from user record", ha="center", fontsize=7, style="italic")
    return save(fig, "figure-4-5-sequence-login.png")


def activity_attendance() -> Path:
    fig, ax = plt.subplots(figsize=(9, 10))
    ax.set_xlim(0, 9)
    ax.set_ylim(0, 10)
    ax.axis("off")
    ax.set_title("Figure 4.6 — Activity Diagram: Attendance Entry\nSchool Portal", fontsize=11, fontweight="bold", pad=12)

    y = 9.0
    box(ax, (3.0, y), 3.0, 0.55, "Open Attendance module", fc="#e7f1ff", ec="#0d6efd")
    arrow(ax, (4.5, y), (4.5, y - 0.2))
    y -= 0.9
    diamond = Polygon([(4.5, y), (5.3, y - 0.35), (4.5, y - 0.7), (3.7, y - 0.35)], closed=True, fc="#fff3cd", ec="#856404")
    ax.add_patch(diamond)
    ax.text(4.5, y - 0.35, "Authorized?\n(RBAC + Policy)", ha="center", va="center", fontsize=7)
    box(ax, (6.5, y - 0.55), 2.0, 0.5, "403 / Deny", fc="#f8d7da", ec="#842029", fontsize=7)
    arrow(ax, (5.3, y - 0.35), (6.5, y - 0.35))

    y -= 1.1
    arrow(ax, (4.5, y + 0.4), (4.5, y + 0.2))
    box(ax, (2.5, y - 0.2), 4.0, 0.55, "Select year, class, section, date")
    y -= 0.9
    box(ax, (2.5, y - 0.2), 4.0, 0.55, "Load roster (tenant-scoped)")
    y -= 0.9
    diamond2 = Polygon([(4.5, y), (5.3, y - 0.35), (4.5, y - 0.7), (3.7, y - 0.35)], closed=True, fc="#fff3cd", ec="#856404")
    ax.add_patch(diamond2)
    ax.text(4.5, y - 0.35, "Teacher assigned\nto section?", ha="center", va="center", fontsize=7)
    y -= 1.0
    box(ax, (2.5, y - 0.2), 4.0, 0.55, "Mark statuses + submit")
    y -= 0.9
    box(ax, (2.2, y - 0.2), 4.6, 0.65, "Service: set school_id from\nTenantContext · save records")
    y -= 0.9
    box(ax, (3.0, y - 0.2), 3.0, 0.55, "Activity / Audit log", fc="#d1e7dd", ec="#0f5132")
    y -= 0.9
    box(ax, (3.2, y - 0.2), 2.6, 0.55, "Confirmation view", fc="#e7f1ff", ec="#0d6efd")

    return save(fig, "figure-4-6-activity-attendance.png")


def main() -> None:
    paths = [
        dfd_level_0(),
        dfd_level_1(),
        use_case_diagram(),
        system_architecture(),
        er_diagram_core(),
        screen_flow(),
        class_diagram(),
        sequence_login(),
        activity_attendance(),
    ]
    print(f"Generated {len(paths)} diagrams in {OUT}")
    for p in paths:
        print(f"  - {p.name}")


if __name__ == "__main__":
    main()
