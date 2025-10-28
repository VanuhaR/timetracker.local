<?php
// ... (начало файла без изменений: session_start, проверка авторизации, получение $user_id и т.д.) ...

// 1. Проверка авторизации
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /pages/login.php');
    exit();
}

// Определяем корень проекта ОДИН РАЗ в самом начале
 $project_root = dirname(__DIR__);

// 2. Получаем данные текущего пользователя
 $current_user = $_SESSION['user'];
 $user_id = $current_user['id'];
 $user_name = $current_user['full_name'];

// 3. Определяем текущий месяц и год для отображения
 $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
 $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// 4. Подключение к БД
require_once $project_root . '/config/db.php';

 $start_date = sprintf('%04d-%02d-01', $year, $month);
 $end_date = date('Y-m-t', strtotime($start_date));

try {
    // --- ГЛАВНОЕ ИЗМЕНЕНИЕ: Новый запрос к представлению ---
    // Мы выбираем нужные поля из `monthly_schedule_view`
    $stmt = $pdo->prepare("
        SELECT work_date, duration_hours, shift_type 
        FROM monthly_schedule_view 
        WHERE user_id = ? AND work_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке графика: " . $e->getMessage());
}

// 5. Преобразуем массив смен в удобный формат
 $schedule_by_day = [];
foreach ($shifts as $shift) {
    $day = date('j', strtotime($shift['work_date']));
    $schedule_by_day[$day] = $shift;
}

// ... (остальная часть кода с подготовкой календаря без изменений) ...

 $first_day_of_month = new DateTime("$year-$month-01");
 $last_day_of_month = new DateTime($end_date);
 $days_in_month = $last_day_of_month->format('t');
 $start_day_of_week = (int)$first_day_of_month->format('w');

 $months_russian = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];

 $days_of_week = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];

function get_nav_link($year, $month, $offset) {
    $date = new DateTime("$year-$month-01");
    $date->modify("$offset month");
    return "?year=" . $date->format('Y') . "&month=" . $date->format('n');
}

include_once $project_root . '/templates/header.php';
?>

<!-- Стили для календаря (мы их немного дополним ниже) -->
<style>
    .schedule-page { padding: 20px; font-family: Arial, sans-serif; }
    .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .schedule-header h1 { margin: 0; font-size: 24px; color: #333; }
    .schedule-nav a { text-decoration: none; color: #007bff; font-size: 18px; padding: 5px 10px; border: 1px solid #007bff; border-radius: 5px; transition: background-color 0.2s; }
    .schedule-nav a:hover { background-color: #007bff; color: white; }
    .schedule-calendar { width: 100%; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .schedule-calendar th, .schedule-calendar td { border: 1px solid #ddd; text-align: center; vertical-align: top; height: 80px; width: 14.28%; }
    .schedule-calendar th { background-color: #f2f2f2; font-weight: bold; padding: 10px 5px; }
    .schedule-calendar td { padding: 5px; background-color: #fff; }
    .schedule-calendar .day-number { font-weight: bold; margin-bottom: 5px; }
    .schedule-calendar .other-month { background-color: #fafafa; color: #aaa; }
    .schedule-calendar .shift-block { padding: 4px 6px; border-radius: 4px; font-size: 14px; font-weight: bold; cursor: default; }
    
    /* --- НОВЫЕ СТИЛИ ДЛЯ РАЗНЫХ ТИПОВ СМЕН --- */
    .schedule-calendar .shift-block.day { background-color: #e7f3ff; color: #0056b3; } /* Дневная */
    .schedule-calendar .shift-block.night { background-color: #f0e6ff; color: #5a2d8f; } /* Ночная */
    .schedule-calendar .shift-block.day-off { background-color: #e6e6e6; color: #555; } /* Выходной (Б) */
</style>

<main class="schedule-page">
    <!-- ... (HTML заголовка без изменений) ... -->
    <div class="schedule-header">
        <h1>Индивидуальный график: <?php echo htmlspecialchars($user_name); ?></h1>
        <div class="schedule-nav">
            <a href="<?php echo get_nav_link($year, $month, -1); ?>">← Предыдущий</a>
            <span style="margin: 0 15px; font-weight: bold;"><?php echo htmlspecialchars($months_russian[$month]) . ' ' . $year; ?></span>
            <a href="<?php echo get_nav_link($year, $month, +1); ?>">Следующий →</a>
        </div>
    </div>

    <table class="schedule-calendar">
        <thead>
            <tr>
                <?php foreach ($days_of_week as $day): ?>
                    <th><?php echo $day; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $current_day = 1;
            while ($current_day <= $days_in_month) {
                echo "<tr>";
                for ($i = 0; $i < 7; $i++) {
                    if (($current_day == 1 && $i < $start_day_of_week) || $current_day > $days_in_month) {
                        echo '<td class="other-month"></td>';
                    } else {
                        echo '<td>';
                        echo '<div class="day-number">' . $current_day . '</div>';
                        
                        if (isset($schedule_by_day[$current_day])) {
                            $shift = $schedule_by_day[$current_day];
                            
                            // --- ГЛАВНОЕ ИЗМЕНЕНИЕ В ОТОБРАЖЕНИИ ---
                            // Определяем класс для стилизации в зависимости от типа смены
                            $shift_class = 'day'; // класс по умолчанию
                            if (isset($shift['shift_type'])) {
                                $shift_type = strtolower($shift['shift_type']);
                                if ($shift_type == 'ночная') $shift_class = 'night';
                                if ($shift_type == 'б') $shift_class = 'day-off';
                            }

                            echo '<div class="shift-block ' . $shift_class . '">';
                            
                            // Выводим продолжительность смены или тип смены для выходного
                            if (isset($shift['duration_hours']) && $shift['duration_hours'] > 0) {
                                echo htmlspecialchars($shift['duration_hours']) . 'ч';
                            } else {
                                echo htmlspecialchars($shift['shift_type']);
                            }
                            echo '</div>';
                        }
                        echo '</td>';
                        $current_day++;
                    }
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</main>

<?php
include_once $project_root . '/templates/footer.php';
?>