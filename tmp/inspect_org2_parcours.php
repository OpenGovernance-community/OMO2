<?php
require __DIR__ . '/../db/connection.php';
$pdo = getPDO();
$sql = "SELECT p.id, p.title, COALESCE(p.ispack,0) AS ispack, op.position, op.everybody, IFNULL(op.anonymous,0) AS anonymous FROM organization_parcours op INNER JOIN parcours p ON p.id = op.IDparcours WHERE op.IDorganization = 2 ORDER BY op.position ASC, p.title ASC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
  echo "(empty)\n";
} else {
  foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
  }
}
?>
