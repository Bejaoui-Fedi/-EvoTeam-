<?php
$c = mysqli_connect('127.0.0.1', 'root', '', 'pi_java');
$res = mysqli_query($c, "DESCRIBE wellbeing_tracker");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
$res2 = mysqli_query($c, "DESCRIBE daily_routine_task");
while ($row = mysqli_fetch_assoc($res2)) {
    print_r($row);
}
$res3 = mysqli_query($c, "DESCRIBE user");
while ($row = mysqli_fetch_assoc($res3)) {
    print_r($row);
}
