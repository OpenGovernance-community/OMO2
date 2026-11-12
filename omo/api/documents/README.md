# Document API structure

The shared document lifecycle remains at this directory level (`create.php`, `save.php`, `detail.php`, visibility and sharing endpoints).

Type-specific endpoints are grouped by document type:

- `pv/`: PV editor, synchronization actions, rendering helpers and PDF export.
- `html/`: HTML content assistance endpoints (rewrite, summarize and audio transcription).
- `upload/`: stored-file download endpoint.
- `url/`: reserved for future external-link-specific behavior. External links currently use only the shared create/save lifecycle.

Former flat entry points remain as compatibility wrappers. New links should target the typed paths directly.

The standalone reader uses `/omo/document.php?code=...` with a dedicated random `readercode`, independent of memo access codes. This code identifies the document without granting additional visibility. Reader downloads and the Collabora launcher require the same code. Collabora reader sessions carry a signed `reader` flag; WOPI rechecks visibility on each request and denies document writes even when the token belongs to an editor. Apply `2026-11-12-02-document-reader-code.sql` before deploying these routes.

Version history uses `Document::supportsVersionHistory()`, the `history_enabled` flag, and independent `DocumentVersion` snapshots. Uploaded files (including Collabora office documents) support automatic snapshots on replacement/removal and manual snapshots through `history_action.php`. Collabora autosaves only update the current file. To support another content type, extend the capability and snapshot adapter together; keep archived content separate from editable documents and authorize downloads through the parent document.
