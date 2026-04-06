<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SELECT id, email, role FROM user ORDER BY id DESC LIMIT 5');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
