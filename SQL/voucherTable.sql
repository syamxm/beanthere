CREATE TABLE vouchers (
  voucherID INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) UNIQUE NOT NULL,
  discount_value DECIMAL(5,2) NOT NULL, -- e.g. 15.00 for 15%
  created_by INT,
  valid_from DATETIME,
  valid_until DATETIME,
  status ENUM('active', 'expired', 'disabled') DEFAULT 'active',
  FOREIGN KEY (created_by) REFERENCES admins(id)
);
