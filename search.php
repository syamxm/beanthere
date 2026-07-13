<?php
session_start();
// Database connection
include "dbconn.php";

// Select roast_level and caffeine_level in addition to other fields
$sqlAllItem = "SELECT id, name, image_path, price, category, roast_level, caffeine_level FROM menu_items";
$resultAllItem = mysqli_query($conn, $sqlAllItem);

$items = [];
while ($row = mysqli_fetch_assoc($resultAllItem)) {
  $items[] = [
    'id' => $row['id'],
    'name' => $row['name'],
    'image' => $row['image_path'],
    'price' => $row['price'],
    'category' => $row['category'],
    'roast_level' => $row['roast_level'],
    'caffeine_level' => $row['caffeine_level']
  ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Search - Bean There</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="style_scrollbar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    section {
      padding: 20px 70px;
    }

    .search-bar {
      text-align: center;
      margin-bottom: 30px;
    }

    .search-bar input {
      width: 60%;
      padding: 10px;
      font-size: 16px;
      border: 1;
      border-radius: 5px;
    }

    .details {
      font-size: 14px;
      color: #f8b400;
      margin-bottom: 15px;
    }

    .go-back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background-color: #c49b63;
      color: #000;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.3s, color 0.3s;
      z-index: 50;
    }

    .go-back-btn:hover {
      background-color: #fff;
      color: #000;
    }
  </style>

</head>

<body>
  <a href="user_dashboard.php" class="go-back-btn">← Go To Main Menu</a>
  <h1>Search Our Coffee</h1>
  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search for coffee or menu...">
  </div>
  <section>
    <div class="box-container" id="results"></div>
  </section>


  <script>
    const allItems = <?php echo json_encode($items); ?>;

    const input = document.getElementById('searchInput');
    const results = document.getElementById('results');

    function displayItems(items) {
      results.innerHTML = items.map(item => {
        const formAction = "customize.php";
        const fromSection = item.category === "menu" ? "menu" : "products";
        const buttonLabel = item.category === "menu" ? "Buy" : "Add To Cart";

        const oldPrice = item.old_price ?
          `<span>RM${parseFloat(item.old_price).toFixed(2)}</span>` :
          "";

        return `
      <form action="${formAction}" method="post">
        <input type="hidden" name="id" value="${item.id}">
        <input type="hidden" name="from_section" value="${fromSection}">
        <div class="box">
          <img loading="lazy" src="${item.image}" alt="${item.name}">
          <h3>${item.name}</h3>
          <div class="price">
            RM${parseFloat(item.price).toFixed(2)}
            ${oldPrice}
          </div>
          <button type="submit" class="btn">${buttonLabel}</button>
        </div>
      </form>
    `;
      }).join('');
    }


    // Initial display
    displayItems(allItems);

    // Filter as you type
    input.addEventListener('input', () => {
      const query = input.value.trim().toLowerCase();
      const filtered = allItems.filter(item =>
        item.name.toLowerCase().includes(query)
      );
      displayItems(filtered);
    });
  </script>
</body>

</html>