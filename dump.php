<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SELECT id, email, role FROM user');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
