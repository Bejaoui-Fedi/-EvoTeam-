<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SHOW TRIGGERS');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
