<?php
require_once __DIR__ . '/bootstrap.php';
usleep(300000);

$showDocuments = commonGetCurrentUserId() > 0;
?>

<div class="menu-item"
     data-hash="">

    <span class="icon">
        <img src="images/tools/connection.png" class="icon-img">
    </span>
    <span class="label">Structure</span>
</div>

<div class="menu-item"
     data-drawer="drawer_projects"
     data-url="api/getProjects.php"
     data-hash="projects">

    <span class="icon">
        <img src="images/tools/product.png" class="icon-img">
    </span>
    <span class="label">Projets</span>
</div>

<div class="menu-item"
     data-drawer="drawer_policy"
     data-url="api/getPolicy.php"
     data-hash="policy">

    <span class="icon">
        <img src="images/tools/policy.png" class="icon-img">
    </span>
    <span class="label">Règlement</span>
</div>

<div class="menu-item"
     data-drawer="drawer_checklists"
     data-url="api/getChecklists.php"
     data-hash="checklists">

    <span class="icon">
        <img src="images/tools/bucket-list.png" class="icon-img">
    </span>
    <span class="label">Checklistes</span>
</div>

<div class="menu-item"
     data-drawer="drawer_stats"
     data-url="api/getStat.php"
     data-hash="stats">

    <span class="icon">
        <img src="images/tools/stats.png" class="icon-img">
    </span>
    <span class="label">Indicateurs</span>
</div>

<?php if ($showDocuments): ?>
<div class="menu-item"
     data-drawer="drawer_documents"
     data-url="api/documents/index.php"
     data-hash="documents">

    <span class="icon">
        <img src="images/tools/documents-folder.png" class="icon-img">
    </span>
    <span class="label">Documents</span>
</div>
<?php endif; ?>
