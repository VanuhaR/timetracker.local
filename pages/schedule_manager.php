<?php
// 1. Проверяем авторизацию
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] != 2 && $_SESSION['user']['role'] != 1)) {
    header('Location: /pages/login.php');
    exit();
}

// 2. Подключаем БД и шаблоны
 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php'; 
include_once $project_root . '/templates/footer.php';

// 3. Получаем списки для фильтров
try {
    $groups = $pdo->query("SELECT * FROM schedule_groups ORDER BY group_name")->fetchAll(PDO::FETCH_ASSOC);
    $work_types = $pdo->query("SELECT * FROM work_types ORDER BY duration_hours DESC")->fetchAll(PDO::FETCH_ASSOC);
    // Создаем массив для быстрого доступа к длительности смены
    $work_durations = [];
    foreach ($work_types as $type) {
        $work_durations[$type['id']] = $type['duration_hours'];
    }
} catch (PDOException $e) {
    die("Ошибка при загрузке данных: " . $e->getMessage());
}

// 4. Обработка фильтров
 $selected_group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;
 $selected_month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

 $employees_to_show = [];
 $schedules_data = [];

if ($selected_group_id) {
    try {
        // Находим все должности, входящие в выбранную группу
        $stmt = $pdo->prepare("SELECT position_id FROM position_group_link WHERE schedule_group_id = :group_id");
        $stmt->execute([':group_id' => $selected_group_id]);
        $position_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if ($position_ids) {
            // Получаем сотрудников, СРАЗУ ЖЕ СОРТИРУЯ ПО ДОЛЖНОСТИ, ОТДЕЛЕНИЮ, ФИО
            $placeholders = implode(',', array_fill(0, count($position_ids), '?'));
            $sql = "SELECT u.id, u.full_name, p.position_name, d.department_name FROM users u JOIN positions p ON u.position_id = p.id JOIN departments d ON u.department_id = d.id WHERE u.position_id IN ($placeholders) ORDER BY p.position_name, d.department_name, u.full_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($position_ids);
            $employees_to_show = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Получаем все существующие смены для этих сотрудников на выбранный месяц
            $start_date = $selected_month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            $user_ids = array_column($employees_to_show, 'id');
            
            if ($user_ids) {
                $in_placeholders = [];
                $params = [];
                foreach ($user_ids as $i => $user_id) {
                    $placeholder = ':id' . $i;
                    $in_placeholders[] = $placeholder;
                    $params[$placeholder] = $user_id;
                }
                $placeholders_string = implode(',', $in_placeholders);
                
                $sql = "SELECT user_id, work_date, work_type_id FROM schedules WHERE user_id IN ($placeholders_string) AND work_date BETWEEN :start_date AND :end_date";
                $stmt = $pdo->prepare($sql);
                $params[':start_date'] = $start_date;
                $params[':end_date'] = $end_date;
                $stmt->execute($params);
                $schedules_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($schedules_result as $schedule) {
                    $schedules_data[$schedule['user_id']][$schedule['work_date']] = $schedule['work_type_id'];
                }
            }
        }
    } catch (PDOException $e) {
        die("Ошибка при загрузке данных: " . $e->getMessage());
    }
}
?>

<main>
    <h1>Редактирование графика</h1>
    <form method="POST" action="/pages/schedule_manager.php" class="filter-form">
        <div class="form-group">
            <label for="group_id">Выберите группу:</label>
            <select name="group_id" id="group_id" required onchange="this.form.submit()">
                <option value="">-- Выберите группу --</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group['id']; ?>" <?php if ($selected_group_id == $group['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($group['group_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="month">Выберите месяц:</label>
            <input type="month" name="month" id="month" value="<?php echo htmlspecialchars($selected_month); ?>" onchange="this.form.submit()">
        </div>
    </form>
    <hr>
    <!-- ПАНЕЛЬ БЫСТРОГО ВЫБОРА СМЕН -->
