<?php
require_once __DIR__ . '/../theme.php';
$htmlTheme = current_theme();
$htmlAccent = current_accent();
?>
<html lang="en" data-theme="<?= htmlspecialchars($htmlTheme) ?>"<?= $htmlAccent !== null ? ' style="--c-caramel: ' . accent_rgb_triplet($htmlAccent) . '"' : '' ?>>
