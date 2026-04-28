<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$rid = (int)($_SESSION['responder_id'] ?? 0);
if ($rid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$db = db();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) AS c FROM assignments WHERE responder_id = ? AND status = 'completed'");
$stmt->bind_param('i', $rid);
$stmt->execute();
$casesHandled = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM assignments WHERE responder_id = ? AND status IN ('assigned','accepted')");
$stmt->bind_param('i', $rid);
$stmt->execute();
$activeCases = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM assignments WHERE responder_id = ? AND assigned_time >= (NOW() - INTERVAL 7 DAY)");
$stmt->bind_param('i', $rid);
$stmt->execute();
$thisWeek = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);

$stmt = $db->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS n FROM ratings WHERE responder_id = ?');
$stmt->bind_param('i', $rid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc() ?: [];
$ratingAvg = $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 2) : null;
$ratingCount = (int)($row['n'] ?? 0);

// Active case (latest accepted/assigned)
$sqlActive = "
  SELECT
    a.assignment_id,
    a.status AS assignment_status,
    a.assigned_time,
    r.request_id,
    r.service_type,
    r.status AS request_status,
    r.location_text,
    u.name AS user_name,
    u.phone AS user_phone
  FROM assignments a
  JOIN requests r ON r.request_id = a.request_id
  JOIN users u ON u.user_id = r.user_id
  WHERE a.responder_id = ? AND a.status IN ('assigned','accepted')
  ORDER BY a.assigned_time DESC
  LIMIT 1
";
$stmt = $db->prepare($sqlActive);
$stmt->bind_param('i', $rid);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();

$activeCase = null;
if ($active) {
  $activeCase = [
    'assignmentId' => (int)$active['assignment_id'],
    'assignmentStatus' => (string)$active['assignment_status'],
    'assignedTime' => (string)$active['assigned_time'],
    'requestId' => (int)$active['request_id'],
    'serviceType' => (string)$active['service_type'],
    'requestStatus' => (string)$active['request_status'],
    'locationText' => (string)($active['location_text'] ?? ''),
    'userName' => (string)$active['user_name'],
    'userPhone' => (string)$active['user_phone'],
  ];
}

// Recent completed cases
$sqlRecent = "
  SELECT
    r.request_id,
    r.service_type,
    r.updated_at,
    u.name AS user_name,
    rt.rating
  FROM assignments a
  JOIN requests r ON r.request_id = a.request_id
  JOIN users u ON u.user_id = r.user_id
  LEFT JOIN ratings rt ON rt.request_id = r.request_id
  WHERE a.responder_id = ? AND a.status = 'completed'
  ORDER BY r.updated_at DESC
  LIMIT 5
";
$stmt = $db->prepare($sqlRecent);
$stmt->bind_param('i', $rid);
$stmt->execute();
$res = $stmt->get_result();
$recent = [];
while ($r = $res->fetch_assoc()) {
  $recent[] = [
    'requestId' => (int)$r['request_id'],
    'serviceType' => (string)$r['service_type'],
    'time' => (string)$r['updated_at'],
    'userName' => (string)$r['user_name'],
    'rating' => $r['rating'] !== null ? (int)$r['rating'] : null,
  ];
}

json_response([
  'ok' => true,
  'stats' => [
    'casesHandled' => $casesHandled,
    'ratingAvg' => $ratingAvg,
    'ratingCount' => $ratingCount,
    'activeCases' => $activeCases,
    'thisWeek' => $thisWeek,
  ],
  'activeCase' => $activeCase,
  'recentCases' => $recent,
]);

