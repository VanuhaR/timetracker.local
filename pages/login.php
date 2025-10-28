<?php
// ВАЖНО: Запускаем сессию в самом начале файла
session_start();
 $hide_sidebar = true; 
 $project_root = dirname(__DIR__);
include_once $project_root . '/templates/header.php';
?>

<!-- Стили для страницы входа. Лучше вынести их в отдельный CSS-файл, но для примера оставим здесь -->
<style>
    /* Стили для фона и центрирования */
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        background-color: #f0f2f5; /* Светло-серый фон */
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    /* Стили для карточки входа */
    .login-card {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
        box-sizing: border-box;
    }

    .login-card h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
        color: #1c1e21;
        font-weight: 600;
    }

    .login-card p {
        margin: 0 0 25px 0;
        color: #606770;
        font-size: 16px;
    }

    /* Стили для формы */
    .login-form .form-group {
        margin-bottom: 15px;
        text-align: left;
    }

    .login-form label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #4b4f56;
        font-weight: 600;
    }

    .login-form input[type="text"],
    .login-form input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #dddfe2;
        border-radius: 6px;
        font-size: 16px;
        box-sizing: border-box;
    }

    .login-form input:focus {
        outline: none;
        border-color: #1877f2; /* Синяя рамка при фокусе */
        box-shadow: 0 0 0 2px #e7f3ff;
    }

    /* Стили для кнопок */
    .btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-primary {
        background-color: #1877f2;
        color: white;
    }

    .btn-primary:hover {
        background-color: #166fe5;
    }

    .btn-secondary {
        background-color: #e4e6eb;
        color: #050505;
        margin-top: 10px;
    }
    
    .btn-secondary:hover {
        background-color: #d8dadf;
    }

    /* Разделитель "ИЛИ" */
    .divider {
        display: flex;
        align-items: center;
        margin: 20px 0;
        color: #606770;
        font-size: 14px;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background-color: #dadde1;
    }

    .divider span {
        padding: 0 15px;
    }

    /* Ссылки под формой */
    .form-links {
        margin-top: 20px;
        font-size: 14px;
    }

    .form-links a {
        color: #1877f2;
        text-decoration: none;
        font-weight: 500;
    }

    .form-links a:hover {
        text-decoration: underline;
    }

    /* Сообщение об ошибке */
    .error-message {
        color: #fa383e;
        background-color: #fff2f2;
        border: 1px solid #fa383e;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>

<main class="login-card">
    <h2>Добро пожаловать!</h2>
    <p>Войдите, чтобы продолжить</p>

    <!-- Блок для отображения ошибок -->
    <?php if (!empty($_SESSION['error_message'])): ?>
        <p class="error-message">
            <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
            ?>
        </p>
    <?php endif; ?>

    <!-- Разделитель -->
    <div class="divider">
        <span>ИЛИ</span>
    </div>

    <!-- Основная форма входа -->
    <form action="/pages/auth.php" method="post" class="login-form">
        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Войти</button>
    </form>

    

<?php
include_once $project_root . '/templates/footer.php';
?>