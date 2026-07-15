<?php

function get_setting(mysqli $conn, string $name): ?string
{
  $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ?");
  if ($stmt === false) {
    return null;
  }
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $stmt->bind_result($value);
  $found = $stmt->fetch();
  $stmt->close();
  return $found ? $value : null;
}

function set_setting(mysqli $conn, string $name, string $value): void
{
  $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE value = VALUES(value)");
  $stmt->bind_param("ss", $name, $value);
  $stmt->execute();
  $stmt->close();
}

const HOURS_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const HOURS_DAY_LABELS = [
  'mon' => 'Monday',
  'tue' => 'Tuesday',
  'wed' => 'Wednesday',
  'thu' => 'Thursday',
  'fri' => 'Friday',
  'sat' => 'Saturday',
  'sun' => 'Sunday',
];

/** Per-day schedule keyed by HOURS_DAYS; empty open/close means closed that day. */
function store_schedule(mysqli $conn): array
{
  $schedule = [];
  foreach (HOURS_DAYS as $day) {
    $schedule[$day] = [
      'open' => get_setting($conn, "hours_{$day}_open") ?? '',
      'close' => get_setting($conn, "hours_{$day}_close") ?? '',
    ];
  }
  return $schedule;
}

function hours_for_today(mysqli $conn): array
{
  return store_schedule($conn)[HOURS_DAYS[(int)date('N') - 1]];
}

function schedule_says_open(array $todayHours): bool
{
  if ($todayHours['open'] === '' || $todayHours['close'] === '') {
    return false;
  }
  $now = date('H:i');
  return $now >= $todayHours['open'] && $now < $todayHours['close'];
}

/**
 * Open/closed resolution: the manual toggle wins while the override switch is
 * on (or before any schedule is saved); otherwise today's schedule decides.
 */
function store_status(mysqli $conn): array
{
  static $status = null;
  if ($status !== null) {
    return $status;
  }

  $manualOpen = get_setting($conn, 'store_open') !== '0';
  $overrideOn = get_setting($conn, 'hours_override') === '1';
  $hoursConfigured = get_setting($conn, 'hours_configured') === '1';
  $today = $hoursConfigured ? hours_for_today($conn) : ['open' => '', 'close' => ''];

  $useManual = $overrideOn || !$hoursConfigured;
  $open = $useManual ? $manualOpen : schedule_says_open($today);

  $message = get_setting($conn, 'closed_message') ?? '';
  if (!$open && !$useManual) {
    $message = $today['open'] !== '' && $today['close'] !== ''
      ? "We're closed right now — today's hours are {$today['open']}–{$today['close']}."
      : "We're closed today — see you soon for your next cup!";
  }

  $status = [
    'open' => $open,
    'message' => $message,
    'today' => $today,
  ];

  return $status;
}