<div class="quick-actions">
    <p>Быстрое заполнение:</p>
    <button type="button" class="quick-set-btn active" data-shift-id="">Отмена</button>
    <?php foreach ($work_types as $type): ?>
        <button type="button" class="quick-set-btn" data-shift-id="<?php echo $type['id']; ?>">
            <?php echo htmlspecialchars($type['type_name']); ?>
        </button>
    <?php endforeach; ?>
</div>
<hr>
    <?php if ($employees_to_show): ?>
        <h3>График за <?php echo date('F Y', strtotime($selected_month . '-01')); ?></h3>
        <div class="schedule-table-container">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th class="employee-col">Сотрудник</th>
                        <?php
                            $days_in_month = date('t', strtotime($selected_month . '-01'));
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                echo "<th class='day-col'>{$day}</th>";
                            }
                        ?>
                        <th class="total-hours-col">Всего часов</th> <!-- Новая колонка -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $current_position = '';
                    $current_department = '';
                    foreach ($employees_to_show as $employee):
                        // Считаем общее количество часов для текущего сотрудника
                        $total_hours = 0;
                        if (isset($schedules_data[$employee['id']])) {
                            foreach ($schedules_data[$employee['id']] as $shift_id) {
                                if (isset($work_durations[$shift_id])) {
                                    $total_hours += $work_durations[$shift_id];
                                }
                            }
                        }

                        // Если должность изменилась, выводим заголовок для новой должности
                        if ($employee['position_name'] !== $current_position):
                    ?>
                        <tr class="position-header-row">
                            <td colspan="<?php echo $days_in_month + 2; ?>" class="position-header">
                                <?php echo htmlspecialchars($employee['position_name']); ?>
                            </td>
                        </tr>
                    <?php
                            $current_position = $employee['position_name'];
                            $current_department = ''; // Сбрасываем отделение, т.к. новая должность
                        endif;

                        // Если отделение изменилось внутри той же должности, выводим заголовок для нового отделения
                        if ($employee['department_name'] !== $current_department):
                    ?>
                        <tr class="department-header-row">
                            <td colspan="<?php echo $days_in_month + 2; ?>" class="department-header">
                                <?php echo htmlspecialchars($employee['department_name']); ?>
                            </td>
                        </tr>
                    <?php
                            $current_department = $employee['department_name'];
                        endif;
                    ?>
                    <tr>
                        <td class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                        <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                            <?php
                                $current_date = $selected_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $shift_id = isset($schedules_data[$employee['id']][$current_date]) ? $schedules_data[$employee['id']][$current_date] : null;
                                $shift_name = '';
                                if ($shift_id) {
                                    foreach($work_types as $type) {
                                        if ($type['id'] == $shift_id) {
                                            $shift_name = htmlspecialchars($type['type_name']);
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <td class="schedule-cell editable" 
                                data-user-id="<?php echo $employee['id']; ?>" 
                                data-date="<?php echo $current_date; ?>"
                                data-shift-id="<?php echo $shift_id ?: ''; ?>">
                                <?php echo $shift_name; ?>
                            </td>
                        <?php endfor; ?>
                        <td class="total-hours-cell"><?php echo number_format($total_hours, 1); ?></td> <!-- Ячейка с итогом -->
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($selected_group_id): ?>
        <p>В этой группе нет сотрудников.</p>
    <?php else: ?>
        <p>Пожалуйста, выберите группу для отображения графика.</p>
    <?php endif; ?>

</main>

<!-- СКРИПТ ОСТАЕТСЯ ПРЕЖНИМ, НО ЕГО НУЖНО ОБНОВИТЬ, ЧТОБЫ ОН УЧИТЫВАЛ НОВУЮ КОЛОНКУ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.schedule-table');
    if (!table) return;
    
    const quickButtons = document.querySelectorAll('.quick-set-btn');
    let massEditMode = null; // Может быть null, 'CANCEL' или ID смены

    // Создаем объект с именами смен для быстрого доступа
    const shiftNames = <?php echo json_encode(array_map('trim', array_column($work_types, 'type_name', 'id'))); ?>;

    // Обработка клика по кнопкам быстрого выбора
    quickButtons.forEach(button => {
        button.addEventListener('click', function() {
            quickButtons.forEach(btn => btn.classList.remove('active'));
            if (this.dataset.shiftId === '') {
                massEditMode = 'CANCEL'; // Устанавливаем специальный режим отмены
                console.log('Entered CANCEL mode.');
            } else {
                massEditMode = this.dataset.shiftId;
                this.classList.add('active');
                console.log('Entered mass edit mode with shift ID:', massEditMode);
            }
        });
    });

    // Обработка клика по ячейке
    table.addEventListener('click', function(e) {
        const cell = e.target.closest('.editable');
        if (!cell) return;

        // ЕСЛИ МЫ В ЛЮБОМ ИЗ РЕЖИМОВ (быстрое заполнение или отмена)
        if (massEditMode !== null) {
            const userId = cell.dataset.userId;
            const date = cell.dataset.date;
            let newShiftId = '';
            let shiftName = '';

            if (massEditMode === 'CANCEL') {
                // Если режим отмены, очищаем ячейку
                newShiftId = '';
                shiftName = '';
                console.log('Action: CANCEL. Clearing cell.');
            } else {
                // Иначе, устанавливаем смену
                newShiftId = massEditMode;
                const button = document.querySelector(`.quick-set-btn[data-shift-id="${newShiftId}"]`);
                if (button) {
                    shiftName = button.textContent.trim();
                }
                console.log(`Action: SET. Setting shift to "${shiftName}".`);
            }

            fetch('/pages/update_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, work_date: date, work_type_id: newShiftId })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    cell.textContent = shiftName;
                    cell.dataset.shiftId = newShiftId;
                    console.log('Cell updated successfully.');
                } else {
                    alert('Ошибка сохранения: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Произошла ошибка при сохранении.');
            });
            return; // Выходим, чтобы не выполнять старую логику
        }

        // СТАРАЯ ЛОГИКА (если не в режиме быстрого заполнения)
        if (cell.querySelector('select')) return;
        // ... остальная часть скрипта для выпадающего списка остается без изменений ...
    });
});
</script>

