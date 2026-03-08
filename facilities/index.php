<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<?php
require_once("../config/db.php");
$result = $conn->query("SELECT * FROM facilities");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - CEFI Reservation</title>
    <link rel="stylesheet" href="../style/facilities.css">
    <link rel="stylesheet" href="../style/navbar.css">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Facilities</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <h2>Facilities</h2>
    <a href="create.php" class="add-btn">+ Add Facility</a>
    
    <?php if (isset($_GET['msg'])): ?>
        <p class="success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p class="error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
    
    <table>
      <tr>
        <th>Name</th>
        <th>Capacity</th>
        <th>Status</th>
        <th>Action</th>
      </tr>

      <?php while($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= $row['capacity'] ?></td>
        <td>
          <?php 
            $status_class = 'status-' . strtolower($row['status']);
            echo "<span class=\"$status_class\">{$row['status']}</span>";
          ?>
        </td>
        <td>
          <a href="edit.php?id=<?= $row['id'] ?>">Edit</a>
          <a href="delete.php?id=<?= $row['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this facility?')">Delete</a>
      <?php endwhile; ?>
    </table>
  </div>
</div>

  <div class="footer">
    © 2026 CEFI ONLINE FACILITY RESERVATION. All rights reserved. | Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website
  </div>
</body>
</html>
