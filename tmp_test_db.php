<?php
require_once("c:/xampp/htdocs/cefi_reservation/config/db.php");

$table = "special_occasions";
$result = $conn->query("SHOW TABLES LIKE '$table'");

if ($result->num_rows > 0) {
    echo "SUCCESS: Table '$table' exists.\n";
    
    $cols = $conn->query("DESCRIBE $table");
    echo "Columns in '$table':\n";
    while($row = $cols->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
    echo "Row count: $count\n";
} else {
    echo "FAILURE: Table '$table' does not exist.\n";
}
?>
