<?php

const MILK_SURCHARGE = 1.00;
const SYRUP_SURCHARGE = 0.50;
const TOPPING_SURCHARGE = 1.00;

/** Price of one drink: menu price plus the customisations stored on the cart row. */
function drink_unit_price(float $basePrice, ?string $milkType, ?string $syrups, ?string $toppings): float
{
  $price = $basePrice
    + (($milkType !== null && $milkType !== '' && $milkType !== 'Dairy') ? MILK_SURCHARGE : 0)
    + count_options($syrups) * SYRUP_SURCHARGE
    + count_options($toppings) * TOPPING_SURCHARGE;

  return round($price, 2);
}

function cart_line_total(float $unitPrice, int $qty): float
{
  return round($unitPrice * $qty, 2);
}

/** Cart rows store syrups/toppings as a comma-separated text list. */
function count_options(?string $list): int
{
  if ($list === null || trim($list) === '') {
    return 0;
  }
  return count(array_filter(array_map('trim', explode(',', $list)), 'strlen'));
}
