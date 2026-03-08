<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once("../config/db.php");

// Handle approve
if (isset($_GET["approve"])) {

    $id = intval($_GET["approve"]);

    // Get reservation details first
    $stmt = $conn->prepare("
        SELECT facility_id, reservation_date, start_time, end_time
        FROM reservations
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($facility_id, $reservation_date, $start_time, $end_time);
    $stmt->fetch();
    $stmt->close();

    // Check for conflict with APPROVED reservations
    $check = $conn->prepare("
        SELECT id FROM reservations
        WHERE facility_id = ?
        AND reservation_date = ?
        AND status = 'APPROVED'
        AND (
            (? < end_time) AND (? > start_time)
        )
    ");

    $check->bind_param("isss",
        $facility_id,
        $reservation_date,
        $start_time,
        $end_time
    );

    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Conflict detected! Facility already booked for that time.";
    } else {
        $update = $conn->prepare("UPDATE reservations SET status = 'APPROVED' WHERE id = ?");
        $update->bind_param("i", $id);
        $update->execute();

        // LOGGING
        require_once("../config/audit_helper.php");
        logActivity($conn, 'UPDATE', 'RESERVATION', $id, "Approved reservation ID $id", null, ['status' => 'APPROVED']);

        header("Location: index.php");
        exit;
    }

    $check->close();
}

// Handle reject
if (isset($_GET["reject"])) {
    $id = intval($_GET["reject"]);
    $stmt = $conn->prepare("UPDATE reservations SET status = 'REJECTED' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // LOGGING
    require_once("../config/audit_helper.php");
    logActivity($conn, 'UPDATE', 'RESERVATION', $id, "Rejected reservation ID $id", null, ['status' => 'REJECTED']);

    header("Location: index.php");
    exit;
}

// Fetch reservations with facility name
$result = $conn->query("
    SELECT r.*, f.name AS facility_name
    FROM reservations r
    JOIN facilities f ON r.facility_id = f.id
    ORDER BY r.created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - CEFI Reservation</title>
    <link rel="stylesheet" href="../style/reservations.css">
    <link rel="stylesheet" href="../style/navbar.css">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Reservations</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <h2>All Reservations</h2>
    
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <a href="create.php" class="add-btn">+ Create Reservation</a>
    
    <?php if (isset($_GET['msg'])): ?>
        <p class="success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p class="error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
    
    <table>
        <tr>
            <th>Name</th>
            <th>Facility</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row["fb_name"]) ?></td>
            <td><?= htmlspecialchars($row["facility_name"]) ?></td>
            <td><?= $row["reservation_date"] ?></td>
            <td><?= $row["start_time"] ?> - <?= $row["end_time"] ?></td>
            <td>
                <?php
                    $status_class = 'status-' . strtolower($row["status"]);
                    echo "<span class=\"$status_class\">{$row["status"]}</span>";
                ?>
            </td>
            <td>
                <div class="action-links">
                    <?php if ($row["status"] == "PENDING"): ?>
                        <a href="?approve=<?= $row["id"] ?>" class="approve">Approve</a>
                        <a href="?reject=<?= $row["id"] ?>" class="reject">Reject</a>
                    <?php elseif ($row["status"] == "APPROVED"): ?>
                        <a href="print.php?id=<?= $row["id"] ?>" class="print">Print</a>
                    <?php else: ?>
                        <span class="no-action">—</span>
                    <?php endif; ?>
                    <a href="edit.php?id=<?= $row["id"] ?>">Edit</a>
                    <a href="delete.php?id=<?= $row["id"] ?>" class="reject" onclick="return confirm('Are you sure you want to delete this reservation?')">Delete</a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
  </div>
</div>

<div class="footer">
    © 2026 CEFI ONLINE FACILITY RESERVATION. All rights reserved. | Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website
</div>

</body>
</html>
