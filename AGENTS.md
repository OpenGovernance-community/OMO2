# Project Rules

- All text files must use UTF-8 without BOM.
- All text files must use LF line endings.
- Never add invisible characters such as BOM, zero-width spaces, or directional markers.
- Default to ASCII only for code, comments, and string literals unless the user explicitly asks for non-ASCII content.
- Keep UTF-8 intact across the project.
- Keep French accents in user-visible strings when the product copy is meant to display them.
- Do not replace intended visible accents with ASCII unless explicitly requested.
- Prefer the existing dbObject autoload over direct `require_once` of dbObject class files. Only add a direct class include when a file is intentionally isolated from the shared bootstrap that normally initializes autoloading.
- Name SQL migrations with an explicit sortable sequence when several files share the same date, using `YYYY-MM-DD-NN-description.sql`, so dependency order is guaranteed by filename sorting.
- For PHP-rendered UI translations, keep the module or page source strings close to the file that renders them, define a local `$sourceLang` array with `text` or `one`/`other` plus `context`, load a single bundle once near the top of the file, and render visible text through `t(...)` or a shared wrapper instead of hardcoding strings in markup.
- For shared JavaScript used by multiple pages, do not duplicate translated strings in every page. Prefer a dedicated PHP JSON endpoint under a shared location such as `/common/jstranslation/`, keep the JS source strings in one shared PHP file, load one bundle server-side, and let the JS fetch that payload at startup with a small local fallback only when needed.
- When several PHP files or JS endpoints share the same translation domain, extract the source language arrays and bundle-loading wrappers into a shared helper instead of duplicating them, but keep page-specific strings in the page or module that owns them.
- For PHP background workers, cron scripts, and async CLI dispatch, do not trust `PHP_BINARY` blindly on hosted environments. It may point to `php-fpm` or `php-cgi` under FPM. Prefer an explicit CLI binary, reject FPM/CGI executables for worker launchers, and add enough logging to diagnose failed async dispatch.
- Before adding page-local CSS for panels, titles, buttons, form fields, or accordions, first check whether an existing generic primitive in `/common/assets/components.css` should be reused or extended.
- Prefer `generic-section`, `generic-soft-panel`, `generic-hero-panel`, `generic-card-title`, `generic-action-button`, `generic-form-control`, and `generic-accordion` over duplicating the same structure with local selectors.
- When a page directly combines shared design tokens like border, radius, surface, spacing, and text styles in a repeated pattern, stop and consider creating or extending a generic reusable object instead of duplicating the CSS.
