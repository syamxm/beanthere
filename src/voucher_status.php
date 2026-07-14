<?php

const VOUCHER_STATUSES = ['active', 'expired', 'disabled'];

function valid_voucher_status(?string $status): bool
{
  return in_array($status, VOUCHER_STATUSES, true);
}
