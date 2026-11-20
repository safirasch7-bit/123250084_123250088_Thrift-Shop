<?php
require 'koneksi.php';

if (isset($_SESSION['login'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$flash_sukses = $_SESSION['flash_login'] ?? '';
unset($_SESSION['flash_login']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header('Location: index.php');
            exit;
        }
    }

    $error = 'Username atau password salah.';
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
</head>
<body class="bg-light" style="color: #ffe5d0;">
    <div class="container mt-5" style="max-width: 750px;">
        <div class="card shadow-sm p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-6 text-center">
                    <img src="assets/logo.png" alt="logo" class="img-fluid" style="max-width: 300px;">
                </div>
                <div class="col-md-6">
                    <h3 class="mb-3">Login</h3>

                    <?php if ($flash_sukses): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash_sukses) ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="form-box">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Masuk</button>
                        <a href="register.php" class="btn btn-link text-decoration-none">Daftar akun</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
