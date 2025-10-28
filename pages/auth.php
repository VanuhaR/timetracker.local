<?php
// Стартуем сессию
session_start();

// Подключаем файл с подключением к БД
 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';

// Проверяем, была ли отправлена форма (метод POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Получаем данные из формы и сразу защищаем от XSS-атак
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    // 2. Простая валидация
    if (empty($login) || empty($password)) {
        $_SESSION['error_message'] = 'Пожалуйста, заполните все поля.';
        header('Location: /pages/login.php');
        exit();
    }

    try {
        // 3. Готовим запрос к базе данных (ИСПОЛЬЗУЕМ ПОДГОТОВЛЕННЫЕ ЗАПРОСЫ!)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
        $stmt->execute([':login' => $login]);

        // 4. Получаем пользователя из базы
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. Проверяем, найден ли пользователь и верен ли пароль
        if ($user && password_verify($password, $user['password_hash'])) {
            // ПАРОЛЬ ВЕРНЫЙ!

            // 6. Успешная авторизация. Сохраняем данные в сессию.
            $_SESSION['user'] = [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'role' => $user['role_id']
            ];

            // Перенаправляем на главную страницу
            header('Location: /pages/dashboard.php');
            exit();

        } else {
            // ПОЛЬЗОВАТЕЛЬ НЕ НАЙДЕН ИЛИ ПАРОЛЬ НЕВЕРНЫЙ
            $_SESSION['error_message'] = 'Неверный логин или пароль.';
            header('Location: /pages/login.php');
            exit();
        }

    } catch (PDOException $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    }

} else {
    // Если кто-то пытается получить доступ к этому файлу напрямую, а не через форму
    header('Location: /pages/login.php');
    exit();
}
?>