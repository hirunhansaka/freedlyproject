<?php
// database_connection.php

// ✅ CORRECTION: This check prevents the "session already active" notice.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_name = 'freelance_db';
$db_user = 'root'; // Your database username
$db_pass = '';     // Your database password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $db_name :" . $e->getMessage());
}