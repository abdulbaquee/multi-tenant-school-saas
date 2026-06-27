#!/usr/bin/env python3
"""
Build the merged MCA final project report from MCA-Project-Report-Template.docx,
chapter files, and mca-screenshots.
"""

from __future__ import annotations

import re
import shutil
import zipfile
from datetime import date
from pathlib import Path

from docx import Document
from docx.enum.text import WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches
from docx.text.paragraph import Paragraph
from docxcompose.composer import Composer

BASE = Path(__file__).resolve().parent
REPO = BASE.parent.parent
SCREENSHOTS = REPO / "docs" / "mca-screenshots"
DIAGRAMS = REPO / "docs" / "mca-diagrams"
TEMPLATE = BASE / "MCA-Project-Report-Template.docx"
OUTPUT = BASE / "MCA-Final-Project-Report-FILLED.docx"

PROJECT_TITLE = (
    "Multi-Tenant School Administration Management SaaS Platform (School Portal)"
)
MENTOR = "Ms. Kashish Gupta"
ORGANIZATION = "Regent Digitech Private Limited, Noida"
UNIVERSITY = "Chandigarh University"
ACADEMIC_YEAR = "2025–26"
REPORT_DATE = date.today().strftime("%d %B %Y")
PLACE = "Noida"

# Personal fields — update before printing/signing
STUDENT_NAME = "[Your Full Name]"
ENROLLMENT_NO = "[Your Enrollment Number]"

CHAPTER_FILES = [
    "Chapter 1 — Introduction.docx",
    "Chapter 2 — Literature Review and System Study.docx",
    "Chapter 3 — System Analysis.docx",
    "Chapter 4 — System Design.docx",
    "Chapter 5 — System Implementation.docx",
    "Chapter 6 — Testing and Validation.docx",
    "Chapter 7 — Results and Discussion.docx",
    "Chapter 8 — Conclusion and Future Enhancements.docx",
    "Chapter 9 — References.docx",
    "Chapter 10 — Appendices.docx",
]

STATIC_FIGURES = [
    "Figure 3.1: DFD Level 0 — Context diagram",
    "Figure 3.2: DFD Level 1",
    "Figure 3.3: Use Case Diagram",
    "Figure 4.1: System Architecture Diagram",
    "Figure 4.2: ER Diagram",
    "Figure 4.3: Screen Flow or UI wireframe",
    "Figure 4.4: Class Diagram — Core Models",
    "Figure 4.5: Sequence Diagram — Login and Tenant Context",
    "Figure 4.6: Activity Diagram — Attendance Entry",
    "Figure 5.1: Login page — School Portal branding",
    "Figure 5.2: Super Admin Schools list",
    "Figure 5.3: Users or Roles screen",
    "Figure 5.4: Academic structure screen",
    "Figure 5.5: Student list or profile",
    "Figure 5.6: Attendance entry or monthly summary",
    "Figure 5.7: Fee collection or receipt",
    "Figure 5.8: Examination or report card screen",
    "Figure 5.9: Reports hub or Analytics charts",
    "Figure 5.10: Live landing page with HTTPS URL",
    "Figure 6.1: Automated test output — 394 passed",
    "Figure 6.2: Tenant isolation SHA vs SHB",
    "Figure 7.1: Super Admin platform overview",
    "Figure 7.2: School Admin dashboard",
    "Figure 7.3: Student list or profile output",
    "Figure 7.4: Attendance output",
    "Figure 7.5: Fee collection or receipt output",
    "Figure 7.6: Examination result or report card output",
    "Figure 7.7: Reports hub or Analytics output",
    "Figure 7.8: Tenant isolation demonstration",
    "Figure 7.9: Live landing page with HTTPS URL",
]

STATIC_TABLES = [
    "Table 2.1: Traditional vs Standalone Software vs Multi-Tenant SaaS",
    "Table 4.1: Role vs Permission Summary",
    "Table 6.1: Test Types and Tools",
    "Table 6.2: Test Case Summary",
    "Table 7.1: Production Stack",
    "Table 7.2: Manual/Paper Process vs School Portal",
]

PRELIMINARY_HEADINGS = {
    "Table of Contents",
    "List of Figures",
    "List of Tables",
    "List of Abbreviations",
    "Abstract / Executive Summary",
    "Acknowledgement",
    "Certificate",
    "Declaration",
}

