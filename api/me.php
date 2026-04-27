<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$stmt = db()->prepare('SELECT user_id, name, email, phone FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) json_response(['ok' => false, 'error' => 'User not found'], 404);

json_response([
  'ok' => true,
  'user' => [
    'userId' => (int)$row['user_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
    'phone' => (string)$row['phone'],
  ],
]);

