<?php
require_once('config.php');

// Check the structure of the drivers table
$stmt = $db->query("SHOW COLUMNS FROM drivers");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Drivers Table Structure</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if there are any triggers on the drivers table
try {
    $stmt = $db->query("SHOW TRIGGERS LIKE 'drivers'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($triggers) > 0) {
        echo "<h2>Triggers on Drivers Table</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Trigger</th><th>Event</th><th>Table</th><th>Statement</th><th>Timing</th></tr>";
        foreach ($triggers as $trigger) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($trigger['Trigger']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Event']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Table']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Statement']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Timing']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No triggers found on the drivers table.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error checking for triggers: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
