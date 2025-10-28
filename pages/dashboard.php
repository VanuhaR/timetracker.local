<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /pages/login.php');
    exit();
}

 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php';
include_once $project_root . '/templates/footer.php';

// Получаем данные для текущего пользователя из нашего нового VIEW
try {
    $stmt = $pdo->prepare("SELECT * FROM dashboard_view WHERE user_id = :id");
    $stmt->execute([':id' => $_SESSION['user']['id']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке данных: " . $e->getMessage());
}
?>

<main>
    <h1>Главная панель</h1>
    <p>Добро пожаловать, <?php echo htmlspecialchars($user_data['full_name']); ?>!</p>

    <div class="dashboard-grid">
        <!-- Блок 1: Личная информация -->
        <div class="widget">
            <h3>Личная информация</h3>
            <table class="widget-table">
                <tr><td>Должность:</td><td><?php echo htmlspecialchars($user_data['position_name']); ?></td></tr>
                <tr><td>Пол:</td><td><?php echo ($user_data['gender'] === 'male') ? 'Мужской' : 'Женский'; ?></td></tr>
                <tr><td>Возраст:</td><td><?php echo (new DateTime($user_data['hire_date']))->diff(new DateTime())->y; ?> лет</td></tr>
                <tr><td>Стаж работы:</td><td><?php echo (new DateTime($user_data['hire_date']))->diff(new DateTime())->y; ?> лет</td></tr>
                <tr><td>Роль в системе:</td><td>
                    <?php
                    switch ($_SESSION['user']['role']) {
                        case 1: echo 'Директор'; break;
                        case 2: echo 'Старшая медсестра'; break;
                        case 3: echo 'Сотрудник'; break;
                        default: echo 'Неизвестна';
                    }
                    ?>
                </td></tr>
                <tr><td>Отделение:</td><td><?php echo htmlspecialchars($user_data['department_name']); ?></td></tr>
            </table>
        </div>

        <!-- Блок 2: Зарплата за месяц -->
        <div class="widget">
            <h3>Зарплата за <?php echo date('F Y'); ?></h3>
            <table class="widget-table">
                <tr><td>Фиксированный оклад:</td><td><?php echo number_format($user_data['base_salary'], 2, '.', ' '); ?> ₽</td></tr>
                <tr><td>Северная надбавка:</td><td><?php echo number_format($user_data['base_salary'] * 1.4, 2, '.', ' '); ?> ₽</td></tr>
                <tr><td>Надбавка за стаж:</td><td><?php echo $user_data['experience_bonus_percent']; ?>%</td></tr>
                <tr><td>Районный коэффициент:</td><td><?php echo $user_data['regional_coefficient']; ?></td></tr>
            </table>
        </div>

        <!-- Блок 3: Статистика за месяц -->
        <div class="widget">
            <h3>Статистика за <?php echo date('F Y'); ?></h3>
            <table class="widget-table">
                <tr><td>Отработано часов:</td><td><?php echo number_format($user_data['worked_hours_current_month'], 2, '.', ' '); ?></td></tr>
                <tr><td>Запланировано часов:</td><td><?php echo number_format($user_data['scheduled_hours_current_month'], 2, '.', ' '); ?></td></tr>
                <tr><td>Норма часов:</td><td><?php echo number_format($user_data['norm_hours'], 2, '.', ' '); ?></td></tr>
            </table>
        </div>

        <!-- Блок 4: Предстоящий отпуск -->
        <div class="widget">
            <h3>Предстоящий отпуск</h3>
            <?php if ($user_data['next_vacation_start']): ?>
                <p>С <strong><?php echo date('d.m.Y', strtotime($user_data['next_vacation_start'])); ?></strong> по <strong><?php echo date('d.m.Y', strtotime($user_data['next_vacation_end'])); ?></strong></p>
            <?php else: ?>
                <p>Предстоящих отпусков не запланировано.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include_once $project_root . '/templates/footer.php';
?>