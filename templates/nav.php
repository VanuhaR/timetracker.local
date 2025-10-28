<?php
// templates/nav.php
if (isset($_SESSION['user'])):
    $user_role = $_SESSION['user']['role'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // 1. Создаем массив с пунктами, которые видны АБСОЛЮТНО ВСЕМ авторизованным пользователям
 $nav_items = [
    'dashboard.php' => 'Главная панель',
    'vacation_calendar.php' => 'Календарь отпусков',
];

// 2. Добавляем "Индивидуальный график работ" для всех, КРОМЕ директора (роль 1)
if ($user_role != 1) {
    $nav_items['general_schedule.php'] = 'Индивидуальный график работ';
}

// 3. Добавляем пункты только для старшей медсестры (роль 2)
if ($user_role == 2) {
    $nav_items['schedule_manager.php'] = 'Общий график работ';
}

// 4. Добавляем пункты только для директора (роль 1)
if ($user_role == 1) {
    $nav_items['user_management.php'] = 'Управление сотрудниками';
    $nav_items['settings.php'] = 'Настройки';
}

// Теперь в переменной $nav_items находится правильный набор пунктов для текущего пользователя
// и вы можете его вывести в цикле
?>
<nav>
    <ul>
        <?php foreach ($nav_items as $page => $title): ?>
            <li>
                <a href="/pages/<?php echo $page; ?>" 
                   class="<?php echo ($current_page == $page) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($title); ?>
                </a>
            </li>
        <?php endforeach; ?>
        <li>
            <a href="/pages/logout.php">Выйти</a>
        </li>
    </ul>
</nav>
<?php else: ?>
    <nav>
        <ul>
            <li><a href="/pages/login.php">Войти</a></li>
        </ul>
    </nav>
<?php endif; ?>