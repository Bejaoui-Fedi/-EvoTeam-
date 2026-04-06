<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SHOW CREATE TABLE user');
file_put_contents('schema.txt', $stmt->fetchColumn(1));
