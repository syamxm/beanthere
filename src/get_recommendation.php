<?php

function get_recommendation(array $answers): array
{
  global $conn;

  $conditions = [];
  $params = [];

  if (!empty($answers['roast'])) {
    $conditions[] = "roast_level = ?";
    $params[] = $answers['roast'];
  }

  if (!empty($answers['caffeine'])) {
    $conditions[] = "caffeine_level = ?";
    $params[] = $answers['caffeine'];
  }

  $jsonFields = [
    'flavour' => 'flavour_profile',
    'currentMood' => 'bestMood',
    'currentWeather' => 'bestWeather',
  ];

  foreach ($jsonFields as $key => $column) {
    if (empty($answers[$key])) {
      continue;
    }
    $subconditions = [];
    foreach (array_map('trim', explode(',', $answers[$key])) as $value) {
      if ($value === '') {
        continue;
      }
      $subconditions[] = "JSON_CONTAINS($column, ?)";
      $params[] = json_encode($value);
    }
    if ($subconditions) {
      $conditions[] = '(' . implode(' OR ', $subconditions) . ')';
    }
  }

  if (!$conditions) {
    return [];
  }

  $sql = "SELECT * FROM menu_items WHERE " . implode(' AND ', $conditions);
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(str_repeat('s', count($params)), ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  $recommendations = [];
  while ($row = $result->fetch_assoc()) {
    $recommendations[] = $row;
  }
  $stmt->close();

  return $recommendations;
}
