<?php
$pageTitle = $pageTitle ?? 'Bean There';
$assetPrefix = $assetPrefix ?? 'assets';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="<?= htmlspecialchars($assetPrefix) ?>/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= htmlspecialchars($assetPrefix) ?>/tailwind.css">
<link rel="stylesheet" href="<?= htmlspecialchars($assetPrefix) ?>/style.css">