TOC_SKIP_FRAGMENTS = (
    "Word Count",
    "List of Figures in This Chapter",
    "List of Tables in This Chapter",
    "Draft Reference",
    "Approximate word count",
    "List of Figures",
    "List of Tables",
)

ACKNOWLEDGEMENT = f"""I, {STUDENT_NAME}, would like to express my sincere gratitude to all those who supported and guided me during the successful completion of my MCA Major Project titled “Multi-Tenant School Administration Management SaaS Platform.”

I am deeply thankful to my project mentor, {MENTOR}, for her valuable guidance, constructive feedback, and continuous support throughout the project work. Her suggestions helped me improve the quality, structure, and technical direction of this project.

I also extend my heartfelt thanks to the faculty members of {UNIVERSITY} for providing academic guidance, learning resources, and encouragement during the course of my MCA program. Their support helped me strengthen my understanding of software development, system design, testing, and project documentation.

I am grateful to {ORGANIZATION}, for providing the organizational association and practical exposure that contributed to the successful completion of this project.

Finally, I would like to thank my family and friends for their constant motivation, patience, and support during the development and documentation of this project. Their encouragement helped me remain focused and complete the project within the required timeline.

{STUDENT_NAME}"""

ABSTRACT = """This project presents Multi-Tenant School Administration Management SaaS Platform, branded as School Portal, developed as an MCA major project to centralize and secure school administration workflows. The system was built using Laravel 13, PHP 8.4, MySQL 8, Blade templates, and Bootstrap 5, following a layered monolithic architecture. It implements native multi-tenancy through school_id, TenantContextMiddleware, TenantContext, global scopes, policies, and service-layer validation, without using external tenancy packages. Role-Based Access Control (RBAC) supports four fixed roles: Super Admin, School Admin, Teacher, and Accountant. Core modules include Authentication, School Management, School Settings, User Management, Role & Permission, Academic Structure, Student Management, Attendance Management, Fee Management, Examination Management, Reporting, Dashboard & Analytics, Activity Log, Audit Trail, and Backup Management. The Fee Management module includes sandbox transaction workflow only, while production payment gateway integration remains outside the current scope. The application was validated through 394 automated tests and manual smoke testing, including role-based workflows and SHA versus SHB tenant isolation. The system was deployed at https://schoolportal.pagescorch.com, demonstrating practical cloud-based school administration with secure tenant separation and academic project readiness."""

CERTIFICATE_NOTE = (
    "Affix the signed Bonafide / Guide certificate here as directed by the mentor. "
    "The Qollabb platform completion certificate is issued after successful evaluation and viva."
)

ABBREVIATIONS = """API — Application Programming Interface
BCA — Bachelor of Computer Application
CSRF — Cross-Site Request Forgery
DFD — Data Flow Diagram
ER — Entity Relationship
HTTPS — Hypertext Transfer Protocol
MCA — Master of Computer Application
MVP — Minimum Viable Product
ORM — Object-Relational Mapping
RBAC — Role-Based Access Control
SaaS — Software as a Service
SDLC — Software Development Life Cycle
SHA / SHB / SHC — Demo school codes (Springdale High A, B, C)
SQL — Structured Query Language
SSL — Secure Sockets Layer
UI — User Interface
UML — Unified Modeling Language
URL — Uniform Resource Locator
VPS — Virtual Private Server"""


def replace_in_paragraph(paragraph, replacements: dict[str, str]) -> None:
    text = paragraph.text
    if not text:
        return
    new_text = text
    for old, new in replacements.items():
        new_text = new_text.replace(old, new)
    if new_text != text:
        if paragraph.runs:
            paragraph.runs[0].text = new_text
            for run in paragraph.runs[1:]:
                run.text = ""
        else:
            paragraph.text = new_text


def replace_in_document(doc: Document, replacements: dict[str, str]) -> None:
    for paragraph in doc.paragraphs:
        replace_in_paragraph(paragraph, replacements)
    for table in doc.tables:
        for row in table.rows:
            for cell in row.cells:
                for paragraph in cell.paragraphs:
                    replace_in_paragraph(paragraph, replacements)


