<?php
// LMS pages also run inside frames without a topbar to provide these assets.
?>
<?= commonStylesheetTags('/common/notifications/notifications.css') ?>
<script src="<?= commonAssetUrl('/common/notifications/notifications.js') ?>"></script>
<script>
if (typeof window.omoNotify !== 'function') {
    window.omoNotify = function (message, type, options) {
        return window.commonNotify(message, type, options);
    };
}
</script>
