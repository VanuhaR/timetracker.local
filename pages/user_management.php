<?php
// 1. Проверяем авторизацию
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /pages/login.php');
    exit();
}

// В самом начале user_management.php, после session_start()
if (isset($_SESSION['success_message'])) {
    echo "<p class='success-message' style='color: green; border: 1px solid green; padding: 10px;'>" . $_SESSION['success_message'] . "</p>";
    unset($_SESSION['success_message']);
}

// 2. Подключаем шаблоны и БД
 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php'; 
include_once $project_root . '/templates/footer.php';
?>

<main>
    <h1>Управление сотрудниками</h1>
    
    <!-- ВОТ ЭТА КНОПКА! Убедись, что она есть. -->
    
        <a href="/pages/create_user.php" class="btn-add">Добавить нового сотрудника</a>
    
    <!-- ВОТ ФОРМА ПОИСКА -->
    <form method="GET" action="/pages/user_management.php" class="search-form">
        <input type="text" name="search" placeholder="Поиск по ФИО..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Найти</button>
    </form>

    <p>Полный список всех сотрудников в системе.</p>

    <?php
    // ... остальной твой PHP-код для получения данных из БД ...
    // (он начинается с try { $stmt = $pdo->query( ... ))
    ?>
<main>

    <?php
    // 3. Получаем данные из базы, объединяя таблицы для получения полной информации
        try {
        // Формируем базовый запрос
        $sql = "SELECT u.id, u.full_name, u.phone1, u.hire_date, p.position_name, p.base_salary, d.department_name
                FROM users u
                JOIN positions p ON u.position_id = p.id
                JOIN departments d ON u.department_id = d.id";
        
        // Если был поиск, добавляем условие WHERE
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $search_term = '%' . trim($_GET['search']) . '%';
            $sql .= " WHERE u.full_name LIKE :search_term";
        }
        
        $sql .= " ORDER BY u.full_name";

        // Выполняем запрос
        $stmt = $pdo->prepare($sql);
        if (isset($search_term)) {
            $stmt->execute([':search_term' => $search_term]);
        } else {
            $stmt->execute();
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Проверяем, есть ли пользователи для отображения
        if ($users) {
            echo "<table>";
            echo "<thead>";
            echo "<tr><th>ФИО</th><th>Должность</th><th>Отделение</th><th>Оклад</th><th>Телефон</th><th>Дата найма</th><th>Действия</th></tr>";
            echo "</thead>";
            echo "<tbody>";

            // 5. Выводим каждого пользователя в цикле
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['position_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['department_name']) . "</td>";
                echo "<td>" . number_format($user['base_salary'], 2, '.', ' ') . " ₽</td>";
                echo "<td>" . htmlspecialchars($user['phone1']) . "</td>";
                echo "<td>" . htmlspecialchars($user['hire_date']) . "</td>";
                echo "<td>
                    <a href='/pages/edit_user.php?id=" . $user['id'] . "' class='btn-edit'>Редактировать</a>
                    <a href='/pages/delete_user.php?id=" . $user['id'] . "' class='btn-delete' onclick=\"return confirm('Вы уверены, что хотите удалить этого сотрудника?');\">Удалить</a>
                </td>"; // Заглушка для кнопки
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>В системе еще нет ни одного сотрудника.</p>";
        }

    } catch (PDOException $e) {
        echo "<p style='color: red;'>Ошибка при загрузке данных: " . $e->getMessage() . "</p>";
    }
    ?>

</main>

<?php
include_once $project_root . '/templates/footer.php';
?>