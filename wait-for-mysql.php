<?php
echo "Waiting for MySQL to be ready...\n";
for ($i = 1; $i <= 30; $i++) {
    try {
        $pdo = new PDO('mysql:host=db;port=3306;dbname=check_du_matin', 'laravel', 'laravel');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "MySQL is ready!\n";
        exit(0);
    } catch (Exception $e) {
        echo "MySQL is not ready yet. Waiting... ($i/30)\n";
        sleep(2);
    }
}
echo "MySQL did not become ready in time!\n";
exit(1);

