<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<?php
require_once("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $capacity = trim($_POST["capacity"]);
    $status = $_POST["status"];

    $valid_statuses = ['AVAILABLE', 'MAINTENANCE', 'CLOSED'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'AVAILABLE';
    }

    if (empty($name) || empty($capacity)) {
        $error = "All fields required.";
    } else {

        $stmt = $conn->prepare("INSERT INTO facilities (name, capacity, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $name, $capacity, $status);
        $stmt->execute();
        $new_id = $conn->insert_id;

        require_once("../config/audit_helper.php");
        logActivity($conn, 'CREATE', 'FACILITY', $new_id, "Created a new facility: $name", null, [
            'name' => $name,
            'capacity' => $capacity,
            'status' => $status
        ]);

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Facility - CEFI Reservation</title>
    <link rel="stylesheet" href="../style/facilities.css">
    <link rel="stylesheet" href="../style/navbar.css">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Add Facility</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <div class="form-container">
      <h2>Add Facility</h2>
      
      <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
      
      <form method="POST">
        <div class="input-group">
          <label for="name">Facility Name</label>
          <input type="text" id="name" name="name" placeholder="Enter facility name" required>
        </div>

        <div class="input-group">
          <label for="capacity">Capacity</label>
          <input type="number" id="capacity" name="capacity" placeholder="Enter capacity" required>
        </div>

        <div class="input-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="AVAILABLE">AVAILABLE</option>
            <option value="MAINTENANCE">MAINTENANCE</option>
            <option value="CLOSED">CLOSED</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>

  <div class="footer">
    © 2026 CEFI ONLINE FACILITY RESERVATION. All rights reserved. | Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website
  </div>
</body>
</html>
