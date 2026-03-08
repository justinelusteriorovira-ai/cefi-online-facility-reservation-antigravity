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
    SELECT r.*, f.name AS facility_name, f.description AS facility_description, f.capacity AS facility_capacity
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

// Check if reservation is approved
if ($reservation["status"] != "APPROVED") {
    header("Location: index.php");
    exit;
}

// Format date and time
$formatted_date = date("F j, Y", strtotime($reservation["reservation_date"]));
$formatted_created_at = date("F j, Y h:i A", strtotime($reservation["created_at"]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Reservation - CEFI Reservation</title>
    <style>
        /* Print styles */
        @media print {
            body {
                font-family: 'Times New Roman', serif;
                font-size: 12pt;
                line-height: 1.5;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            @page {
                margin: 20mm;
            }
        }

        /* Screen styles */
        @media screen {
            body {
                font-family: Arial, sans-serif;
                font-size: 14px;
                background-color: #f5f5f5;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background-color: white;
                padding: 40px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
        }

        /* Common styles */
        * {
            box-sizing: border-box;
        }

        body {
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo-container img {
            max-height: 80px;
        }

        h1 {
            font-size: 24pt;
            color: #0056b3;
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .subtitle {
            font-size: 14pt;
            color: #666;
            margin: 0 0 20px 0;
        }

        .document-info {
            text-align: right;
            margin-bottom: 30px;
            font-size: 10pt;
            color: #666;
        }

        .document-title {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
            color: #333;
        }

        .reservation-details {
            margin: 30px 0;
        }

        .detail-section {
            margin-bottom: 20px;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
            color: #555;
        }

        .detail-value {
            display: inline-block;
            vertical-align: top;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #0056b3;
            padding-bottom: 5px;
            border-bottom: 2px solid #0056b3;
        }

        .facility-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            background-color: #28a745;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10pt;
        }

        .signature-section {
            margin-top: 60px;
            text-align: right;
        }

        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            width: 300px;
            margin-left: auto;
            text-align: center;
            padding-top: 10px;
            font-weight: bold;
        }

        .print-button-container {
            text-align: center;
            margin-top: 30px;
        }

        .print-button {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .print-button:hover {
            background-color: #004494;
        }

        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        .divider {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 20pt;
            }
            
            .document-title {
                font-size: 16pt;
            }
            
            .detail-label {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="https://enrollment.cefi.website/images/cefi-logo.png" alt="CEFI Logo">
            </div>
            <h1>CALAYAN EDUCATIONAL FOUNDATION INC.</h1>
            <p class="subtitle">CEFI Online Facility Reservation System</p>
            <p style="color: #666; font-size: 10pt;">Calayan Educational Foundation Inc., Philippines | Contact: info@cefi.website</p>
        </div>

        <div class="document-info">
            <p>Document No: RES-<?= str_pad($reservation["id"], 6, '0', STR_PAD_LEFT) ?></p>
            <p>Date Issued: <?= $formatted_created_at ?></p>
            <p>Status: <span class="status-badge">APPROVED</span></p>
        </div>

        <div class="document-title">
            RESERVATION CONFIRMATION
        </div>

        <div class="divider"></div>

        <!-- Reservation Details -->
        <div class="reservation-details">
            <div class="section-title">Reservation Information</div>
            
            <div class="detail-section">
                <span class="detail-label">Reservation ID:</span>
                <span class="detail-value">RES-<?= str_pad($reservation["id"], 6, '0', STR_PAD_LEFT) ?></span>
            </div>

            <div class="detail-section">
                <span class="detail-label">Requester:</span>
                <span class="detail-value"><?= htmlspecialchars($reservation["fb_name"]) ?></span>
            </div>

            <div class="detail-section">
                <span class="detail-label">Facebook ID:</span>
                <span class="detail-value"><?= htmlspecialchars($reservation["fb_user_id"]) ?></span>
            </div>

            <div class="detail-section">
                <span class="detail-label">Purpose:</span>
                <span class="detail-value"><?= htmlspecialchars($reservation["purpose"]) ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Facility Details -->
        <div class="reservation-details">
            <div class="section-title">Facility Information</div>
            
            <div class="facility-info">
                <div class="detail-section">
                    <span class="detail-label">Facility Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($reservation["facility_name"]) ?></span>
                </div>

                <div class="detail-section">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?= htmlspecialchars($reservation["facility_description"]) ?></span>
                </div>

                <div class="detail-section">
                    <span class="detail-label">Capacity:</span>
                    <span class="detail-value"><?= $reservation["facility_capacity"] ?> persons</span>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Schedule Details -->
        <div class="reservation-details">
            <div class="section-title">Schedule Information</div>
            
            <div class="detail-section">
                <span class="detail-label">Reservation Date:</span>
                <span class="detail-value"><?= $formatted_date ?></span>
            </div>

            <div class="detail-section">
                <span class="detail-label">Time Slot:</span>
                <span class="detail-value"><?= $reservation["start_time"] ?> - <?= $reservation["end_time"] ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Terms and Conditions -->
        <div class="reservation-details">
            <div class="section-title">Terms and Conditions</div>
            <ul style="font-size: 10pt; line-height: 1.4;">
                <li>This reservation is subject to the terms and conditions of CEFI Online Facility Reservation System.</li>
                <li>The facility must be used for the stated purpose only.</li>
                <li>Users are responsible for maintaining the cleanliness and safety of the facility.</li>
                <li>Any damages to the facility must be reported immediately to the administration.</li>
                <li>Reservations may be canceled or rescheduled with prior notice.</li>
            </ul>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <p style="font-size: 10pt; color: #666;">This reservation has been approved by the CEFI Administration.</p>
            <div class="signature-line">
                Authorized Signature
            </div>
            <p style="font-size: 10pt; color: #666; margin-top: 5px;">Date: <?= date("F j, Y") ?></p>
        </div>

        <!-- Print and Back Buttons -->
        <div class="print-button-container no-print">
            <a href="index.php" class="back-button">← Back to Reservations</a>
            <button class="print-button" onclick="window.print()">Print Document</button>
        </div>
    </div>

    <script>
        // Auto-print on load if parameter is provided
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                window.print();
            }
        });
    </script>
</body>
</html>
