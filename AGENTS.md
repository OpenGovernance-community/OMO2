# Project Rules

- All text files must use UTF-8 without BOM.
- All text files must use LF line endings.
- Never add invisible characters such as BOM, zero-width spaces, or directional markers.
- Default to ASCII only for code, comments, and string literals unless the user explicitly asks for non-ASCII content.
- Keep UTF-8 intact across the project.
- Keep French accents in user-visible strings when the product copy is meant to display them.
- Do not replace intended visible accents with ASCII unless explicitly requested.
- Before adding page-local CSS for panels, titles, buttons, form fields, or accordions, first check whether an existing generic primitive in `/common/assets/components.css` should be reused or extended.
- Prefer `generic-section`, `generic-soft-panel`, `generic-hero-panel`, `generic-card-title`, `generic-action-button`, `generic-form-control`, and `generic-accordion` over duplicating the same structure with local selectors.
- When a page directly combines shared design tokens like border, radius, surface, spacing, and text styles in a repeated pattern, stop and consider creating or extending a generic reusable object instead of duplicating the CSS.
