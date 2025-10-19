<?php
require_once 'config.php';

try {
    echo "Fixing foreign key constraint issue..." . PHP_EOL;

    // First, let's see if there are any existing history_of_delivery records
    $existingRecords = $db->query('SELECT COUNT(*) FROM history_of_delivery')->fetchColumn();
    echo "Existing history_of_delivery records: " . $existingRecords . PHP_EOL;

    if ($existingRecords > 0) {
        echo "Backing up existing data..." . PHP_EOL;
        // Create a backup of existing data
        $backup = $db->query('SELECT * FROM history_of_delivery')->fetchAll(PDO::FETCH_ASSOC);
        $backupItems = $db->query('SELECT * FROM history_of_delivery_items')->fetchAll(PDO::FETCH_ASSOC);

        // Drop the problematic foreign key constraint
        echo "Dropping foreign key constraint..." . PHP_EOL;
        $db->exec("ALTER TABLE history_of_delivery DROP FOREIGN KEY history_of_delivery_ibfk_1");

        // Recreate the table without the problematic constraint
        echo "Recreating table structure..." . PHP_EOL;
        $db->exec("DROP TABLE IF EXISTS history_of_delivery_items");
        $db->exec("DROP TABLE IF EXISTS history_of_delivery");

        // Recreate history_of_delivery table without the problematic foreign key
        $db->exec("
            CREATE TABLE history_of_delivery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                to_be_delivered_id INT NOT NULL,
                driver_id INT NOT NULL,
                user_id INT NOT NULL,
                delivery_address TEXT NOT NULL,
                payment_received DECIMAL(10,2),
                change_given DECIMAL(10,2),
                delivery_time TIMESTAMP,
                proof_image VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Recreate history_of_delivery_items table
        $db->exec("
            CREATE TABLE history_of_delivery_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                history_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (history_id) REFERENCES history_of_delivery(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ");

        // Restore backed up data
        echo "Restoring backed up data..." . PHP_EOL;
        foreach ($backup as $record) {
            $stmt = $db->prepare("
                INSERT INTO history_of_delivery 
                (to_be_delivered_id, driver_id, user_id, delivery_address, payment_received, change_given, delivery_time, proof_image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $record['to_be_delivered_id'],
                $record['driver_id'],
                $record['user_id'],
                $record['delivery_address'],
                $record['payment_received'],
                $record['change_given'],
                $record['delivery_time'],
                $record['proof_image'],
                $record['created_at']
            ]);
            $newId = $db->lastInsertId();

            // Restore items for this delivery
            foreach ($backupItems as $item) {
                if ($item['history_id'] == $record['id']) {
                    $itemStmt = $db->prepare("
                        INSERT INTO history_of_delivery_items (history_id, product_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $itemStmt->execute([$newId, $item['product_id'], $item['quantity'], $item['price']]);
                }
            }
        }

        echo "Data restored successfully!" . PHP_EOL;
    } else {
        echo "No existing data to backup. Dropping and recreating tables..." . PHP_EOL;

        // Drop and recreate tables without the problematic constraint
        $db->exec("DROP TABLE IF EXISTS history_of_delivery_items");
        $db->exec("DROP TABLE IF EXISTS history_of_delivery");

        // Recreate history_of_delivery table without the problematic foreign key
        $db->exec("
            CREATE TABLE history_of_delivery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                to_be_delivered_id INT NOT NULL,
                driver_id INT NOT NULL,
                user_id INT NOT NULL,
                delivery_address TEXT NOT NULL,
                payment_received DECIMAL(10,2),
                change_given DECIMAL(10,2),
                delivery_time TIMESTAMP,
                proof_image VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Recreate history_of_delivery_items table
        $db->exec("
            CREATE TABLE history_of_delivery_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                history_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (history_id) REFERENCES history_of_delivery(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ");

        echo "Tables recreated successfully!" . PHP_EOL;
    }

    echo "Foreign key constraint issue fixed!" . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
