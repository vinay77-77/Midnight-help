<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$db = db();

$tables = ['admins', 'users', 'responders', 'responder_services', 'requests', 'assignments'];
$missing = [];
foreach ($tables as $t) {
  // MariaDB doesn't support placeholders in "SHOW TABLES LIKE ?".
  // Use information_schema (works in MySQL and MariaDB).
  $stmt = $db->prepare(
    'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
  );
  $stmt->bind_param('s', $t);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res->fetch_assoc()) $missing[] = $t;
}

$res = $db->query('SELECT DATABASE() AS db');
$dbName = (string)($res->fetch_assoc()['db'] ?? '');

json_response([
  'ok' => true,
  'db' => [
    'name' => $dbName,
    'host' => DB_HOST,
  ],
  'tables' => [
    'required' => $tables,
    'missing' => $missing,
  ],
]);

