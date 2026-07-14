<?php

session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/get_recommendation.php';
require_once __DIR__ . '/../src/ollama.php';
require_once __DIR__ . '/../src/rate_limit.php';

const CHAT_MESSAGE_MAX_LENGTH = 300;
const CHAT_MAX_PER_MINUTE = 10;
const CHAT_WINDOW_SECONDS = 60;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['reply' => 'Send me a message and I will pick a drink for you.']);
  exit;
}

csrf_verify();

$message = trim((string)($_POST['message'] ?? ''));
if ($message === '') {
  echo json_encode(['reply' => 'Tell me what you feel like — sweet, bitter, iced, budget, anything.']);
  exit;
}
$message = mb_substr($message, 0, CHAT_MESSAGE_MAX_LENGTH);

// Over the limit the answer still comes, but from the rule-based path only —
// the model is the expensive part worth protecting.
$sessionIdentifier = 'chat:' . session_id();
$ipIdentifier = rate_limit_ip_identifier('chat');
$throttled = rate_limit_check($conn, $sessionIdentifier) !== null
  || rate_limit_check($conn, $ipIdentifier) !== null;

rate_limit_record($conn, $sessionIdentifier, CHAT_MAX_PER_MINUTE, CHAT_WINDOW_SECONDS);
rate_limit_record($conn, $ipIdentifier, CHAT_MAX_PER_MINUTE, CHAT_WINDOW_SECONDS);

$drinks = fetch_menu_drinks($conn);
$conn->close();

if (!$drinks) {
  echo json_encode(['reply' => 'Our drinks are all sold out right now — check back in a bit.']);
  exit;
}

$recommendation = null;
$source = 'fallback';

$fromModel = $throttled ? null : ollama_recommend($drinks, $message);
if ($fromModel !== null) {
  $drink = find_drink_by_id($drinks, $fromModel['drink_id']);
  if ($drink !== null) {
    $recommendation = ['drink' => $drink, 'reason' => $fromModel['reason']];
    $source = 'model';
  }
}

if ($recommendation === null) {
  $recommendation = rule_based_recommend($drinks, $message);
}

$drink = $recommendation['drink'];

// nosemgrep: php.lang.security.injection.echoed-request.echoed-request -- JSON API response with application/json content type, not HTML output
echo json_encode([
  'reply' => $recommendation['reason'],
  'source' => $source,
  'drink' => [
    'id' => (int)$drink['id'],
    'name' => $drink['name'],
    'description' => $drink['description'] ?? '',
    'image_path' => $drink['image_path'],
    'price' => number_format((float)$drink['price'], 2),
    'category' => $drink['category'],
  ],
]);
