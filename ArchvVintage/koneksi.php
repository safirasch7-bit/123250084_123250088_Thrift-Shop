<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "archvvintage";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi cek login untuk melindungi halaman dashboard & CRUD
function wajib_login() {
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        header("Location: login.php");
        exit;
    }
}
?>
