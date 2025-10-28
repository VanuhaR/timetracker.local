<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учета рабочего времени</title>
    <link rel="stylesheet" href="/styles/style.css">
    <script src="https://unpkg.com/imask"></script>
</head>
<body>
    <?php // --- Добавляем проверку здесь --- ?>
    <?php if (!isset($hide_sidebar) || !$hide_sidebar): ?>
        <div class="sidebar">
            <?php
            // Показываем навигацию только если пользователь авторизован
            if (isset($_SESSION['user'])) {
                include_once $_SERVER['DOCUMENT_ROOT'] . '/templates/nav.php';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="main-content">