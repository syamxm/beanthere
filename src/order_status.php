<?php

// The two happy paths, plus a Cancelled terminal reachable from any live state.
// cron (scripts/update_order_status.php), the admin orders page and the barista
// board all read their rules from here so they can never disagree.
const PICKUP_FLOW = ['Order Received', 'Preparing', 'Ready for Pickup', 'Done Pickup'];
const DELIVERY_FLOW = ['Order Received', 'Preparing', 'Out for Delivery', 'Delivered'];
const ORDER_CANCELLED = 'Cancelled';

function order_flow(string $delivery): array
{
  return strtolower($delivery) === 'delivery' ? DELIVERY_FLOW : PICKUP_FLOW;
}

function order_is_terminal(string $delivery, string $status): bool
{
  if ($status === ORDER_CANCELLED) {
    return true;
  }
  $flow = order_flow($delivery);
  return $status === end($flow);
}

/** The single status a group advances to next, or null if already terminal. */
function order_next_status(string $delivery, string $current): ?string
{
  $flow = order_flow($delivery);
  $i = array_search($current, $flow, true);
  if ($i === false || $i === count($flow) - 1) {
    return null;
  }
  return $flow[$i + 1];
}

/** An admin may advance one step forward, or cancel anything not yet terminal. */
function order_can_transition(string $delivery, string $from, string $to): bool
{
  if (order_is_terminal($delivery, $from)) {
    return false;
  }
  if ($to === ORDER_CANCELLED) {
    return true;
  }
  return $to === order_next_status($delivery, $from);
}