<!-- УЛУЧШЕННЫЕ СТИЛИ -->
<style>
.schedule-table-container { overflow-x: auto; border: 1px solid #ccc; border-radius: 5px; background: #fff; }
.schedule-table { border-collapse: collapse; width: 100%; min-width: 700px; font-size: 14px; }
.schedule-table th, .schedule-table td { border: 1px solid #ddd; text-align: center; vertical-align: middle; }
.schedule-table th { background-color: #f2f2f2; font-weight: bold; position: sticky; top: 0; z-index: 10; }
.employee-col { width: 150px; min-width: 150px; text-align: left; }
.day-col { width: 35px !important; min-width: 35px !important; max-width: 35px !important; padding: 2px 1px !important; font-size: 12px; }
.total-hours-col { width: 80px; min-width: 80px; font-weight: bold; background-color: #f8f9fa; } /* Стиль для новой колонки */
.schedule-cell { width: 35px !important; min-width: 35px !important; max-width: 35px !important; padding: 2px 1px !important; font-size: 11px; word-wrap: break-word; cursor: pointer; }
.employee-name { font-weight: bold; white-space: nowrap; font-size: 16px; }
.position-header-row .position-header { background-color: #e9ecef; font-weight: bold; text-align: center; font-size: 15px; padding: 8px 4px; }
.department-header-row .department-header { background-color: #f1f3f4; font-style: italic; text-align: center; font-size: 13px; padding: 6px 4px; } /* Стиль для нового заголовка */
.schedule-cell:hover { background-color: #f0f8ff; }
.schedule-cell select { width: 100%; font-size: 11px; }
</style>

