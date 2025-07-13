<?php
$conn = new mysqli("localhost", "root", "", "test");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
