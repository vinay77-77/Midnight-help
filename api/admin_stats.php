<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

$db = db();

// Total users
$res = $db->query('SELECT COUNT(*) AS c FROM users');
$totalUsers = (int)($res->fetch_assoc()['c'] ?? 0);

// Total volunteers
$res = $db->query('SELECT COUNT(*) AS c FROM responders');
$totalVolunteers = (int)($res->fetch_assoc()['c'] ?? 0);

// Volunteers currently online (available)
$res = $db->query("SELECT COUNT(*) AS c FROM responders WHERE availability_status = 'available'");
$onlineVolunteers = (int)($res->fetch_assoc()['c'] ?? 0);

// Active requests (pending, accepted, in_progress)
$res = $db->query("SELECT COUNT(*) AS c FROM requests WHERE status IN ('pending','accepted','in_progress')");
$activeRequests = (int)($res->fetch_assoc()['c'] ?? 0);

// SOS alerts (sos service type still active)
$res = $db->query("SELECT COUNT(*) AS c FROM requests WHERE service_type = 'sos' AND status IN ('pending','accepted','in_progress')");
$sosAlerts = (int)($res->fetch_assoc()['c'] ?? 0);

// Total requests today
$res = $db->query("SELECT COUNT(*) AS c FROM requests WHERE DATE(created_at) = CURDATE()");
$requestsToday = (int)($res->fetch_assoc()['c'] ?? 0);

// Resolved requests today
$res = $db->query("SELECT COUNT(*) AS c FROM requests WHERE status = 'completed' AND DATE(updated_at) = CURDATE()");
$resolvedToday = (int)($res->fetch_assoc()['c'] ?? 0);

// Pending verification (unverified responders)
$res = $db->query('SELECT COUNT(*) AS c FROM responders WHERE verified = 0');
$pendingVerification = (int)($res->fetch_assoc()['c'] ?? 0);

json_response([
  'ok' => true,
  'stats' => [
    'totalUsers' => $totalUsers,
    'totalVolunteers' => $totalVolunteers,
    'onlineVolunteers' => $onlineVolunteers,
    'activeRequests' => $activeRequests,
    'sosAlerts' => $sosAlerts,
    'requestsToday' => $requestsToday,
    'resolvedToday' => $resolvedToday,
    'pendingVerification' => $pendingVerification,
  ],
]);

