<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    header('Content-Type: application/json'); // Устанавливаем заголовок в любом случае
    echo json_encode(['success' => false, 'message' => 'Авторизация required']);
    exit();
}

$project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';

// Устанавливаем заголовок в самом начале
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];
$work_date = $data['work_date'];
$work_type_id = $data['work_type_id'];

if (!$user_id || !$work_date) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM schedules WHERE user_id = :user_id AND work_date = :work_date");
    $stmt->execute([':user_id' => $user_id, ':work_date' => $work_date]);
    $existing_schedule = $stmt->fetch();

    if ($existing_schedule) {
        if ($work_type_id) {
            $stmt = $pdo->prepare("UPDATE schedules SET work_type_id = :work_type_id WHERE user_id = :user_id AND work_date = :work_date");
            $stmt->execute([':work_type_id' => $work_type_id, ':user_id' => $user_id, ':work_date' => $work_date]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE user_id = :user_id AND work_date = :work_date");
            $stmt->execute([':user_id' => $user_id, ':work_date' => $work_date]);
        }
    } else {
        if ($work_type_id) {
            $stmt = $pdo->prepare("INSERT INTO schedules (user_id, work_date, work_type_id) VALUES (:user_id, :work_date, :work_type_id)");
            $stmt->execute([':user_id' => $user_id, ':work_date' => $work_date, ':work_type_id' => $work_type_id]);
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Теперь любая ошибка БД будет поймана и отправлена как JSON
    http_response_code(500); // Внутренняя ошибка сервера
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Ловим любые другие возможные ошибки
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Общая ошибка: ' . $e->getMessage()]);
}
?>