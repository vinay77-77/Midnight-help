<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

session_start();
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$serviceType = (string)($body['serviceType'] ?? '');
$description = trim((string)($body['description'] ?? ''));
$urgency = trim((string)($body['urgency'] ?? ''));
$locationText = trim((string)($body['locationText'] ?? ''));
$landmark = trim((string)($body['landmark'] ?? ''));
$latitude = $body['latitude'] ?? null;
$longitude = $body['longitude'] ?? null;

$allowed = ['elderly', 'vehicle', 'driving'];
if (!in_array($serviceType, $allowed, true)) {
  json_response(['ok' => false, 'error' => 'Invalid service type'], 400);
}
if ($description === '' || strlen($description) < 5) {
  json_response(['ok' => false, 'error' => 'Description is required'], 400);
}

$lat = null;
$lng = null;
if ($latitude !== null && $latitude !== '') {
  if (!is_numeric($latitude)) json_response(['ok' => false, 'error' => 'Invalid latitude'], 400);
  $lat = (float)$latitude;
}
if ($longitude !== null && $longitude !== '') {
  if (!is_numeric($longitude)) json_response(['ok' => false, 'error' => 'Invalid longitude'], 400);
  $lng = (float)$longitude;
}

// We don't have dedicated columns for urgency/landmark in schema; append into description text.
$fullDescription = $description;
if ($urgency !== '') $fullDescription .= "\n\nUrgency: " . $urgency;
if ($landmark !== '') $fullDescription .= "\nLandmark: " . $landmark;

$db = db();
$stmt = $db->prepare(
  'INSERT INTO requests (user_id, service_type, description, location_text, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, \'pending\')'
);

// bind_param doesn't accept null for "d" reliably in all setups; use "s" and pass nulls as NULL via set to null + send types "dd" with 0.
// We'll handle nullable coords by inserting NULL when not provided.
if ($lat === null && $lng === null) {
  $stmt = $db->prepare(
    'INSERT INTO requests (user_id, service_type, description, location_text, latitude, longitude, status) VALUES (?, ?, ?, ?, NULL, NULL, \'pending\')'
  );
  $stmt->bind_param('isss', $uid, $serviceType, $fullDescription, $locationText);
} elseif ($lat !== null && $lng !== null) {
  $stmt = $db->prepare(
    'INSERT INTO requests (user_id, service_type, description, location_text, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, \'pending\')'
  );
  $stmt->bind_param('isssdd', $uid, $serviceType, $fullDescription, $locationText, $lat, $lng);
} else {
  json_response(['ok' => false, 'error' => 'Both latitude and longitude are required if providing coordinates'], 400);
}

$stmt->execute();
$requestId = (int)$db->insert_id;

json_response(['ok' => true, 'requestId' => $requestId], 201);

