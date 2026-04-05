<?php
require 'vendor/autoload.php';
$u = new App\Entity\User();
$u->setRole('ADMIN');
echo json_encode($u->getRoles());
