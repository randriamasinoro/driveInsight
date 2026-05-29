<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php'); exit();
}
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['res_id'])) {
    getDB()->prepare("DELETE FROM reservations WHERE id = ?")
           ->execute([intval($_POST['res_id'])]);
}

header('Location: /admin/index.php');
exit();
