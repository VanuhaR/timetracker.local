<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: /pages/login.php');
    exit();
}

 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php';
include_once $project_root . '/templates/footer.php';

 $message = '';
 $errors = [];

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка добавления новой должности
    if (isset($_POST['add_position'])) {
        $position_name = trim($_POST['position_name']);
        if (empty($position_name)) {
            $errors[] = 'Название должности не может быть пустым.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO positions (position_name) VALUES (:position_name)");
                $stmt->execute([':position_name' => $position_name]);
                $message = "Должность '{$position_name}' успешно добавлена.";
            } catch (PDOException $e) {
                $errors[] = "Ошибка при добавлении должности: " . $e->getMessage();
            }
        }
    }

    // Обработка обновления оклада
    if (isset($_POST['update_salary'])) {
        $position_id = (int)$_POST['position_id'];
        $base_salary = (float)$_POST['base_salary'];
        if ($position_id && $base_salary > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE positions SET base_salary = :base_salary WHERE id = :id");
                $stmt->execute([':base_salary' => $base_salary, ':id' => $position_id]);
                $message = "Оклад для должности с ID {$position_id} успешно обновлен.";
            } catch (PDOException $e) {
                $errors[] = "Ошибка при обновлении оклада: " . $e->getMessage();
            }
        } else {
            $errors[] = 'Неверный ID должности или оклад.';
        }
    }
}

// Получаем данные для отображения
try {
    $positions = $pdo->query("SELECT * FROM positions ORDER BY position_name")->fetchAll(PDO::FETCH_ASSOC);
    $settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Ошибка при загрузке данных: " . $e->getMessage());
}
?>

<main>
    <h1>Настройки</h1>

    <?php if ($message): ?>
        <p class="success-message"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="error-container">
            <p><strong>Ошибки:</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Блок 1: Управление должностями -->
        <section class="settings-section">
            <h2>Управление должностями</h2>
            <form method="post" action="/pages/settings.php" class="form-inline">
                <input type="text" name="position_name" placeholder="Название новой должности" required>
                <button type="submit" name="add_position" class="btn">Добавить должность</button>
            </form>
            <hr>
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Должность</th>
                        <th>Оклад</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $position): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                        <td>
                            <form method="post" action="/pages/settings.php" class="form-inline">
                                <input type="hidden" name="update_salary" value="1">
                                <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                <input type="number" name="base_salary" step="100" value="<?php echo $position['base_salary']; ?>" placeholder="Новый оклад" required>
                                <button type="submit" class="btn btn-sm">Обновить</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="/pages/settings.php" onsubmit="return confirm('Вы уверены, что хотите удалить эту должность?');">
                                <input type="hidden" name="delete_position" value="1">
                                <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Блок 2: Глобальные настройки -->
        <section class="settings-section">
            <h2>Глобальные настройки</h2>
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Настройка</th>
                        <th>Значение</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td>
                            <form method="post" action="/pages/settings.php">
                                <input type="hidden" name="update_setting" value="1">
                                <input type="hidden" name="setting_name" value="<?php echo $key; ?>">
                                <input type="text" name="setting_value" value="<?php echo htmlspecialchars($value); ?>" required>
                                <button type="submit" class="btn btn-sm">Сохранить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

</main>

<style>
.settings-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
.settings-section { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; }
.settings-section h2 { margin-top: 0; }
.form-inline { display: flex; gap: 10px; align-items: center; }
.form-inline input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.btn-sm { padding: 4px 8px; font-size: 12px; }
.btn-danger { background-color: #dc3545; color: white; }
.btn-danger:hover { background-color: #c82333; }
</style>

<?php
include_once $project_root . '/templates/footer.php';
?>