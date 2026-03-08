<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once("../config/db.php");

// Fetch facilities for dropdown
$facilities = $conn->query("SELECT id, name FROM facilities WHERE status = 'AVAILABLE'");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fb_name = trim($_POST["fb_name"]);
    $fb_user_id = trim($_POST["fb_user_id"]);
    $facility_id = $_POST["facility_id"];
    $reservation_date = $_POST["reservation_date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $purpose = trim($_POST["purpose"]);

    if (
        empty($fb_name) || empty($fb_user_id) ||
        empty($facility_id) || empty($reservation_date) ||
        empty($start_time) || empty($end_time)
    ) {
        $error = "All required fields must be filled.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO reservations
            (fb_user_id, fb_name, facility_id, reservation_date, start_time, end_time, purpose)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssissss",
            $fb_user_id,
            $fb_name,
            $facility_id,
            $reservation_date,
            $start_time,
            $end_time,
            $purpose
        );

        $stmt->execute();
        $new_id = $conn->insert_id;

        require_once("../config/audit_helper.php");
        logActivity($conn, 'CREATE', 'RESERVATION', $new_id, "Created a new reservation for $fb_name", null, [
            'fb_name' => $fb_name,
            'fb_user_id' => $fb_user_id,
            'facility_id' => $facility_id,
            'reservation_date' => $reservation_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'purpose' => $purpose
        ]);

        header("Location: create.php?success=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Reservation - CEFI Reservation</title>
    <link rel="stylesheet" href="../style/reservations.css">
    <link rel="stylesheet" href="../style/navbar.css">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Create Reservation</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <div class="form-container">
        <h2>Create Reservation</h2>
        
        <?php if (isset($_GET["success"])): ?>
            <p class="success">Reservation saved successfully (PENDING).</p>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label for="fb_name">Facebook Name</label>
                <input type="text" id="fb_name" name="fb_name" placeholder="Enter Facebook name" required>
            </div>

            <div class="input-group">
                <label for="fb_user_id">Facebook User ID</label>
                <input type="text" id="fb_user_id" name="fb_user_id" placeholder="Enter Facebook User ID" required>
            </div>

            <div class="input-group">
                <label for="facility_id">Facility</label>
                <select id="facility_id" name="facility_id" required>
                    <option value="">Select Facility</option>
                    <?php while($row = $facilities->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>">
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="reservation_date">Date</label>
                <input type="date" id="reservation_date" name="reservation_date" required>
            </div>

            <div class="input-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>

            <div class="input-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>

            <div class="input-group">
                <label for="purpose">Purpose</label>
                <textarea id="purpose" name="purpose" placeholder="Enter purpose of reservation"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Reservation</button>
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
