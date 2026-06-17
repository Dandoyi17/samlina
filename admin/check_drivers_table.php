<?php
require_once '../db/config.php';

// Get table structure
$sql = "DESCRIBE drivers";
$result = $conn->query($sql);

echo "<h2>Drivers Table Structure:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Also show sample data
echo "<h2>Sample Data:</h2>";
$data_result = $conn->query("SELECT * FROM drivers LIMIT 5");
if ($data_result && $data_result->num_rows > 0) {
    echo "<pre>";
    while ($row = $data_result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "No drivers in database";
}
?>
