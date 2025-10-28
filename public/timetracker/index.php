<?php
session_start();

// Вычисляем корневую папку проекта.
 $project_root = dirname(__DIR__, 2);

include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php';
include_once $project_root . '/templates/main.php';
include_once $project_root . '/templates/footer.php';
?>