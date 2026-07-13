<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';

$sqlMenuItem = "SELECT id, name, image_path, price, old_price, stock FROM menu_items WHERE category = 'menu'";
$resultMenuItem = mysqli_query($conn, $sqlMenuItem);

$sqlProductItem = "SELECT id, name, image_path, price, stock FROM menu_items WHERE category = 'product'";
$resultProductItem = mysqli_query($conn, $sqlProductItem);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bean There - Coffee Shop</title>
  <link rel="stylesheet" href="assets/style.css" />
  <link rel="stylesheet" href="assets/style_scrollbar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    .logo {
      font-size: 24px;
      font-weight: 700;
      color: #c49b63;
      text-transform: uppercase;
      letter-spacing: 2px;
      text-align: center;
    }

    nav ul {
      display: flex;
      list-style: none;
    }

    nav ul li {
      margin-left: 30px;
    }

    nav ul li a {
      text-decoration: none;
      color: #fff;
      font-weight: 500;
      transition: 0.3s;
      position: relative;
    }

    nav ul li a:hover,
    nav ul li a.active {
      color: #c49b63;
    }

    nav ul li a.active::after,
    nav ul li a:hover::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 2px;
      background-color: #c49b63;
      left: 0;
      bottom: -5px;
    }

    .icons {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .icons a {
      color: #fff;
      font-size: 20px;
      text-decoration: none;
      transition: 0.3s;
    }

    .icons a:hover {
      color: #c49b63;
    }

    section {
      border-top: 2px solid transparent;
      border-bottom: 2px solid transparent;
      border-image: linear-gradient(to right, #9f7aea, #f6ad55);
      border-image-slice: 1;
      padding: 125px 50px;
      min-height: 100vh;
    }

    .home-section {
      background: url('https://images.unsplash.com/photo-1511920170033-f8396924c348') no-repeat center center/cover;
      display: flex;
      align-items: center;
    }

    .home-section-text {
      max-width: 600px;
    }

    .home-section-text h1 {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 20px;
    }

    h2 {
      font-size: 36px;
      font-weight: 700;
      color: #c49b63;
      text-align: center;
      margin-bottom: 20px;
    }

    .review .stars {
      color: gold;
      margin-top: 10px;
    }

    .user {
      width: 60px;
      border-radius: 50%;
      margin-top: 10px;
    }

    .contact {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      align-items: center;
      justify-content: center;
    }

    .contact iframe {
      border: 0;
      width: 100%;
      height: 400px;
      max-width: 600px;
    }

    .icons a i {
      transition: color 0.3s;
    }

    .icons a:hover i {
      color: #c49b63;
    }

    .profile-dropdown {
      position: relative;
      display: inline-block;
    }

    .profile-dropdown .dropdown-content {
      display: none;
      position: absolute;
      background-color: #111;
      min-width: 120px;
      box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 20;
      right: 0;
      border-radius: 5px;
    }

    .profile-dropdown .dropdown-content a {
      color: white;
      padding: 10px 15px;
      text-decoration: none;
      display: block;
      font-size: 14px;
      transition: 0.3s;
    }

    .profile-dropdown .dropdown-content a:hover {
      background-color: #c49b63;
      color: #000;
    }

    .profile-dropdown:hover .dropdown-content {
      display: block;
    }

    .about-container {
      display: flex;
      flex-direction: column;
      gap: 40px;
    }

    .dropdown-username {
      font-weight: bold;
      padding: 10px 16px;
      margin-bottom: 5px;
      border-bottom: 1px solid #444;
      color: #aaffaa;
    }

    .about-box {
      opacity: 0;
      transform: translateY(60px);
      transition: opacity 2s ease, transform 2s ease;

      /* New background styling */
      background-color: #111;
      /* Semi-transparent dark background */
      color: #fff;
      /* Light text for contrast */
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(228, 228, 228, 0.3);
      margin-bottom: 20px;
    }

    .video-wrapper {
      position: relative;
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      padding-bottom: 40%;
      /* 16:9 aspect ratio = 9/16 = 0.5625 */
      background-color: #000;
      border: 2px solid #c49b63;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(196, 155, 99, 0.3);
    }

    .video-wrapper video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      /* Makes sure thumbnail fits the box */
      border-radius: 12px;
    }




    /* Animation triggers */
    .about-box.fade-in.animate {
      opacity: 1;
      transform: translateY(0);
    }

    .about-box.slide-in-left.animate {
      opacity: 1;
      transform: translateX(0);
    }

    /* Initial transform values */
    .fade-in {
      transform: translateY(60px);
    }

    .slide-in-left {
      transform: translateX(-100px);
    }

    .btn[disabled] {
      background-color: #777;
      cursor: not-allowed;
      opacity: 0.6;
    }
  </style>

</head>


<body>

  <header>

    <div class="logo">BEAN THERE</div>
    <nav>
      <ul>
        <li><a href="#home" class="active">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#menu">Menu</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#review">Review</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="#tutorial">Tutorial</a></li>
      </ul>
    </nav>

    <div class="icons">

      <a href="search.php" title="Search"><i class="fas fa-search"></i></a>
      <a href="cart.php" title="Cart"><i class="fas fa-shopping-cart"></i></a>
      <a href="recommendation.php" title="AI Coffee Recommender"><i class="fas fa-robot"></i></a>

      <div class="profile-dropdown" id="profileDropdown">
        <a href="#" id="profileToggle" title="Profile"><i class="fas fa-user"></i></a>
        <div class="dropdown-content" id="dropdownContent">
          <?php if (isset($_SESSION['current_user'])): ?>
            <p class="dropdown-username"><?= htmlspecialchars($_SESSION['current_user']) ?></p>
            <a href="user_verify.php">Verify</a>
            <a href="edit_user_detail.php">Edit My Info</a>
            <a href="user_order_tracking.php">My Orders</a>
            <a href="membership.php">Check Membership</a>
            <a href="voucher.php">Vouchers</a>
            <a href="user_logout.php">Logout</a>
          <?php else: ?>
            <a href="user_register.php">Sign Up</a>
            <a href="user_login.php">Log In</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </header>


  <section class="home-section" id="home">
    <div class="home-section-text">

      <h1>FRESH COFFEE IN THE MORNING</h1>
      <a href="#menu" class="btn">Get Yours Now</a>

    </div>
  </section>


  <section class="about" id="about">
    <h2>ABOUT <span>US</span></h2>
    <div class="about-container">
      <div class="about-box fade-in">
        <h3>Our Story</h3>
        <p>Founded in the heart of Petaling Jaya, Bean There began as a small café with a big dream: to bring the finest coffee experiences to our community. Over the years, we've grown, but our passion for quality and connection remains unchanged.</p>
      </div>

      <div class="about-box slide-in-left">
        <h3>Our Mission</h3>
        <p>We aim to craft the perfect cup of coffee for every customer, every time. By sourcing premium beans and embracing sustainable practices, we ensure that each sip supports both our patrons and the planet.</p>
      </div>

      <div class="about-box slide-in-left">
        <h3>Meet the Team</h3>
        <p>Our dedicated team of baristas and coffee enthusiasts work tirelessly to create a welcoming atmosphere. Their expertise and warmth are the heart of Bean There's success.</p>
      </div>
    </div>
  </section>



  <section class="menu" id="menu">
    <h2>OUR <span>MENU</span></h2>

    <div class="box-container">
      <?php if (mysqli_num_rows($resultMenuItem) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($resultMenuItem)): ?>
          <form action="customize.php" method="post">
            <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
            <input type="hidden" name="from_section" value="menu">
            <div class="box">
              <img loading="lazy" src="<?php echo $row['image_path'] ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
              <h3><?php echo htmlspecialchars($row['name']); ?></h3>
              <div class="price">RM<?php echo number_format($row['price'], 2); ?>
                <span>RM<?php echo number_format($row['old_price'], 2); ?></span>
              </div>
              <?php if ((int)$row['stock'] > 0): ?>
                <button type="submit" class="btn">Buy</button>
              <?php else: ?>
                <button type="button" class="btn" disabled style="background: #999; cursor: not-allowed;">Out of Stock</button>
              <?php endif; ?>

            </div>
          </form>

        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #fff;">No items found in the menu.</p>
      <?php endif; ?>
    </div>
  </section>


  <section class="products" id="products">
    <h2>OUR <span>PRODUCTS</span></h2>

    <div class="box-container">
      <?php if (mysqli_num_rows($resultProductItem) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($resultProductItem)): ?>
          <form action="add_to_cart.php" method="post">
            <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
            <input type="hidden" name="name" value="<?php echo $row['name'] ?>">
            <input type="hidden" name="price" value="<?php echo $row['price'] ?>">
            <input type="hidden" name="from_section" value="products">
            <div class="box">
              <img loading="lazy" src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
              <h3><?php echo htmlspecialchars($row['name']); ?></h3>
              <div class="price">RM<?php echo number_format($row['price'], 2); ?></div>
              <?php if ((int)$row['stock'] > 0): ?>
                <button type="submit" class="btn">Add To Cart</button>
              <?php else: ?>
                <button type="button" class="btn" disabled style="background: #999; cursor: not-allowed;">Out of Stock</button>
              <?php endif; ?>

            </div>
          </form>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #fff;">No items found in the menu.</p>
      <?php endif; ?>
    </div>
  </section>


  <?php
  mysqli_close($conn);
  ?>

  <section class="review" id="review">
    <h2>CUSTOMER'S <span>REVIEW</span></h2>
    <div class="box-container">

      <div class="box">
        <p>"The latte was rich and creamy. Perfectly balanced flavors!"</p>
        <img loading="lazy" src="assets/images/user1.jpg" class="user" alt="User">
        <h3>Jane Smith</h3>
        <div class="stars">⭐⭐⭐⭐⭐</div>
      </div>

      <div class="box">
        <p>"Loved the mocha. Would definitely come back for more!"</p>
        <img loading="lazy" src="assets/images/user2.jpg" class="user" alt="User">
        <h3>Ali Rahman</h3>
        <div class="stars">⭐⭐⭐⭐⭐</div>
      </div>

      <div class="box">
        <p>"Fast delivery and the beans were so fresh! 10/10."</p>
        <img loading="lazy" src="assets/images/user3.jpg" class="user" alt="User">
        <h3>Sophia Lee</h3>
        <div class="stars">⭐⭐⭐⭐</div>
      </div>

    </div>
  </section>



  <section class="contact" id="contact">
    <div class="form-container">
      <h2>GET IN TOUCH</h2>
      <form>
        <div class="form-group">
          <input type="text" placeholder="name">
        </div>

        <div class="form-group">
          <input type="email" placeholder="email">
        </div>

        <div class="form-group">
          <input type="text" placeholder="number">
        </div>

        <div class="form-group">
          <button type="submit" class="submit-button">Contact Now</button>
        </div>

      </form>

    </div>
  </section>

  <section class="tutorial-section" id="tutorial">
    <h2 style="color: #c49b63; font-size: 28px; text-align: center; margin-bottom: 20px;">
      How to Brew with French Press
    </h2>

    <div class="video-wrapper">
      <video controls poster="images/thumbnail.jpg">
        <source src="FrenchPressCoffeeTutorial.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </div>
  </section>





  <script>
    const sections = document.querySelectorAll("section");
    const navLinks = document.querySelectorAll("nav ul li a");

    window.addEventListener("scroll", () => {
      let current = "";
      sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        if (scrollY >= sectionTop) {
          current = section.getAttribute("id");
        }
      });

      navLinks.forEach(link => {
        link.classList.remove("active");
        if (link.getAttribute("href") === `#${current}`) {
          link.classList.add("active");
        }
      });
    });


    document.addEventListener("DOMContentLoaded", () => {
      const aboutBoxes = document.querySelectorAll('.about-box');

      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('animate');
            observer.unobserve(entry.target); // animate only once
          }
        });
      }, {
        threshold: 0.2 // trigger when 20% visible
      });

      aboutBoxes.forEach(box => observer.observe(box));
    });
  </script>

</body>

</html>
