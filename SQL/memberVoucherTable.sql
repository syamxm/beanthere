CREATE TABLE member_vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  membershipID INT NOT NULL,
  voucherID INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  used BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (membershipID) REFERENCES membership(membershipID),
  FOREIGN KEY (voucherID) REFERENCES vouchers(voucherID)
);
