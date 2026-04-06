<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SELECT id, email, role FROM user ORDER BY id DESC LIMIT 5');
file_put_contents('users.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
