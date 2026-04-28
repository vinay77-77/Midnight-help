<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$rid = (int)($_SESSION['responder_id'] ?? 0);
if ($rid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$limit = (int)($_GET['limit'] ?? 50);
if ($limit <= 0) $limit = 50;
if ($limit > 200) $limit = 200;

$db = db();

// Only show requests matching volunteer services + not already assigned.
$sql = "
  SELECT
    r.request_id,
    r.service_type,
    r.description,
    r.location_text,
    r.created_at,
    u.name AS user_name,
    u.phone AS user_phone
  FROM requests r
  JOIN users u ON u.user_id = r.user_id
  JOIN responder_services rs ON rs.responder_id = ? AND rs.service_type = r.service_type
  LEFT JOIN assignments a ON a.request_id = r.request_id AND a.status IN ('assigned','accepted')
  WHERE r.status = 'pending' AND a.assignment_id IS NULL
  ORDER BY r.created_at DESC
  LIMIT ?
";

$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $rid, $limit);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
  $rows[] = [
    'requestId' => (int)$row['request_id'],
    'serviceType' => (string)$row['service_type'],
    'description' => (string)($row['description'] ?? ''),
    'locationText' => (string)($row['location_text'] ?? ''),
    'createdAt' => (string)$row['created_at'],
    'userName' => (string)$row['user_name'],
    'userPhone' => (string)$row['user_phone'],
  ];
}

json_response(['ok' => true, 'requests' => $rows]);

