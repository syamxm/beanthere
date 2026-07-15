<?php

// Revenue reports only count groups a customer actually paid for.
const REPORT_EXCLUDED_STATUSES = ['Awaiting Payment', 'Payment Failed', 'Cancelled'];

/** Validated [from, to] dates (Y-m-d) from ?from/?to, defaulting to the last 30 days. */
function report_range_from_get(): array
{
  $from = report_valid_date($_GET['from'] ?? '') ?? date('Y-m-d', strtotime('-29 days'));
  $to = report_valid_date($_GET['to'] ?? '') ?? date('Y-m-d');
  if ($from > $to) {
    [$from, $to] = [$to, $from];
  }
  return [$from, $to];
}

function report_valid_date(string $value): ?string
{
  $parsed = DateTime::createFromFormat('Y-m-d', $value);
  return ($parsed && $parsed->format('Y-m-d') === $value) ? $value : null;
}
