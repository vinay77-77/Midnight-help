<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$stmt = db()->prepare('SELECT admin_id, name, email, phone, is_active FROM admins WHERE admin_id = ? LIMIT 1');
$stmt->bind_param('i', $aid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) json_response(['ok' => false, 'error' => 'Admin not found'], 404);
if ((int)$row['is_active'] !== 1) json_response(['ok' => false, 'error' => 'Account is disabled'], 403);

json_response([
  'ok' => true,
  'admin' => [
    'adminId' => (int)$row['admin_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
    'phone' => (string)($row['phone'] ?? ''),
  ],
]);

