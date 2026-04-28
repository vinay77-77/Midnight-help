<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

$limit = (int)($_GET['limit'] ?? 20);
if ($limit <= 0) $limit = 20;
if ($limit > 50) $limit = 50;

$db = db();

$events = [];

// SOS alerts
$res = $db->query("SELECT r.request_id, r.location_text, r.created_at, u.name AS user_name FROM requests r JOIN users u ON u.user_id = r.user_id WHERE r.service_type = 'sos' ORDER BY r.created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
  $events[] = [
    'type' => 'sos',
    'title' => 'SOS Alert',
    'text' => escapeHtml((string)$row['user_name']) . ' triggered emergency' . ($row['location_text'] ? ' at ' . escapeHtml((string)$row['location_text']) : ''),
    'time' => (string)$row['created_at'],
  ];
}

// New requests
$res = $db->query("SELECT r.request_id, r.service_type, r.status, r.created_at, u.name AS user_name, resp.name AS responder_name FROM requests r JOIN users u ON u.user_id = r.user_id LEFT JOIN assignments a ON a.request_id = r.request_id AND a.status IN ('assigned','accepted') LEFT JOIN responders resp ON resp.responder_id = a.responder_id ORDER BY r.created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
  $assigned = $row['responder_name'] ? ' (' . escapeHtml((string)$row['responder_name']) . ')' : ' (unassigned)';
  $events[] = [
    'type' => 'request',
    'title' => 'New Request',
    'text' => serviceLabel((string)$row['service_type']) . ' requested by ' . escapeHtml((string)$row['user_name']) . $assigned,
    'time' => (string)$row['created_at'],
  ];
}

// New users
$res = $db->query("SELECT name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
  $events[] = [
    'type' => 'user',
    'title' => 'New User',
    'text' => escapeHtml((string)$row['name']) . ' registered',
    'time' => (string)$row['created_at'],
  ];
}

// Completed cases
$res = $db->query("SELECT r.request_id, r.service_type, r.updated_at, u.name AS user_name, resp.name AS responder_name FROM requests r JOIN users u ON u.user_id = r.user_id LEFT JOIN assignments a ON a.request_id = r.request_id AND a.status = 'completed' LEFT JOIN responders resp ON resp.responder_id = a.responder_id WHERE r.status = 'completed' ORDER BY r.updated_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
  $volunteer = $row['responder_name'] ? escapeHtml((string)$row['responder_name']) : 'A volunteer';
  $events[] = [
    'type' => 'completed',
    'title' => 'Case Completed',
    'text' => $volunteer . ' resolved ' . serviceLabel((string)$row['service_type']) . ' for ' . escapeHtml((string)$row['user_name']),
    'time' => (string)$row['updated_at'],
  ];
}

// Volunteers who came online recently (availability_status = available, ordered by updated_at)
$res = $db->query("SELECT name, updated_at FROM responders WHERE availability_status = 'available' ORDER BY updated_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
  $events[] = [
    'type' => 'volunteer',
    'title' => 'Volunteer Online',
    'text' => escapeHtml((string)$row['name']) . ' came online',
    'time' => (string)$row['updated_at'],
  ];
}

// Sort all events by time descending
usort($events, function ($a, $b) {
  return strcmp($b['time'], $a['time']);
});

$events = array_slice($events, 0, $limit);

json_response(['ok' => true, 'events' => $events]);

function escapeHtml(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function serviceLabel(string $type): string {
  $map = ['elderly' => 'Elderly care', 'vehicle' => 'Vehicle repair', 'driving' => 'Driving assist', 'sos' => 'SOS'];
  return $map[$type] ?? $type;
}

