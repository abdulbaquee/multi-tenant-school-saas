# MCA Final Report — Build Output

## Main file to submit (after your edits)

**`MCA-Final-Project-Report-FILLED.docx`**

This is the merged report built from:

- `MCA-Project-Report-Template.docx` (preliminary pages)
- All chapter `.docx` files (non-backup)
- Screenshots from `docs/mca-screenshots/`

## Why TOC / LOF / LOT looked empty (pages 5–8)

The automated builder initially left **placeholder text** only (`"Auto-generate in Microsoft Word…"`) and **empty bullet lines** under the Abstract. Word showed page numbers 5–8 but little visible content.

The rebuilt `MCA-Final-Project-Report-FILLED.docx` now includes:

- **Table of Contents** — chapter/section list + Word TOC field
- **List of Figures** — all 29 figure entries
- **List of Tables** — all 6 table entries
- **List of Abbreviations** — full list

## Final step in Microsoft Word (page numbers)

1. Open `MCA-Final-Project-Report-FILLED.docx` in **Microsoft Word** (not Preview).
2. Click inside **Table of Contents**.
3. Press **F9** or right-click → **Update Field** → **Update entire table**.
4. Repeat for the TOC field line if page numbers are still missing.
5. Manually add dot leaders + page numbers to LOF/LOT if your template requires them, or leave as title list (acceptable for many MCA submissions).

## Regenerate diagrams

```bash
MPLCONFIGDIR=.matplotlib-cache MPLBACKEND=Agg \
  .venv-report/bin/python3 docs/generate_mca_diagrams.py
```

Output: `docs/mca-diagrams/figure-3-1-dfd-level-0.png` … `figure-4-6-activity-attendance.png`

## Rebuild command

```bash
cd /Users/abdul/Sites/multi-tenant-school-saas
.venv-report/bin/python3 docs/MCA-Final-Project-Report-Qollabb/build_mca_report.py
```

## Before you export PDF — update in Word

1. **Find & replace** `[Your Full Name]` and `[Your Enrollment Number]` (cover, declaration, acknowledgement).
2. **Paste** signed Bonafide / Guide certificate on the Certificate page.
3. **Sign** declaration (signature line).
4. **Delete** empty bullet lines under *Abstract / Executive Summary* (if visible).
5. **References → Table of Contents → Update entire table**.
6. **Insert Table of Figures** and **Table of Tables** (screenshots are already embedded).
7. **Diagrams** — auto-generated in `docs/mca-diagrams/` (re-run `docs/generate_mca_diagrams.py` if needed).
8. **Export PDF** for Qollabb upload.

## Final presentation (PPT + PDF)

**Files (ready for Qollabb upload):**

- `MCA-Final-Presentation.pptx` — editable PowerPoint (speaker notes included)
- `MCA-Final-Presentation.pdf` — PDF export for portal upload

Both are built from project screenshots, diagrams, and verified facts (394 tests, live URL, four roles, 15 modules).

**Regenerate:**

```bash
cd /Users/abdul/Sites/multi-tenant-school-saas
.venv-report/bin/python3 docs/MCA-Final-Project-Report-Qollabb/build_mca_presentation.py
```

**Before upload:** edit `STUDENT_NAME` and `ENROLLMENT_NO` at the top of `build_mca_presentation.py`, then re-run the script. Qollabb limit is **20 MB** per file (current build is ~2 MB PPTX / ~1 MB PDF).

**Optional:** open the `.pptx` in PowerPoint or Google Slides to tweak fonts or add your photo; re-export PDF from PowerPoint if you prefer that layout.

## Do not use

- `*.backup.docx` — removed from repo (incorrect Reporting wording)
- `review-md/` — removed (duplicate text exports; use `corrected-md/`)
- `MCA-Final-Project-Report-FILLED.docx` — regeneratable; keep your signed copy locally
- `Project Report Writting Guidelines.pdf` — Qollabb official PDF; keep locally, not in git

## Screenshot mapping used

| Placeholder area | Image file |
|------------------|------------|
| Landing / login / HTTPS | `00_Landing-page.png` |
| Super Admin schools | `Schools-list.png` |
| Dashboards | `01_overview-dashboard-*.png` |
| Fees | `02_fees-*.png` |
| Reports | `02_reports-*.png`, `School-Reports-teacher.png` |
| Tenant isolation | `tenant-isolation-sha-students.png`, `tenant-isolation-shb-students.png` |
| Test output | `php-artisan-test-394-passed.png` (generated during build) |
