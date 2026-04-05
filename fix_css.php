<?php
$c = file_get_contents('assets/styles/styles.css');
$c = str_replace("\0", "", $c);
file_put_contents('assets/styles/styles.css', $c);
echo "Fixed encoding.";
