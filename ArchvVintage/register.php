<?php
include "koneksi.php";

$error = [];

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error['username'] = "Username harus 3-30 karakter.";
    }

    if (strlen($password) < 8) {
        $error['password'] = "Password minimal 8 karakter.";
    }

    if ($password !== $konfirmasi) {
        $error['konfirmasi'] = "Password tidak sama.";
    }

    // Cek username hanya jika format username sudah valid
    if (!isset($error['username'])) {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error['username'] = "Username sudah dipakai.";
        }

        $stmt->close();
    }

    // Simpan akun
    if (empty($error)) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password_hash)
             VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute();
        $stmt->close();

        header("Location: login.php?status=berhasil");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Rental DVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card mx-auto p-4" style="max-width:450px">
        <h3 class="text-center mb-3">Register</h3>

        <form method="POST">
            <!-- Input Username -->
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    id="username"
                    class="form-control <?= isset($error['username']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
                <div class="invalid-feedback">
                    <?= $error['username'] ?? '' ?>
                </div>
            </div>
            
            <!-- Input Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    class="form-control <?= isset($error['password']) ? 'is-invalid' : '' ?>"
                >
                <div class="invalid-feedback">
                    <?= $error['password'] ?? '' ?>
                </div>
            </div>

            <!-- Input Konfirmasi Password -->
            <div class="mb-3">
                <label for="konfirmasi" class="form-label">Konfirmasi Password</label>
                <input 
                    type="password" 
                    name="konfirmasi" 
                    id="konfirmasi"
                    class="form-control <?= isset($error['konfirmasi']) ? 'is-invalid' : '' ?>"
                >
                <div class="invalid-feedback">
                    <?= $error['konfirmasi'] ?? '' ?>
                </div>
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100 mt-2">
                Daftar
            </button>
        </form>

        <p class="text-center mt-3 mb-0">
            Sudah punya akun?
            <a href="login.php">Login</a>
        </p>
    </div>
</div>

</body>
</html>
