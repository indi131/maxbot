<?php


$host = "localhost";
$username = "unicodebo_base";
$password = "iE0gB7lM8plF";
$database = "unicodebo_base";

$db = new mysqli($host, $username, $password, $database);

if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
} 


?>