<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

$email = strtolower(trim((string)($body['email'] ?? '')));
$pass  = (string)($body['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Valid email required'], 400);
if ($pass === '') json_response(['ok' => false, 'error' => 'Password required'], 400);

$stmt = db()->prepare(
  'SELECT responder_id, name, email, password_hash, verified, is_active FROM responders WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) json_response(['ok' => false, 'error' => 'Invalid email or password'], 401);
if ((int)$row['is_active'] !== 1) json_response(['ok' => false, 'error' => 'Account is disabled'], 403);
if ((int)$row['verified'] !== 1) json_response(['ok' => false, 'error' => 'Volunteer account is not verified yet'], 403);
if (!password_verify($pass, (string)$row['password_hash'])) json_response(['ok' => false, 'error' => 'Invalid email or password'], 401);

session_start();
unset($_SESSION['admin_id'], $_SESSION['user_id']);
$_SESSION['responder_id'] = (int)$row['responder_id'];

json_response([
  'ok' => true,
  'volunteer' => [
    'responderId' => (int)$row['responder_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
  ],
]);
