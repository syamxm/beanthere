CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  price DECIMAL(6,2) NOT NULL,
  old_price DECIMAL(6,2),
  category VARCHAR(50) DEFAULT 'menu'
);
