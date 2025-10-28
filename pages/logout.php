<?php
// Запускаем сессию, чтобы иметь к ней доступ
session_start();

// Уничтожаем все данные сессии
session_destroy();

// Перенаправляем пользователя на страницу входа
header('Location: /pages/login.php');
exit();
?>