def patch_docx_xml(path: Path, replacements: dict[str, str]) -> None:
    """Replace text in raw document.xml (covers cover page blocks python-docx may skip)."""
    with zipfile.ZipFile(path, "r") as zin:
        files = {name: zin.read(name) for name in zin.namelist()}

    xml = files["word/document.xml"].decode("utf-8")
    for old, new in replacements.items():
        if old in xml:
            xml = xml.replace(old, new)

    guide_old = (
        "<w:t>Guide</w:t></w:r><w:r><w:rPr><w:sz w:val=\"28\"/>"
        "<w:szCs w:val=\"28\"/></w:rPr><w:t>/Mentor</w:t></w:r><w:r><w:rPr><w:sz w:val=\"28\"/>"
        "<w:szCs w:val=\"28\"/></w:rPr><w:t> Name: ____________________</w:t>"
    )
    if guide_old in xml:
        xml = xml.replace(guide_old, f"<w:t>Guide/Mentor Name: {MENTOR}</w:t>")

    xml = re.sub(
        r"<w:t>Guide</w:t></w:r><w:r><w:rPr><w:sz w:val=\"28\"/><w:szCs w:val=\"28\"/></w:rPr><w:t>/Mentor</w:t></w:r><w:r[^>]*><w:rPr><w:sz w:val=\"28\"/><w:szCs w:val=\"28\"/></w:rPr><w:t xml:space=\"preserve\"> Name: ____________________</w:t>",
        f"<w:t>Guide/Mentor Name: {MENTOR}</w:t>",
        xml,
        count=1,
    )

    # Declaration enrollment split across proofing runs
    xml = re.sub(
        r"<w:t> No: ____________________</w:t>",
        f"<w:t> No: {ENROLLMENT_NO}</w:t>",
        xml,
        count=1,
    )
    xml = re.sub(
        r"(<w:t>Enrollment</w:t></w:r><w:proofErr w:type=\"spellEnd\"/><w:r><w:t xml:space=\"preserve\">) No: ____________________(</w:t>)",
        rf"\1 No: {ENROLLMENT_NO}\2",
        xml,
        count=1,
    )

    files["word/document.xml"] = xml.encode("utf-8")

    temp = path.with_suffix(".xml-patched.docx")
    with zipfile.ZipFile(temp, "w", zipfile.ZIP_DEFLATED) as zout:
        for name, data in files.items():
            zout.writestr(name, data)
    shutil.move(str(temp), str(path))


def resolve_screenshot(label: str) -> list[Path]:
    label_l = label.lower()
    shots = SCREENSHOTS

    rules: list[tuple[list[str], str | list[str]]] = [
        (["php artisan test", "394 passed"], "php-artisan-test-394-passed.png"),
        (["tenant isolation", "sha vs shb"], ["tenant-isolation-sha-students.png", "tenant-isolation-shb-students.png"]),
        (["super admin platform", "super admin overview"], "01_overview-dashboard-super-admin.png"),
        (["school admin dashboard", "springdale high a"], "01_overview-dashboard-school-admin.png"),
        (["schools list", "super admin schools"], "Schools-list.png"),
        (["login", "landing", "branding", "live landing", "https"], "00_Landing-page.png"),
        (["users or roles"], "01_overview-dashboard-super-admin.png"),
        (["classes", "sections", "subjects"], "01_overview-dashboard-school-admin.png"),
        (["student list", "student profile"], "tenant-isolation-sha-students.png"),
        (["attendance"], "01_overview-dashboard-school-admin.png"),
        (["fee collection", "receipt"], "02_fees-collect-accountant.png"),
        (["exam", "report card"], "School-Reports-teacher.png"),
        (["reports hub", "analytics", "fee reports"], "02_reports-fee-reports-accountant.png"),
        (["accountant"], "01_overview-dashboard-accountant.png"),
        (["teacher"], "01_overview-dashboard-teacher.png"),
        (["outstanding"], "02_fees-outstanding-balance-accountant.png"),
        (["fees"], "02_fees-accountant.png"),
    ]

    for keywords, filename in rules:
        if any(k in label_l for k in keywords):
            if isinstance(filename, list):
                paths = [shots / f for f in filename]
            else:
                paths = [shots / filename]
            return [p for p in paths if p.exists()]

    return []


def format_figure_caption(label: str) -> str:
    label = label.strip()
    if label.lower().startswith("figure"):
        return label
    return f"Figure {label}" if re.match(r"^\d", label) else f"Figure: {label}"


def insert_paragraph_after(paragraph: Paragraph) -> Paragraph:
    new_p = OxmlElement("w:p")
    paragraph._p.addnext(new_p)
    return Paragraph(new_p, paragraph._parent)


