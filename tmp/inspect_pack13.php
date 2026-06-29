<?php
require __DIR__ . '/../db/connection.php';
$pdo = getPDO();
$queries = [
  'pack_link' => "SELECT * FROM organization_parcours WHERE IDorganization = 2 AND IDparcours = 13",
  'pack_children' => "SELECT * FROM parcours_parcours WHERE IDparcours_parent = 13 ORDER BY COALESCE(position, id), id",
  'direct_child_links_org2' => "SELECT op.* FROM organization_parcours op INNER JOIN parcours_parcours pp ON pp.IDparcours_child = op.IDparcours WHERE op.IDorganization = 2 AND pp.IDparcours_parent = 13 ORDER BY op.IDparcours",
  'children_meta' => "SELECT p.id, p.title, p.IDorganization, p.IDapplication, COALESCE(p.ispack,0) AS ispack FROM parcours p INNER JOIN parcours_parcours pp ON pp.IDparcours_child = p.id WHERE pp.IDparcours_parent = 13 ORDER BY COALESCE(pp.position, pp.id), pp.id"
];
foreach ($queries as $label => $sql) {
  echo "== $label ==\n";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  if (!$rows) {
    echo "(empty)\n";
    continue;
  }
  foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
  }
}
?>
