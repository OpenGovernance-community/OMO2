# Document API structure

The shared document lifecycle remains at this directory level (`create.php`, `save.php`, `detail.php`, visibility and sharing endpoints).

Type-specific endpoints are grouped by document type:

- `pv/`: PV editor, synchronization actions, rendering helpers and PDF export.
- `html/`: HTML content assistance endpoints (rewrite, summarize and audio transcription).
- `upload/`: stored-file download endpoint.
- `url/`: reserved for future external-link-specific behavior. External links currently use only the shared create/save lifecycle.

Former flat entry points remain as compatibility wrappers. New links should target the typed paths directly.

Version history uses `Document::supportsVersionHistory()`, the `history_enabled` flag, and independent `DocumentVersion` snapshots. Uploaded files (including Collabora office documents) support automatic snapshots on replacement/removal and manual snapshots through `history_action.php`. Collabora autosaves only update the current file. To support another content type, extend the capability and snapshot adapter together; keep archived content separate from editable documents and authorize downloads through the parent document.
