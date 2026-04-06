<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pidevusermanagement', 'root', '');
$stmt = $pdo->query('SHOW TRIGGERS');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
    echo "TRIGGER: " . $t['Trigger'] . "\n";
    echo "STATEMENT: " . $t['Statement'] . "\n\n";
}
