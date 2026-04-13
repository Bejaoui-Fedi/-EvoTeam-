<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$pdo->exec("UPDATE user SET role='ADMIN' WHERE email LIKE '%admin%' OR email LIKE '%ad%'");
$pdo->exec("UPDATE user SET role='PSY_COACH' WHERE id >= 60 OR email='flen@gmail.com'");
echo "Database FIXED!";
