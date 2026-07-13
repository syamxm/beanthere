<?php
include "dbconn.php";

if (isset($_GET['drink_id'])) {
  $id = intval($_GET['drink_id']);
  $query = "SELECT name, price FROM menu_items WHERE id = $id";
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) === 1) {
    $drink = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'drink' => $drink]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Drink not found']);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'No drink ID provided']);
}
