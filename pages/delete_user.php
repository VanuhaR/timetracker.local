<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    // Удалять может только директор
    header('Location: /pages/login.php');
    exit();
}

 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';

 $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id === 0) {
    die("Ошибка: ID пользователя не указан.");
}

try {
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);

    $_SESSION['success_message'] = "Сотрудник и все его смены успешно удалены.";
    header('Location: /pages/user_management.php');
    exit();

} catch (PDOException $e) {
    die("Ошибка при удалении сотрудника: " . $e->getMessage());
}
?>