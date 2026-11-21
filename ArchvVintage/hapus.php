<?php
require 'koneksi.php';
wajib_login();

DELETE FROM produk WHERE id = ? AND user_id = ?;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        // Ambil nama file foto sebelum data dihapus
        $stmt = $conn->prepare("SELECT foto FROM produk WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            // Hapus record dari database
            $del = $conn->prepare("DELETE FROM produk WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                // Hapus file fisik foto di folder uploads
                if (!empty($res['foto']) && file_exists('uploads/' . $res['foto'])) {
                    unlink('uploads/' . $res['foto']);
                }
                $_SESSION['notif'] = "Barang berhasil dihapus.";
            }
            $del->close();
        }
    }
}

header("Location: index.php");
exit;
?>