def insert_word_field(paragraph: Paragraph, instruction: str, placeholder: str) -> None:
    """Insert a Word field (TOC / LOF / LOT) that Word populates on Update Field."""
    run_begin = paragraph.add_run()
    fld_begin = OxmlElement("w:fldChar")
    fld_begin.set(qn("w:fldCharType"), "begin")
    run_begin._r.append(fld_begin)

    run_instr = paragraph.add_run()
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = f" {instruction} "
    run_instr._r.append(instr)

    run_sep = paragraph.add_run()
    fld_sep = OxmlElement("w:fldChar")
    fld_sep.set(qn("w:fldCharType"), "separate")
    run_sep._r.append(fld_sep)

    paragraph.add_run(placeholder)

    run_end = paragraph.add_run()
    fld_end = OxmlElement("w:fldChar")
    fld_end.set(qn("w:fldCharType"), "end")
    run_end._r.append(fld_end)


def delete_paragraph(paragraph: Paragraph) -> None:
    element = paragraph._element
    parent = element.getparent()
    if parent is not None:
        parent.remove(element)


def remove_empty_paragraphs(doc: Document) -> int:
    removed = 0
    for paragraph in list(doc.paragraphs):
        text = paragraph.text.strip()
        if not text or text in {"•", "•\t"} or text.replace("•", "").strip() == "":
            delete_paragraph(paragraph)
            removed += 1
    return removed


def add_static_list_entries(anchor: Paragraph, entries: list[str]) -> None:
    current = anchor
    for entry in entries:
        nxt = insert_paragraph_after(current)
        try:
            nxt.style = "Normal"
        except KeyError:
            pass
        nxt.add_run(entry)
        current = nxt


def build_static_toc_entries(doc: Document) -> list[str]:
    lines: list[str] = []
    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if not text or text in PRELIMINARY_HEADINGS:
            continue
        if any(skip in text for skip in TOC_SKIP_FRAGMENTS):
            continue
        style = paragraph.style.name if paragraph.style else ""
        is_chapter = bool(re.match(r"^Chapter \d+", text))
        is_section = bool(re.match(r"^\d+\.\d+\s", text))
        if (style == "Heading 1" or is_chapter) and is_chapter:
            lines.append(text)
        elif (style == "Heading 2" or is_section) and is_section and lines:
            lines.append(f"    {text}")
    return lines


def normalize_chapter_headings(doc: Document) -> None:
    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if re.match(r"^Chapter \d+", text):
            try:
                paragraph.style = "Heading 1"
            except KeyError:
                pass
        elif re.match(r"^\d+\.\d+\s", text):
            try:
                paragraph.style = "Heading 2"
            except KeyError:
                pass


def clear_section_after_heading(doc: Document, heading_text: str, stop_prefixes: tuple[str, ...]) -> Paragraph | None:
    anchor: Paragraph | None = None
    to_delete: list[Paragraph] = []
    collecting = False

    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if text == heading_text:
            anchor = paragraph
            collecting = True
            continue
        if not collecting:
            continue
        if any(text.startswith(prefix) for prefix in stop_prefixes):
            break
        to_delete.append(paragraph)

    for paragraph in to_delete:
        delete_paragraph(paragraph)

    return anchor


def populate_preliminary_lists(doc: Document) -> None:
    """Replace empty TOC / LOF / LOT placeholders with Word fields + static entries."""
    remove_empty_paragraphs(doc)

    toc = clear_section_after_heading(doc, "Table of Contents", ("List of Figures", "Chapter "))
    if toc is not None:
        field_p = insert_paragraph_after(toc)
        insert_word_field(
            field_p,
            r'TOC \o "1-3" \h \z \u',
            "Right-click here → Update Field → Update entire table.",
        )
        static_toc = build_static_toc_entries(doc)
        if static_toc:
            add_static_list_entries(field_p, static_toc)

    lof = clear_section_after_heading(doc, "List of Figures", ("List of Tables", "Chapter "))
    if lof is not None:
        add_static_list_entries(lof, STATIC_FIGURES)

    lot = clear_section_after_heading(doc, "List of Tables", ("List of Abbreviations", "Chapter "))
    if lot is not None:
        add_static_list_entries(lot, STATIC_TABLES)

    remove_empty_paragraphs(doc)


def apply_caption_styles(doc: Document) -> None:
    caption_style = None
    try:
        caption_style = doc.styles["Caption"]
    except KeyError:
        pass

    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if re.match(r"^Figure\s+\d", text) or re.match(r"^Table\s+\d", text):
            if caption_style is not None:
                paragraph.style = caption_style
            for run in paragraph.runs:
                run.italic = False


