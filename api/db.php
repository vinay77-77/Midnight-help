<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// XAMPP defaults: user=root, password="".
// If your MySQL has a password, set it here.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'midnight_help';
const DB_USER = 'root';
const DB_PASS = '';

function db(): mysqli {
  static $conn = null;
  if ($conn instanceof mysqli) return $conn;

  try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    return $conn;
  } catch (mysqli_sql_exception $e) {
    // Always return JSON (these files are API endpoints).
    json_response([
      'ok' => false,
      'error' => 'Database connection failed',
      'hint' => 'Check api/db.php DB_HOST/DB_NAME/DB_USER/DB_PASS and import db/schema.sql into MySQL (phpMyAdmin).',
      'details' => $e->getMessage(),
    ], 500);
  }
}

function json_response(array $data, int $statusCode = 200): void {
  http_response_code($statusCode);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data);
  exit;
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

function require_post(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
  }
}

