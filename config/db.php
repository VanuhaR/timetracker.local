<?php
// config/db.php
 $db_host = 'mysql-8.4'; // Или '127.0.0.1'
 $db_name = 'TimeTracker';
 $db_user = 'root';
 $db_pass = ''; // Пароль по умолчанию пустой

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>