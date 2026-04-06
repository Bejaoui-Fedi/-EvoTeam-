<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SHOW TRIGGERS');
file_put_contents('triggers.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
