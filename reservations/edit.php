<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once("../config/db.php");

// Check if ID is provided
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET["id"];

// Fetch the reservation
$stmt = $conn->prepare("
    SELECT r.*, f.name AS facility_name
    FROM reservations r
    JOIN facilities f ON r.facility_id = f.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    header("Location: index.php");
    exit;
}

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
    $status = $_POST["status"];

    $valid_statuses = ['PENDING', 'APPROVED', 'REJECTED'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'PENDING';
    }

    if (
        empty($fb_name) || empty($fb_user_id) ||
        empty($facility_id) || empty($reservation_date) ||
        empty($start_time) || empty($end_time)
    ) {
        $error = "All required fields must be filled.";
    } else {

        $stmt = $conn->prepare("
            UPDATE reservations 
            SET fb_user_id = ?, fb_name = ?, facility_id = ?, 
                reservation_date = ?, start_time = ?, end_time = ?, 
                purpose = ?, status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssisssssi",
            $fb_user_id,
            $fb_name,
            $facility_id,
            $reservation_date,
            $start_time,
            $end_time,
            $purpose,
            $status,
            $id
        );

        $stmt->execute();

        require_once("../config/audit_helper.php");
        logActivity($conn, 'UPDATE', 'RESERVATION', $id, "Updated reservation for $fb_name", $reservation, [
            'fb_name' => $fb_name,
            'fb_user_id' => $fb_user_id,
            'facility_id' => $facility_id,
            'reservation_date' => $reservation_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'purpose' => $purpose,
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
    <title>Edit Reservation - CEFI Reservation</title>
    <link rel="stylesheet" href="../style/reservations.css">
    <link rel="stylesheet" href="../style/navbar.css">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Edit Reservation</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <div class="form-container">
        <h2>Edit Reservation</h2>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label for="fb_name">Facebook Name</label>
                <input type="text" id="fb_name" name="fb_name" value="<?= htmlspecialchars($reservation['fb_name']) ?>" placeholder="Enter Facebook name" required>
            </div>

            <div class="input-group">
                <label for="fb_user_id">Facebook User ID</label>
                <input type="text" id="fb_user_id" name="fb_user_id" value="<?= htmlspecialchars($reservation['fb_user_id']) ?>" placeholder="Enter Facebook User ID" required>
            </div>

            <div class="input-group">
                <label for="facility_id">Facility</label>
                <select id="facility_id" name="facility_id" required>
                    <option value="">Select Facility</option>
                    <?php 
                    // Reset pointer for facilities query
                    $facilities->data_seek(0);
                    while($row = $facilities->fetch_assoc()): 
                    ?>
                        <option value="<?= $row['id'] ?>" <?= $reservation['facility_id'] == $row['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="reservation_date">Date</label>
                <input type="date" id="reservation_date" name="reservation_date" value="<?= $reservation['reservation_date'] ?>" required>
            </div>

            <div class="input-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="<?= $reservation['start_time'] ?>" required>
            </div>

            <div class="input-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" value="<?= $reservation['end_time'] ?>" required>
            </div>

            <div class="input-group">
                <label for="purpose">Purpose</label>
                <textarea id="purpose" name="purpose" placeholder="Enter purpose of reservation"><?= htmlspecialchars($reservation['purpose']) ?></textarea>
            </div>

            <div class="input-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="PENDING" <?= $reservation['status'] == 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                    <option value="APPROVED" <?= $reservation['status'] == 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                    <option value="REJECTED" <?= $reservation['status'] == 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Reservation</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <?php if ($reservation['status'] == 'APPROVED'): ?>
                <a href="print.php?id=<?= $reservation['id'] ?>" class="btn btn-print">Print Form</a>
            <?php endif; ?>
        </form>
    </div>
</div>
</div>

<div class="footer">
    © 2026 CEFI ONLINE FACILITY RESERVATION. All rights reserved. | Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website
</div>

</body>
</html>
