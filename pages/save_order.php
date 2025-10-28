<?php
// Проверяем авторизию
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] != 2 && $_SESSION['user']['role'] != 1)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

// Подключаем БД
 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php'; 
include_once $project_root . '/templates/footer.php';

// Получаем данные
 $data = json_decode(file_get_contents('php://input'), true);
 $group_id = $data['group_id'];
 $user_ids = $data['user_ids']; // Массив ID в новом порядке

if (!$group_id || !is_array($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit();
}

try {
    $pdo->beginTransaction(); // Начинаем транзакцию

    // 1. Удаляем старый порядок для этой группы
    $stmt = $pdo->prepare("DELETE FROM schedule_group_order WHERE schedule_group_id = :group_id");
    $stmt->execute([':group_id' => $group_id]);

    // 2. Вставляем новый порядок
    $sql = "INSERT INTO schedule_group_order (schedule_group_id, user_id, order_index) VALUES (:group_id, :user_id, :order_index)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($user_ids as $index => $user_id) {
        $stmt->execute([
            ':group_id' => $group_id,
            ':user_id' => $user_id,
            ':order_index' => $index
        ]);
    }

    $pdo->commit(); // Подтверждаем транзакцию
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack(); // Откатываем изменения в случае ошибки
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>