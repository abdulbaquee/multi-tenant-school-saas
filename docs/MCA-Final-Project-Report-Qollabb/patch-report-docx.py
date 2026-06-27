#!/usr/bin/env python3
"""Apply verified authenticity fixes to Chapter 3 and Chapter 5 DOCX files."""

from __future__ import annotations

import shutil
import tempfile
import zipfile
from pathlib import Path

BASE = Path(__file__).resolve().parent

REPLACEMENTS: dict[str, list[tuple[str, str]]] = {
    "Chapter 3 — System Analysis.docx": [
        (
            "The Reporting Module provides operational reports across student, attendance, fee, examination, and audit-related areas. It supports filters, search, pagination, CSV export, and browser print for printable report views. Reports are role-aware and tenant-aware, meaning each user receives only the data that is permitted by role, tenant, assignment, and policy boundaries. Super Admin reports are platform-level and aggregate-focused, while School Admin, Teacher, and Accountant reports are restricted to their permitted school, class, assigned, or financial context.",
            "The Reporting Module provides operational reports across student, attendance, fee, and examination areas. Super Admin and School Admin users may also access authorized school summary and user summary reports where permitted. It supports filters, search, pagination, CSV export, and browser print for printable report views. Reports are role-aware and tenant-aware, meaning each user receives only the data that is permitted by role, tenant, assignment, and policy boundaries. Super Admin reports are platform-level and aggregate-focused, while School Admin, Teacher, and Accountant reports are restricted to their permitted school, class, assigned, or financial context. Activity logs, audit trail, and backup management are provided through System Operations, not the Reports hub.",
        ),
        (
            "The sixth process is Reporting. This includes student reports, attendance reports, fee reports, examination reports, audit reports, CSV export, browser print, dashboard analytics, activity log review, audit trail review, and backup history where permitted. Reports are generated through filters and role-specific authorization. The reporting process does not expose data outside the user’s tenant, assignment, or platform boundary.",
            "The sixth process is Reporting. This includes student reports, attendance reports, fee reports, examination reports, and authorized school or user summary reports for Super Admin and School Admin roles. CSV export and browser-print views are supported where implemented. Dashboard analytics provides chart-based summaries for authorized roles. Activity log review, audit trail review, and backup history are handled through the separate System Operations workspace rather than the Reports hub. Reports are generated through filters and role-specific authorization. The reporting process does not expose data outside the user’s tenant, assignment, or platform boundary.",
        ),
    ],
    "Chapter 5 — System Implementation.docx": [
        (
            "Report categories include student reports, attendance reports, fee reports, examination reports, activity reports, audit reports, and backup-related reports where permitted. Reports support filters, pagination, CSV export where implemented, and browser print views. The report service layer ensures that each role sees only the data allowed by its responsibility boundary.",
            "Report categories in the Reports hub include student reports, attendance reports, fee reports, examination reports, and authorized school or user summary reports for Super Admin and School Admin roles. Reports support filters, pagination, CSV export where implemented, and browser print views. The report service layer ensures that each role sees only the data allowed by its responsibility boundary. Activity log review, audit trail review, and backup history are implemented under System Operations with separate policies and navigation, not as CSV report hub categories.",
        ),
    ],
}


def patch_docx(path: Path, replacements: list[tuple[str, str]]) -> int:
    xml_path = Path("word/document.xml")
    applied = 0

    with tempfile.TemporaryDirectory() as tmp:
        work = Path(tmp)
        with zipfile.ZipFile(path, "r") as zin:
            zin.extractall(work)

        document_xml = work / xml_path
        content = document_xml.read_text(encoding="utf-8")
        original = content

        for old, new in replacements:
            if old not in content:
                raise ValueError(f"Expected text not found in {path.name}:\n{old[:120]}...")
            content = content.replace(old, new, 1)
            applied += 1

        if content == original:
            return 0

        document_xml.write_text(content, encoding="utf-8")

        patched = path.with_suffix(".patched.docx")
        with zipfile.ZipFile(patched, "w", zipfile.ZIP_DEFLATED) as zout:
            for file_path in sorted(work.rglob("*")):
                if file_path.is_file():
                    zout.write(file_path, file_path.relative_to(work).as_posix())

        backup = path.with_suffix(".backup.docx")
        if not backup.exists():
            shutil.copy2(path, backup)

        shutil.move(str(patched), str(path))

    return applied


def main() -> None:
    for filename, replacements in REPLACEMENTS.items():
        path = BASE / filename
        if not path.exists():
            raise SystemExit(f"Missing file: {path}")

        count = patch_docx(path, replacements)
        print(f"Patched {filename}: {count} replacement(s)")


if __name__ == "__main__":
    main()
