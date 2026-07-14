<?php

const DEFAULT_THEME = 'dark-roast';

function theme_options(): array
{
  return [
    'dark-roast' => ['label' => 'Dark Roast', 'bg' => '#16100b', 'surface' => '#241a12', 'border' => '#3a2a1a', 'text' => '#ede4d3', 'accent' => '#c49b63'],
    'strawberry-latte' => ['label' => 'Strawberry Latte', 'bg' => '#2b1a1f', 'surface' => '#3a2229', 'border' => '#52303a', 'text' => '#f5e6ea', 'accent' => '#e8749a'],
    'morning-brew' => ['label' => 'Morning Brew', 'bg' => '#f5eede', 'surface' => '#ffffff', 'border' => '#d9c9ad', 'text' => '#2b1d10', 'accent' => '#8f5a1f'],
    'matcha' => ['label' => 'Matcha', 'bg' => '#14201a', 'surface' => '#1e2f25', 'border' => '#32493c', 'text' => '#e9f0e4', 'accent' => '#9ec97f'],
  ];
}

function valid_theme(string $theme): bool
{
  return array_key_exists($theme, theme_options());
}

function valid_accent(string $accent): bool
{
  return preg_match('/^#[0-9a-f]{6}$/i', $accent) === 1;
}

function current_theme(): string
{
  $theme = $_SESSION['theme'] ?? DEFAULT_THEME;
  return is_string($theme) && valid_theme($theme) ? $theme : DEFAULT_THEME;
}

function current_accent(): ?string
{
  $accent = $_SESSION['accent'] ?? null;
  return is_string($accent) && valid_accent($accent) ? $accent : null;
}

function accent_rgb_triplet(string $accent): string
{
  return hexdec(substr($accent, 1, 2)) . ' ' . hexdec(substr($accent, 3, 2)) . ' ' . hexdec(substr($accent, 5, 2));
}
