<?php

/**
 * Keyword -> drink attribute the keyword hints at. Used by the rule-based
 * fallback that runs whenever the local model is unavailable.
 */
function recommendation_keyword_rules(): array
{
  return [
    'bitter' => ['roast_level' => 'dark'],
    'bold' => ['roast_level' => 'dark'],
    'strong' => ['roast_level' => 'dark'],
    'dark' => ['roast_level' => 'dark'],
    'light' => ['roast_level' => 'light'],
    'mild' => ['roast_level' => 'light'],
    'smooth' => ['flavour' => 'smooth'],
    'sweet' => ['flavour' => 'sweet'],
    'fruity' => ['flavour' => 'fruity'],
    'nutty' => ['flavour' => 'nutty'],
    'chocolate' => ['flavour' => 'chocolate'],
    'caramel' => ['flavour' => 'caramel'],
    'vanilla' => ['flavour' => 'vanilla'],
    'creamy' => ['flavour' => 'creamy'],
    'wake me up' => ['caffeine_level' => 'high'],
    'awake' => ['caffeine_level' => 'high'],
    'energy' => ['caffeine_level' => 'high'],
    'high caffeine' => ['caffeine_level' => 'high'],
    'low caffeine' => ['caffeine_level' => 'low'],
    'decaf' => ['caffeine_level' => 'low'],
    'sleep' => ['caffeine_level' => 'low'],
    'hot' => ['drink_type' => 'hot'],
    'warm' => ['drink_type' => 'hot'],
    'iced' => ['drink_type' => 'iced'],
    'cold' => ['drink_type' => 'iced'],
    'milk' => ['milk' => true],
    'milky' => ['milk' => true],
    'latte' => ['milk' => true],
    'black' => ['milk' => false],
    'no milk' => ['milk' => false],
  ];
}

/** In-stock drinks, with everything both the model prompt and the drink card need. */
function fetch_menu_drinks(mysqli $conn): array
{
  $sql = "SELECT id, name, description, image_path, price, category, roast_level, caffeine_level,
                 flavour_profile, drink_type, sugar_level
          FROM menu_items
          WHERE category = 'menu' AND stock > 0
          ORDER BY id";
  $result = $conn->query($sql);
  if (!$result) {
    return [];
  }

  $drinks = $result->fetch_all(MYSQLI_ASSOC);
  $result->free();
  return $drinks;
}

function find_drink_by_id(array $drinks, int $drinkId): ?array
{
  foreach ($drinks as $drink) {
    if ((int)$drink['id'] === $drinkId) {
      return $drink;
    }
  }
  return null;
}

/**
 * Closest drink on the menu for a free-text request. Never returns an empty
 * result unless the menu itself is empty, so the chat always has an answer.
 * Returns ['drink' => array, 'reason' => string] or null.
 */
function rule_based_recommend(array $drinks, string $message): ?array
{
  if (!$drinks) {
    return null;
  }

  $text = mb_strtolower($message);
  $budget = extract_budget($text);

  $affordable = $budget === null
    ? $drinks
    : array_values(array_filter($drinks, fn($drink) => (float)$drink['price'] <= $budget));
  if (!$affordable) {
    $affordable = $drinks;
    $budget = null;
  }

  $best = null;
  foreach ($affordable as $drink) {
    $scored = score_drink($drink, $text);
    $beatsBest = $best === null
      || $scored['score'] > $best['score']
      || ($scored['score'] === $best['score'] && (float)$drink['price'] < (float)$best['drink']['price']);
    if ($beatsBest) {
      $best = ['drink' => $drink, 'score' => $scored['score'], 'matches' => $scored['matches']];
    }
  }

  return [
    'drink' => $best['drink'],
    'reason' => build_reason($best['drink'], $best['matches'], $budget),
  ];
}

function score_drink(array $drink, string $text): array
{
  $flavours = drink_flavours($drink);
  $hasMilk = drink_has_milk($drink);

  $score = 0;
  $matches = [];

  foreach (recommendation_keyword_rules() as $keyword => $rule) {
    if (!str_contains($text, $keyword)) {
      continue;
    }

    foreach ($rule as $field => $wanted) {
      if ($field === 'flavour') {
        foreach ($flavours as $flavour) {
          if (str_contains($flavour, $wanted)) {
            $score += 2;
            $matches[] = $flavour;
          }
        }
      } elseif ($field === 'milk') {
        if ($hasMilk === $wanted) {
          $score += 2;
          $matches[] = $wanted ? 'milky' : 'served black';
        }
      } elseif (mb_strtolower((string)$drink[$field]) === $wanted) {
        $score += 2;
        $matches[] = match ($field) {
          'roast_level' => "$wanted roast",
          'caffeine_level' => "$wanted in caffeine",
          default => $wanted,
        };
      }
    }
  }

  foreach ($flavours as $flavour) {
    if (str_contains($text, $flavour)) {
      $score += 1;
      $matches[] = $flavour;
    }
  }

  return ['score' => $score, 'matches' => array_values(array_unique($matches))];
}

function drink_flavours(array $drink): array
{
  $flavours = json_decode((string)$drink['flavour_profile'], true);
  return is_array($flavours) ? array_map('mb_strtolower', $flavours) : [];
}

function drink_has_milk(array $drink): bool
{
  $haystack = mb_strtolower($drink['name'] . ' ' . ($drink['description'] ?? '') . ' ' . (string)$drink['flavour_profile']);
  foreach (['latte', 'cappuccino', 'macchiato', 'mocha', 'flat white', 'milk', 'creamy', 'foam'] as $needle) {
    if (str_contains($haystack, $needle)) {
      return true;
    }
  }
  return false;
}

function extract_budget(string $text): ?float
{
  if (preg_match('/(?:under|below|less than|max|budget of|cheaper than)\s*(?:rm)?\s*(\d+(?:\.\d+)?)/', $text, $match)) {
    return (float)$match[1];
  }
  if (preg_match('/rm\s*(\d+(?:\.\d+)?)\s*(?:or less|and under|max)/', $text, $match)) {
    return (float)$match[1];
  }
  return null;
}

function build_reason(array $drink, array $matches, ?float $budget): string
{
  $reason = $matches
    ? sprintf('%s is the closest match — it is %s.', $drink['name'], readable_list(array_slice($matches, 0, 3)))
    : sprintf('%s is a house favourite, so it is a safe place to start.', $drink['name']);

  if ($budget !== null) {
    $reason .= sprintf(' It also stays under RM%s.', number_format($budget, 2));
  }
  return $reason;
}

function readable_list(array $items): string
{
  if (count($items) === 1) {
    return $items[0];
  }
  $last = array_pop($items);
  return implode(', ', $items) . ' and ' . $last;
}
