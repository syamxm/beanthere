CREATE DATABASE beanthere;
USE beanthere;


CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  price DECIMAL(10,2),
  image VARCHAR(255)
);

CREATE TABLE cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  product_id INT,
  quantity INT DEFAULT 1,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  drink VARCHAR(255),
  temperature VARCHAR(20),
  milk_type VARCHAR(50),
  sugar_level VARCHAR(20),
  syrup VARCHAR(100),
  toppings VARCHAR(100),
  delivery_method VARCHAR(20),
  total_amount DECIMAL(6,2),
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

