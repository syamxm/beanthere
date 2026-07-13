CREATE TABLE membership (
  membershipID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  join_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active', 'revoked', 'pending') DEFAULT 'pending',
  FOREIGN KEY (userID) REFERENCES users(userID)
);
