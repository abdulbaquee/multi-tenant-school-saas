# MCA Report — Bundle Assembly Order

Use this order when merging your chapter `.docx` files into **one final document** for PDF export and Qollabb upload.

---

## Recommended final document structure

### Part A — Preliminary pages (from official university/Qollabb template)

These are usually **not** in your chapter files. Copy them from the Chandigarh University / Qollabb MCA template:

1. Cover page (title, your name, enrollment, guide, university)
2. Bonafide certificate
3. Declaration
4. **Acknowledgement** ← `Step 12 Acknowledgement.docx`
5. **Abstract** ← `STEP 11 — Abstract (LAST).docx` *(always after acknowledgement, before main body)*
6. Table of Contents (auto-generated in Word)
7. List of Figures (auto-generated)
8. List of Tables (auto-generated)
9. List of Abbreviations / Symbols (if required by template)

### Part B — Main chapters (in numeric order)

10. `Chapter 1 — Introduction.docx`
11. `Chapter 2 — Literature Review and System Study.docx`
12. `Chapter 3 — System Analysis.docx` *(patched — Reporting fix applied)*
13. `Chapter 4 — System Design.docx`
14. `Chapter 5 — System Implementation.docx` *(patched — Reporting fix applied)*
15. `Chapter 6 — Testing and Validation.docx`
16. `Chapter 7 — Results and Discussion.docx`
17. `Chapter 8 — Conclusion and Future Enhancements.docx`
18. `Chapter 9 — References.docx`
19. `Chapter 10 — Appendices.docx`

---

## Fastest Word workflow (recommended)

### Option 1 — Master document merge (easiest)

1. Open the **official MCA Word template** (blank master with styles).
2. Insert preliminary pages from template.
3. For each file in order above: **Insert → Object → Text from File** (or copy-paste end of document).
4. Apply heading styles (Heading 1 for chapters, Heading 2 for sections) if paste broke styles.
5. Insert screenshots at every `[SCREENSHOT: ...]` marker.
6. Insert diagrams at every `[FIGURE: ...]` marker.
7. **References → Table of Contents → Update entire table**.
8. **File → Save As → PDF**.

### Option 2 — Pandoc (if you prefer Markdown)

```bash
cd docs/MCA-Final-Project-Report-Qollabb/corrected-md

# Merge all chapters (adjust list if you add cover pages separately)
pandoc \
  "STEP 11 — Abstract (LAST).md" \
  "Chapter 1 — Introduction.md" \
  "Chapter 2 — Literature Review and System Study.md" \
  "Chapter 3 — System Analysis.md" \
  "Chapter 4 — System Design.md" \
  "Chapter 5 — System Implementation.md" \
  "Chapter 6 — Testing and Validation.md" \
  "Chapter 7 — Results and Discussion.md" \
  "Chapter 8 — Conclusion and Future Enhancements.md" \
  "Chapter 9 — References.md" \
  "Chapter 10 — Appendices.md" \
  -o MCA-Final-Report-DRAFT.docx
```

Then open in Word, apply university template formatting, add preliminary pages, figures, and TOC.

> **Note:** Pandoc will **not** carry embedded images from your current chapter docx files. Use Option 1 if screenshots are already placed in Word.

---

## Qollabb submission bundle (separate from report PDF)

Prepare these **alongside** the report PDF:

| Item | Source |
|------|--------|
| Final report PDF | Merged Word → PDF (&lt; 20 MB) |
| Presentation PPT/PDF | Your viva deck |
| Live demo URL | `https://schoolportal.pagescorch.com` |
| GitHub URL | `https://github.com/abdulbaquee/multi-tenant-school-saas` |
| Milestone evidence | Screenshots already in Qollabb (if uploaded) |

Qollabb final submission is **one-shot** (no draft save) — have PDF, PPT, and links ready in one session.

---

## File inventory in this folder

| File | Role in bundle |
|------|----------------|
| `Step 12 Acknowledgement.docx` | Preliminary — §4 |
| `STEP 11 — Abstract (LAST).docx` | Preliminary — §5 |
| `Chapter 1` … `Chapter 10` `.docx` | Main body §10–19 |
| `*.backup.docx` | Original before patch — do not submit |
| `corrected-md/*.md` | Markdown backup / pandoc source |
| `REPORT_AUTHENTICITY_AUDIT.md` | Verification record |

---

## Quick quality gate before export

- [ ] No `[FIGURE` or `[SCREENSHOT` placeholders remain
- [ ] No `[VERIFY BEFORE SUBMIT]` in References
- [ ] Chapter 3 §3.1.11 mentions **System Operations** for logs/audit/backup
- [ ] Chapter 5 §5.11 does **not** list activity/audit/backup as report categories
- [ ] Page numbers and TOC updated
- [ ] Spell-check + grammar pass
- [ ] PDF opens correctly and file size is acceptable

When this checklist is complete, you are ready to upload to Qollabb.
