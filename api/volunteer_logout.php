<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
unset($_SESSION['responder_id']);

json_response(['ok' => true]);
