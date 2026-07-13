<?php

const OLLAMA_TIMEOUT_SECONDS = 10;
const OLLAMA_REASON_MAX_LENGTH = 240;

/**
 * Asks the local Ollama server to pick one drink from the given menu.
 * Returns ['drink_id' => int, 'reason' => string], or null when the server is
 * unreachable, slow, or replies with anything other than the expected JSON.
 */
function ollama_recommend(array $drinks, string $message): ?array
{
  $baseUrl = rtrim((string)getenv('OLLAMA_URL'), '/');
  $model = (string)getenv('OLLAMA_MODEL');
  if ($baseUrl === '' || $model === '' || !$drinks) {
    return null;
  }

  $payload = [
    'model' => $model,
    'stream' => false,
    'format' => 'json',
    'options' => ['temperature' => 0.2],
    'messages' => [
      ['role' => 'system', 'content' => ollama_system_prompt($drinks)],
      ['role' => 'user', 'content' => $message],
    ],
  ];

  $raw = ollama_post($baseUrl . '/api/chat', $payload);
  if ($raw === null) {
    return null;
  }

  $envelope = json_decode($raw, true);
  $content = $envelope['message']['content'] ?? null;
  if (!is_string($content)) {
    return null;
  }

  $answer = json_decode($content, true);
  if (!is_array($answer)) {
    return null;
  }

  $drinkId = filter_var($answer['drink_id'] ?? null, FILTER_VALIDATE_INT);
  $reason = trim((string)($answer['reason'] ?? ''));
  if ($drinkId === false || $reason === '') {
    return null;
  }

  return [
    'drink_id' => $drinkId,
    'reason' => mb_substr($reason, 0, OLLAMA_REASON_MAX_LENGTH),
  ];
}

function ollama_system_prompt(array $drinks): string
{
  $lines = [];
  foreach ($drinks as $drink) {
    $flavours = json_decode((string)$drink['flavour_profile'], true);
    $lines[] = sprintf(
      'id=%d | %s | RM%s | %s | roast: %s | caffeine: %s | flavour: %s | %s',
      $drink['id'],
      $drink['name'],
      number_format((float)$drink['price'], 2),
      $drink['description'] !== null && $drink['description'] !== '' ? $drink['description'] : 'no description',
      $drink['roast_level'] ?: 'n/a',
      $drink['caffeine_level'] ?: 'n/a',
      is_array($flavours) && $flavours ? implode(', ', $flavours) : 'n/a',
      $drink['drink_type'] ?: 'n/a'
    );
  }

  return "You are the barista at Bean There, a coffee shop. Recommend exactly one drink from the menu below.\n\n"
    . "MENU:\n" . implode("\n", $lines) . "\n\n"
    . "Rules:\n"
    . "- Only recommend a drink from the menu above. Never invent a drink or an id.\n"
    . "- If nothing matches perfectly, pick the closest drink on the menu anyway.\n"
    . "- Reply with JSON only, in this exact shape: {\"drink_id\": <id from the menu>, \"reason\": \"<one friendly sentence, max 25 words>\"}\n"
    . "- The reason must mention why the drink fits what the customer asked for.";
}

function ollama_post(string $url, array $payload): ?string
{
  $curl = curl_init($url);
  curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => OLLAMA_TIMEOUT_SECONDS,
    CURLOPT_CONNECTTIMEOUT => 2,
  ]);

  $body = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
  curl_close($curl);

  if ($body === false || $status !== 200) {
    return null;
  }
  return $body;
}
