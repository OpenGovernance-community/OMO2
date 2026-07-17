# Document API structure

The shared document lifecycle remains at this directory level (`create.php`, `save.php`, `detail.php`, visibility and sharing endpoints).

Type-specific endpoints are grouped by document type:

- `pv/`: PV editor, synchronization actions, rendering helpers and PDF export.
- `html/`: HTML content assistance endpoints (rewrite, summarize and audio transcription).
- `upload/`: stored-file download endpoint.
- `url/`: reserved for future external-link-specific behavior. External links currently use only the shared create/save lifecycle.

Former flat entry points remain as compatibility wrappers. New links should target the typed paths directly.
