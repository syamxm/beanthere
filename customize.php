<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

require_once 'dbconn.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $itemID = $_POST['id'] ?? 0;

  // Get item category from menu_items table using itemID
  $category = null;
  $categoryQuery = $conn->prepare("SELECT category FROM menu_items WHERE id = ?");
  $categoryQuery->bind_param("i", $itemID);
  $categoryQuery->execute();
  $categoryQuery->bind_result($category);
  $categoryQuery->fetch();
  $categoryQuery->close();


  $default_roast = "";
  $default_caffeine = "";
  $default_drinkType = "";
  $default_sugarLevel = "";

  if ($itemID > 0) {
    if ($category === "menu") {
      $stmt = $conn->prepare("SELECT name, drink_type, sugar_level, roast_level, caffeine_level, price FROM menu_items WHERE id = ?");
      $stmt->bind_param("i", $itemID);
      $stmt->execute();
      $stmt->bind_result($name, $drinkType, $sugarLevel, $db_roast, $db_caffeine, $price);
      if ($stmt->fetch()) {
        $default_name = $name;
        $default_drinkType = $drinkType;
        $default_sugarLevel = $sugarLevel;
        $default_roast = $db_roast;
        $default_caffeine = $db_caffeine;
        $default_price = $price;
      }
    } else if ($category === "product") {
      $stmt = $conn->prepare("SELECT name, price FROM menu_items WHERE id = ?");
      $stmt->bind_param("i", $itemID);
      $stmt->execute();
      $stmt->bind_result($name, $price);
      if ($stmt->fetch()) {
        $default_name = $name;
        $default_price = $price;
      }
    }
    $stmt->close();
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Checkout - Bean There</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fdfaf6;
      color: #4a4a4a;
      margin: 0;
      padding: 0;
    }

    .card {
      background-color: #ffffff;
      border-radius: 1rem;
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.06);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    h2,
    h3 {
      color: #5e4b3c;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #5e4b3c;
    }

    input,
    select {
      width: 100%;
      padding: 0.6rem;
      margin-top: 0.25rem;
      border: 1px solid #d4cfc9;
      border-radius: 0.5rem;
      background-color: #fdf9f3;
      color: #4a4a4a;
    }

    input:focus,
    select:focus {
      border-color: #bfa88f;
      outline: none;
      background-color: #fdf4e3;
    }

    .section-title {
      font-size: 1.25rem;
      margin-bottom: 1rem;
      border-bottom: 1px solid #eee;
      padding-bottom: 0.5rem;
    }

    .price-summary {
      font-size: 1.25rem;
      font-weight: bold;
    }

    .btn-primary {
      background-color: #8d6e63;
      color: white;
      padding: 0.8rem 1.2rem;
      border-radius: 0.5rem;
      font-weight: bold;
      width: 100%;
      transition: background-color 0.3s ease;
      margin-top: 1rem;
    }

    .btn-primary:hover {
      background-color: #6d4c41;
    }

    .inline-group label {
      display: inline-block;
      margin-right: 1rem;
    }
  </style>
</head>

