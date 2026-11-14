# Document API structure

The shared document lifecycle remains at this directory level (`create.php`, `save.php`, `detail.php`, visibility and sharing endpoints).

Type-specific endpoints are grouped by document type:

- `pv/`: PV editor, synchronization actions, rendering helpers and PDF export.
- `html/`: HTML content assistance endpoints (rewrite, summarize and audio transcription).
- `upload/`: stored-file download endpoint.
- `url/`: reserved for future external-link-specific behavior. External links currently use only the shared create/save lifecycle.

Former flat entry points remain as compatibility wrappers. New links should target the typed paths directly.

The standalone reader uses `/doc/{id}/{code}` with an eight-character alphanumeric code derived from the document's dedicated `readercode`. Existing `/omo/document.php?code=...` links remain supported. The code identifies the document without granting additional visibility. Reader downloads and the Collabora launcher accept the document id and short code, as well as the legacy long code. Collabora reader sessions carry a signed `reader` flag; WOPI rechecks visibility on each request and denies document writes even when the token belongs to an editor. Apply `2026-11-12-02-document-reader-code.sql` before deploying these routes.

External shares use the existing `DocumentShareLink` records and long random tokens, with independent expiration, password and revocation. Apply `2026-11-14-01-document-share-edit.sql` before publishing. The `allow_edit` option defaults to false and only applies to Collabora files, Etherpad, EtherCalc and SpaceDeck. HTML memos, PVs and other files remain read-only; existing PV participation links keep their separate workflow. Management requires document management rights, while other readers can still copy the short OMO link. Share pages use the same full-window reader as short links. Both structure and document share management reuse `/common/share/popup.css` and the `ShareLink` dbObject base.

Browser requests verify password approval against the current link fingerprint. Downloads cannot access archived versions. Collabora and SpaceDeck carry signed document/link/fingerprint grants and recheck them server-side; the OMO viewer's personal editing permissions never upgrade a read-only share. Collabora saves use the existing storage synchronization path with an anonymous last editor rather than impersonating the link creator. No Collabora or SpaceDeck server image change is needed. Etherpad sessions belong to a dedicated per-link author, expire no later than the link, and are deleted when the link is changed or revoked. Etherpad itself requires no update.

EtherCalc shares require the patched worker from `docker/ethercalc/Dockerfile`. Rebuild/restart that service when deploying, retaining its volumes and the existing EtherCalc key on both services. Standalone installations must include `standalone/bin/patch-share-access.cjs`; `start.sh` applies it to both the worker bundle and its workerd network configuration. The callback is the only added outbound request, and its URL is signed with the server key before any fetch. See the [workerd network configuration](https://github.com/cloudflare/workerd/blob/main/src/workerd/server/workerd.capnp) for public/private callback connectivity.

Configure `ETHERCALC_OMO_ACCESS_URL` in the OMO PHP environment to the absolute `/omo/api/documents/ethercalc/access.php` URL reachable **from EtherCalc**. Local Compose supplies `http://app/omo/api/documents/ethercalc/access.php`; production must use its own reachable URL, without redirects and with a trusted TLS certificate when using HTTPS. This server-controlled URL must never come from a visitor. Shared editing fails closed when the callback is absent/unreachable or the worker is unpatched.

The EtherCalc worker revalidates shared credentials for incoming messages and outgoing broadcasts, prevents permanent credential disclosure through `/edit`, `/app` or peer messages, and requires native authorization for room exports and REST operations. Share credentials cannot delete or replace a sheet, submit to sibling sheets or introduce new cross-sheet references. Existing cross-sheet references remain readable as part of the shared document. The old unauthenticated legacy socket transport is disabled; the current browser shim uses the native WebSocket transport. Read-only sheets, including normal OMO read-only access, are rendered through OMO without disclosing a native room credential. OMO creates sheets via authenticated PUT and signs its own snapshot/export requests.

Version history uses `Document::supportsVersionHistory()`, the `history_enabled` flag, and independent `DocumentVersion` snapshots. Uploaded files (including Collabora office documents) support automatic snapshots on replacement/removal and manual snapshots through `history_action.php`. Collabora autosaves only update the current file. To support another content type, extend the capability and snapshot adapter together; keep archived content separate from editable documents and authorize downloads through the parent document.