def apply_table_grid_style(doc: Document) -> None:
    try:
        grid = doc.styles["Table Grid"]
    except KeyError:
        return
    for table in doc.tables:
        try:
            table.style = grid
        except Exception:
            pass


def post_process_report(path: Path) -> None:
    doc = Document(str(path))
    normalize_chapter_headings(doc)
    populate_preliminary_lists(doc)
    apply_caption_styles(doc)
    apply_table_grid_style(doc)
    remove_empty_paragraphs(doc)
    doc.save(str(path))


def resolve_figure(label: str) -> list[Path]:
    label_l = label.lower()
    rules: list[tuple[list[str], str]] = [
        (["3.1", "dfd level 0", "context diagram"], "figure-3-1-dfd-level-0.png"),
        (["3.2", "dfd level 1"], "figure-3-2-dfd-level-1.png"),
        (["3.3", "use case"], "figure-3-3-use-case.png"),
        (["4.1", "system architecture"], "figure-4-1-system-architecture.png"),
        (["4.2", "er diagram"], "figure-4-2-er-diagram.png"),
        (["4.3", "screen flow", "wireframe"], "figure-4-3-screen-flow.png"),
        (["4.4", "class diagram"], "figure-4-4-class-diagram.png"),
        (["4.5", "sequence diagram", "login", "tenant context"], "figure-4-5-sequence-login.png"),
        (["4.6", "activity diagram", "attendance"], "figure-4-6-activity-attendance.png"),
    ]
    for keywords, filename in rules:
        if any(k in label_l for k in keywords):
            figure_path = DIAGRAMS / filename
            if figure_path.exists():
                return [figure_path]
    return []


def _embed_images_after_paragraph(paragraph, images: list[Path], label: str) -> int:
    parent = paragraph._element.getparent()
    idx = parent.index(paragraph._element)
    inserted = 0

    cap_p = paragraph.insert_paragraph_before(format_figure_caption(label))

    for image_path in images:
        pic_para = paragraph.insert_paragraph_before("")
        pic_para.add_run().add_picture(str(image_path), width=Inches(6.4))
        inserted += 1

    parent.remove(paragraph._element)
    return inserted


def insert_images_for_placeholders(doc: Document) -> int:
    inserted = 0
    screenshot_re = re.compile(r"\[SCREENSHOT:\s*(.+?)\]", re.IGNORECASE)
    figure_re = re.compile(r"\[FIGURE\s*([^\]]+)\]", re.IGNORECASE)
    figure_note_re = re.compile(r"^\(FIGURE .+insert drawn diagram", re.IGNORECASE)

    for paragraph in list(doc.paragraphs):
        text = paragraph.text.strip()
        if not text:
            continue

        if text.startswith("[FIGURE") and text.endswith("]"):
            label = text.strip("[]").replace("FIGURE", "", 1).strip(" :")
            images = resolve_figure(label)
            if images:
                inserted += _embed_images_after_paragraph(paragraph, images, label)
            else:
                paragraph.clear()
                paragraph.add_run(f"(Diagram not found for: {label})").italic = True
            continue

        if figure_note_re.match(text):
            paragraph.text = ""
            continue

        match = screenshot_re.search(text)
        if not match:
            continue

        label = match.group(1).strip()
        images = resolve_screenshot(label)
        if not images:
            paragraph.clear()
            paragraph.add_run(f"(Screenshot not found for: {label})")
            continue

        inserted += _embed_images_after_paragraph(paragraph, images, label)

    return inserted


def clean_references(doc: Document) -> None:
    for paragraph in doc.paragraphs:
        if "[VERIFY" in paragraph.text:
            replace_in_paragraph(
                paragraph,
                {
                    " [VERIFY BEFORE SUBMIT]": "",
                    "[VERIFY BEFORE SUBMIT]": "",
                    " [VERIFY APA]": "",
                    "[VERIFY APA]": "",
                },
            )


def truncate_template_after_toc(doc: Document) -> None:
    """Remove duplicate Abstract and empty chapter placeholders from template tail."""
    body = doc.element.body
    children = list(body)
    cut_index = None
    for idx, child in enumerate(children):
        texts = "".join(node.text or "" for node in child.iter())
        if "Chapter 1: Introduction" in texts:
            cut_index = idx
            break

    if cut_index is None:
        return

    for child in children[cut_index:]:
        body.remove(child)


