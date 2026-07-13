<?php
$pageTitle = $pageTitle ?? 'Bean There';
$assetPrefix = $assetPrefix ?? 'assets';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          espresso: '#16100b',
          roast: '#241a12',
          bean: '#3a2a1a',
          crema: '#ede4d3',
          foam: '#9c8b74',
          caramel: '#c49b63'
        },
        fontFamily: {
          sans: ['Poppins', 'sans-serif']
        }
      }
    }
  }
</script>
<link rel="stylesheet" href="<?= $assetPrefix ?>/style.css">
