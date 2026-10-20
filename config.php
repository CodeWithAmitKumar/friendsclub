<?php
// Friends Club — database config (XAMPP defaults)

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'friendsclub';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
  die('Connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