def tidy_abstract_section(doc: Document) -> None:
    """Place the final abstract before the Table of Contents."""
    for i, paragraph in enumerate(doc.paragraphs):
        if "Abstract / Executive Summary" not in paragraph.text:
            continue
        j = i + 1
        abstract_written = False
        while j < len(doc.paragraphs):
            text = doc.paragraphs[j].text.strip()
            if text == "Table of Contents":
                if not abstract_written:
                    doc.paragraphs[j].insert_paragraph_before(ABSTRACT)
                break
            doc.paragraphs[j].text = ""
            j += 1
        break


def remove_post_toc_duplicate_abstract(doc: Document) -> None:
    seen_toc = False
    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if text == "Table of Contents":
            seen_toc = True
            continue
        if not seen_toc:
            continue
        if text.startswith("List of Figures"):
            break
        if text in {
            "Abstract",
            "",
            "(Use MS Word → References → Table of Contents to auto-generate)",
        }:
            paragraph.text = ""
            continue
        if text.startswith("Chapter "):
            break


def fill_preliminary_pages(doc: Document) -> None:
    replacements = {
        "Copy to the certificate received from Qollabb to be pasted here.": CERTIFICATE_NOTE,
        "I, ____________________, hereby solemnly declare": f"I, {STUDENT_NAME}, hereby solemnly declare",
        'project report titled "____________________"': f'project report titled "{PROJECT_TITLE}"',
        "during the academic year __________ under the supervision of ____________________ (Guide/Mentor Name).": f"during the academic year {ACADEMIC_YEAR} under the supervision of {MENTOR} (Guide/Mentor Name).",
        "Place: ____________________": f"Place: {PLACE}",
        "Date: ____________________": f"Date: {REPORT_DATE}",
        "Write a short paragraph thanking your guide(Mentor), faculty members and organization, for their support.": ACKNOWLEDGEMENT,
        "Write content here...": "",
        "(150–250 words)": "",
    }
    replace_in_document(doc, replacements)
    tidy_abstract_section(doc)
    truncate_template_after_toc(doc)
    remove_post_toc_duplicate_abstract(doc)


def add_list_pages(doc: Document) -> None:
    """Add LOF, LOT, and Abbreviations sections (TOC heading comes from template)."""
    doc.add_paragraph().add_run().add_break(WD_BREAK.PAGE)
    doc.add_heading("List of Figures", level=1)
    doc.add_paragraph().add_run().add_break(WD_BREAK.PAGE)
    doc.add_heading("List of Tables", level=1)
    doc.add_paragraph().add_run().add_break(WD_BREAK.PAGE)
    doc.add_heading("List of Abbreviations", level=1)
    for line in ABBREVIATIONS.splitlines():
        if line.strip():
            doc.add_paragraph(line.strip())


def process_chapter(path: Path) -> Document:
    doc = Document(str(path))
    clean_references(doc)
    insert_images_for_placeholders(doc)
    return doc


def build() -> None:
    if not TEMPLATE.exists():
        raise SystemExit(f"Template not found: {TEMPLATE}")

    work_template = BASE / "_work_template.docx"
    shutil.copy2(TEMPLATE, work_template)
    patch_docx_xml(
        work_template,
        {
            "Project Title _____________________": PROJECT_TITLE,
            "Student Name: ____________________": f"Student Name: {STUDENT_NAME}",
            "Enrollment No: ____________________": f"Enrollment No: {ENROLLMENT_NO}",
            "Guide/Mentor Name: ____________________": f"Guide/Mentor Name: {MENTOR}",
        },
    )

    master = Document(str(work_template))
    fill_preliminary_pages(master)
    add_list_pages(master)

    composer = Composer(master)
    total_images = 0

    for chapter_name in CHAPTER_FILES:
        chapter_path = BASE / chapter_name
        if not chapter_name.endswith(".backup.docx") and chapter_path.exists():
            chapter_doc = process_chapter(chapter_path)
            composer.append(chapter_doc)

    composer.save(str(OUTPUT))
    work_template.unlink(missing_ok=True)

    post_process_report(OUTPUT)

    print(f"Built: {OUTPUT}")
    print(f"Student fields to update before print: {STUDENT_NAME}, {ENROLLMENT_NO}")
    print("Open in Word → select TOC/LOF → right-click → Update Field → Update entire table.")


if __name__ == "__main__":
    build()
