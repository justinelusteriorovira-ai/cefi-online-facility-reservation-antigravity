<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: auth/login.php");
    exit;
}

require_once("config/db.php");

// Get statistics
$total_facilities = $conn->query("SELECT COUNT(*) as count FROM facilities")->fetch_assoc()['count'];
$available_facilities = $conn->query("SELECT COUNT(*) as count FROM facilities WHERE status = 'AVAILABLE'")->fetch_assoc()['count'];

$total_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations")->fetch_assoc()['count'];
$pending_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'PENDING'")->fetch_assoc()['count'];
$approved_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'APPROVED'")->fetch_assoc()['count'];
$rejected_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'REJECTED'")->fetch_assoc()['count'];

// Get recent reservations
$recent_reservations = $conn->query("
    SELECT r.*, f.name AS facility_name 
    FROM reservations r 
    JOIN facilities f ON r.facility_id = f.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");

// Get today's reservations
$today = date('Y-m-d');
$today_reservations = $conn->query("
    SELECT COUNT(*) as count FROM reservations 
    WHERE reservation_date = '$today'
")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CEFI Reservation</title>
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="style/navbar.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Dashboard</h1>
        <div class="header-user">Admin</div>
    </div>

    <div class="container">
    <h1>Welcome, Admin</h1>
    
    <?php if (isset($_GET['msg'])): ?>
        <p class="success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p class="error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon facilities-icon">🏢</div>
            <div class="stat-info">
                <h3><?= $total_facilities ?></h3>
                <p>Total Facilities</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon available-icon">✅</div>
            <div class="stat-info">
                <h3><?= $available_facilities ?></h3>
                <p>Available Facilities</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon reservations-icon">📅</div>
            <div class="stat-info">
                <h3><?= $total_reservations ?></h3>
                <p>Total Reservations</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon today-icon">📆</div>
            <div class="stat-info">
                <h3><?= $today_reservations ?></h3>
                <p>Today's Reservations</p>
            </div>
        </div>
    </div>
    
    <!-- Reservation Status Cards -->
    <h2>Reservation Status Overview</h2>
    <div class="status-grid">
        <div class="status-card pending">
            <h3><?= $pending_reservations ?></h3>
            <p>Pending</p>
        </div>
        
        <div class="status-card approved">
            <h3><?= $approved_reservations ?></h3>
            <p>Approved</p>
        </div>
        
        <div class="status-card rejected">
            <h3><?= $rejected_reservations ?></h3>
            <p>Rejected</p>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <h2>Quick Actions</h2>
    <div class="quick-actions">
        <a href="facilities/create.php" class="action-card">
            <span class="action-icon">➕</span>
            <span class="action-text">Add New Facility</span>
        </a>
        
        <a href="reservations/create.php" class="action-card">
            <span class="action-icon">📝</span>
            <span class="action-text">Create Reservation</span>
        </a>
        
        <a href="facilities/index.php" class="action-card">
            <span class="action-icon">🏢</span>
            <span class="action-text">Manage Facilities</span>
        </a>
        
        <a href="reservations/index.php" class="action-card">
            <span class="action-icon">📋</span>
            <span class="action-text">View All Reservations</span>
        </a>
        
        <a href="calendar/index.php" class="action-card">
            <span class="action-icon">🗓️</span>
            <span class="action-text">View Calendar</span>
        </a>

        <a href="calendar/occasions.php" class="action-card">
            <span class="action-icon">🌟</span>
            <span class="action-text">Manage Occasions</span>
        </a>

        <a href="reservations/history.php" class="action-card">
            <span class="action-icon">📜</span>
            <span class="action-text">Reservation History</span>
        </a>

        <a href="audit_trail.php" class="action-card">
            <span class="action-icon">📝</span>
            <span class="action-text">View Audit Trail</span>
        </a>
    </div>

    <!-- Upcoming Occasions Widget -->
    <?php
    $upcoming_occ = $conn->query("
        SELECT * FROM special_occasions 
        WHERE occasion_date >= '$today' 
        ORDER BY occasion_date ASC 
        LIMIT 5
    ");
    ?>
    <?php if ($upcoming_occ->num_rows > 0): ?>
    <div class="upcoming-occasions">
        <h2>Upcoming Special Occasions</h2>
        <div class="occasions-grid">
            <?php while($occ = $upcoming_occ->fetch_assoc()): 
                $d = date('M d', strtotime($occ['occasion_date']));
            ?>
            <div class="occasion-card" style="border-left-color: <?= $occ['color'] ?>;">
                <div class="occasion-date"><?= $d ?></div>
                <div class="occasion-title"><?= htmlspecialchars($occ['title']) ?></div>
                <div class="occasion-type"><?= str_replace('_', ' ', $occ['type']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Reservations -->
    <h2>Recent Reservations</h2>
    <?php if ($recent_reservations->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $recent_reservations->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['fb_name']) ?></td>
                    <td><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td><?= $row['reservation_date'] ?></td>
                    <td><?= $row['start_time'] ?> - <?= $row['end_time'] ?></td>
                    <td>
                        <?php
                            $status_class = 'status-' . strtolower($row['status']);
                            echo "<span class=\"$status_class\">{$row['status']}</span>";
                        ?>
                    </td>
                    <td>
                        <a href="reservations/edit.php?id=<?= $row['id'] ?>" class="edit-link">Edit</a>
                        <a href="reservations/delete.php?id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this reservation?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    <?php elseif ($recent_reservations->num_rows == 0): ?>
        <p class="no-data">No reservations found. <a href="reservations/create.php">Create one now</a>.</p>
    <?php endif; ?>
    
    <div class="view-all">
        <a href="reservations/index.php" class="view-all-btn">View All Reservations →</a>
    </div>
    </div>
</div>

<div class="footer">
    © 2026 CEFI ONLINE FACILITY RESERVATION. All rights reserved. | Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website
</div>

</body>
</html>
