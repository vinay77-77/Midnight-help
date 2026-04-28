<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

// Optional query params
$onlyVerified = (int)($_GET['verified'] ?? -1); // -1 = all, 0/1 filter
$limit = (int)($_GET['limit'] ?? 200);
if ($limit <= 0) $limit = 200;
if ($limit > 500) $limit = 500;

$where = [];
$types = '';
$params = [];

if ($onlyVerified === 0 || $onlyVerified === 1) {
  $where[] = 'r.verified = ?';
  $types .= 'i';
  $params[] = $onlyVerified;
}

$whereSql = '';
if (!empty($where)) $whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
  SELECT
    r.responder_id,
    r.name,
    r.email,
    r.phone,
    r.availability_status,
    r.verified,
    r.is_active,
    r.created_at,
    r.updated_at,
    GROUP_CONCAT(rs.service_type ORDER BY rs.service_type SEPARATOR ',') AS services_csv
  FROM responders r
  LEFT JOIN responder_services rs ON rs.responder_id = r.responder_id
  $whereSql
  GROUP BY r.responder_id
  ORDER BY r.created_at DESC
  LIMIT ?
";

$types .= 'i';
$params[] = $limit;

$stmt = db()->prepare($sql);
if ($types !== '') {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$volunteers = [];
while ($row = $res->fetch_assoc()) {
  $csv = (string)($row['services_csv'] ?? '');
  $services = $csv !== '' ? explode(',', $csv) : [];
  $volunteers[] = [
    'responderId' => (int)$row['responder_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
    'phone' => (string)$row['phone'],
    'availabilityStatus' => (string)$row['availability_status'],
    'verified' => (int)$row['verified'] === 1,
    'active' => (int)$row['is_active'] === 1,
    'services' => $services,
    'createdAt' => (string)$row['created_at'],
    'updatedAt' => (string)$row['updated_at'],
  ];
}

json_response(['ok' => true, 'volunteers' => $volunteers]);