<body class="bg-yellow-50 p-6 font-sans">
  <?php $fromSection = $_POST['from_section'] ?? 'menu'; ?>
  <a href="user_dashboard.php#<?= $fromSection ?>" class="fixed top-5 left-5 z-50 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-md hover:bg-gray-100 transition duration-300">
    ← Go Back
  </a>


  <form action="add_to_cart.php" method="post">
    <div class="max-w-2xl mx-auto">
      <div class="card">
        <h2 class="text-2xl font-bold mb-6 text-center">Customize Your Drink</h2>

        <!-- Drink Info -->
        <div class="mb-6">
          <label>
            <?php
            if ($category === "menu") {
              echo "Drink:";
            } else if ($category === "product") {
              echo "Product:";
            }
            ?>
          </label>
          <input type="text" name="name" value="<?= htmlspecialchars($default_name) ?>" readonly />
        </div>

        <?php if ($category === "menu"): ?>
          <!-- Drink Type -->
          <div class="mb-6">
            <label>Drink Type:</label>
            <select name="drinkType">
              <option value="Iced" <?php if ($default_drinkType == "Iced") echo "selected"; ?>>Iced</option>
              <option value="Hot" <?php if ($default_drinkType == "Hot") echo "selected"; ?>>Hot</option>
            </select>

          </div>

          <!-- Roast Level -->
          <div class="mb-6">
            <label>Roast Level:</label>
            <select name="roastLevel">
              <option value="" <?php if ($default_roast == "") echo "selected"; ?>>None</option>
              <option value="light" <?php if ($default_roast == "light") echo "selected"; ?>>Light</option>
              <option value="medium" <?php if ($default_roast == "medium") echo "selected"; ?>>Medium</option>
              <option value="dark" <?php if ($default_roast == "dark") echo "selected"; ?>>Dark</option>
            </select>
          </div>

          <!-- Caffeine -->
          <div class="mb-6">
            <label>Caffeine Level:</label>
            <select name="caffeineLevel">
              <option value="" <?php if ($default_caffeine == "") echo "selected"; ?>>None</option>
              <option value="low" <?php if ($default_caffeine == "low") echo "selected"; ?>>Low</option>
              <option value="medium" <?php if ($default_caffeine == "medium") echo "selected"; ?>>Medium</option>
              <option value="high" <?php if ($default_caffeine == "high") echo "selected"; ?>>High</option>
            </select>
          </div>

          <!-- Milk Type -->
          <div class="mb-6">
            <label>Milk Type:</label>
            <select name="milkType">
              <option value="Dairy">Dairy</option>
              <option value="Oatside">Oatside (+RM1)</option>
              <option value="Almond">Almond (+RM1)</option>
              <option value="Soy">Soy (+RM1)</option>
            </select>
          </div>

          <!-- Sugar Level -->
          <div class="mb-6">
            <label>Sugar Level:</label>
            <select name="sugarLevel">
              <option value="100%" <?php if ($default_sugarLevel == "100%") echo "selected"; ?>>100%</option>
              <option value="75%" <?php if ($default_sugarLevel == "75%") echo "selected"; ?>>75%</option>
              <option value="50%" <?php if ($default_sugarLevel == "50%") echo "selected"; ?>>50%</option>
              <option value="25%" <?php if ($default_sugarLevel == "25%") echo "selected"; ?>>25%</option>
              <option value="0%" <?php if ($default_sugarLevel == "0%") echo "selected"; ?>>0%</option>
            </select>

          </div>

          <!-- Syrups -->
          <div class="mb-6">
            <h3 class="section-title text-lg font-semibold text-gray-800 mb-2">Syrups (RM0.50 each):</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label class="flex justify-between items-center border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                <span class="text-gray-700">Vanilla</span>
                <input type="checkbox" name="syrups[]" class="syrup w-5 h-5 text-yellow-500" value="Vanilla" />
              </label>
              <label class="flex justify-between items-center border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                <span class="text-gray-700">Caramel</span>
                <input type="checkbox" name="syrups[]" class="syrup w-5 h-5 text-yellow-500" value="Caramel" />
              </label>
              <label class="flex justify-between items-center border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                <span class="text-gray-700">Hazelnut</span>
                <input type="checkbox" name="syrups[]" class="syrup w-5 h-5 text-yellow-500" value="Hazelnut" />
              </label>
            </div>
          </div>

          <!-- Toppings -->
          <div class="mb-6">
            <h3 class="section-title text-lg font-semibold text-gray-800 mb-2">Toppings (RM1.00 each):</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label class="flex justify-between items-center border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                <span class="text-gray-700">Whipped Cream</span>
                <input type="checkbox" name="toppings[]" class="topping w-5 h-5 text-yellow-500" value="Whipped Cream" />
              </label>
              <label class="flex justify-between items-center border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                <span class="text-gray-700">Espresso Jelly</span>
                <input type="checkbox" name="toppings[]" class="topping w-5 h-5 text-yellow-500" value="Expresso Jelly" />
              </label>
            </div>
          </div>
        <?php endif; ?>

        <!-- Total Price -->
        <div class="mb-4 price-summary text-right">
          Total: RM <span id="totalPrice"><?= htmlspecialchars($default_price) ?></span>
        </div>

        <div class="form-group">
          <input type="hidden" name="id" value="<?php echo $itemID ?>">
          <button type="submit" class="btn-primary">Add To Cart</button>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const basePrice = parseFloat(<?= json_encode($default_price) ?>);
      const totalPriceElem = document.getElementById("totalPrice");
      const deliveryRadios = document.querySelectorAll("input[name='deliveryOption']");
      const form = document.querySelector("form");

      const totalHiddenInput = document.createElement("input");
      totalHiddenInput.type = "hidden";
      totalHiddenInput.name = "total";
      form.appendChild(totalHiddenInput);

      // Optional elements – may not exist for "product" category
      const milkSelect = document.querySelector("select[name='milkType']");
      const syrupCheckboxes = document.querySelectorAll("input[name='syrups[]']");
      const toppingCheckboxes = document.querySelectorAll("input[name='toppings[]']");

      function calculateTotal() {
        let total = basePrice;

        // Add milk pricing if milk selection exists
        if (milkSelect) {
          const milk = milkSelect.value;
          if (milk !== "Dairy") total += 1;
        }

        // Add syrup cost
        if (syrupCheckboxes.length > 0) {
          syrupCheckboxes.forEach(cb => {
            if (cb.checked) total += 0.5;
          });
        }

        // Add topping cost
        if (toppingCheckboxes.length > 0) {
          toppingCheckboxes.forEach(cb => {
            if (cb.checked) total += 1;
          });
        }

        // Delivery charge
        /*let deliveryText = "Pickup";
        deliveryRadios.forEach(rb => {
          if (rb.checked) {
            deliveryText = rb.value;
            if (deliveryText === "Delivery") total += 3;
          }
        });*/

        // Create/update delivery hidden input
        /*let deliveryHidden = document.querySelector("input[name='delivery']");
        if (!deliveryHidden) {
          deliveryHidden = document.createElement("input");
          deliveryHidden.type = "hidden";
          deliveryHidden.name = "delivery";
          form.appendChild(deliveryHidden);
        }
        deliveryHidden.value = deliveryText;*/

        // Display total
        totalPriceElem.textContent = total.toFixed(2);
        totalHiddenInput.value = total.toFixed(2);
      }

      // Attach event listeners if those sections exist
      if (milkSelect) milkSelect.addEventListener("change", calculateTotal);
      syrupCheckboxes.forEach(cb => cb.addEventListener("change", calculateTotal));
      toppingCheckboxes.forEach(cb => cb.addEventListener("change", calculateTotal));
      deliveryRadios.forEach(rb => rb.addEventListener("change", calculateTotal));

      // Initial calculation
      calculateTotal();
    });
  </script>



</body>

</html>