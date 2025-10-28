<?php
session_start();
if (!isset($_SESSION['user']) {
    header('Запрещено для неавторизованных пользователей.');
    exit();
}

if ($_SESSION['user']['role'] != 1) {
    header('Location: /pages/login.php');
    exit();
}

 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php';
include_once $project_root . '/templates/footer.php';

// Обработка форм для добавления/редактирования отпуска
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $action = $_POST['action']; // 'add' или 'edit' или 'delete'
    
    if ($action === 'add') {
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $reason = trim($_POST['reason']);
        
        if (empty($start_date) || empty($end_date) || empty($reason)) {
            $_SESSION['error_message'] = 'Пожалуйста, заполните все поля.';
            header('Location: /pages/vacation_manager.php');
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_vacations (user_id, start_date, end_date, reason, status) VALUES (:user_id, :start_date, :end_date, :reason, 'planned')");
            $stmt->execute([':user_id' => $user_id, 'start_date' => $start_date, 'end_date' => $end_date, 'reason' => $reason]);
            $_SESSION['success_message'] = 'Отпуск успешно добавлен!';
            header('location: /pages/vacation_manager.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Ошибка при добавлении отпуска: ' . $e->getMessage();
        }
    }
    
    // ... (здесь будет код для редактирования и удаления)
}

// В конце файла добавь этот код для отображения сообщений
if (isset($_SESSION['success_message'])) {
    echo "<p class='success-message'>" . $_SESSION['success_message'] . "</p>";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo "<p class='error-message'>" . $_SESSION['error_message'] . "</p>";
    unset($_SESSION['error_message']);
}
?>