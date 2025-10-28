<?php
// 1. Проверяем авторизацию
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /pages/login.php');
    exit();
}

// 2. Подключаем БД
 $project_root = dirname(__DIR__);
include_once $project_root . '/config/db.php';
include_once $project_root . '/templates/header.php';
include_once $project_root . '/templates/nav.php'; 
include_once $project_root . '/templates/footer.php';

// 3. Получаем ID пользователя из URL и проверяем его
 $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id === 0) {
    die("Ошибка: Не указан ID пользователя.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Получаем данные из формы
    $full_name = trim($_POST['full_name']);
    $login = trim($_POST['login']);
    $password = $_POST['password']; // Новое поле для пароля
    $phone1 = trim($_POST['phone1']);
    $phone2 = trim($_POST['phone2']);
    $position_id = (int)$_POST['position_id'];
    $department_id = (int)$_POST['department_id'];
    $role_id = (int)$_POST['role_id']; // Новое поле для роли
    $hire_date = $_POST['hire_date'];
    $gender = $_POST['gender'];

    // Валидация
    if (empty($full_name) || empty($login) || empty($position_id) || empty($role_id)) {
        $_SESSION['error_message'] = 'Пожалуйста, заполните все обязательные поля.';
        header('Location: /pages/user_management.php');
        exit();
    }

    try {
        // Проверяем, не занят ли логин другим пользователем
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = :login AND id != :id");
        $stmt->execute([':login' => $login, ':id' => $_GET['id']]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'Этот логин уже занят другим пользователем.';
            header('Location: /pages/user_management.php');
            exit();
        }

        // Если введен новый парольль, хешируем его
        $password_hash = null;
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
        }

        // Обновляем данные в базе
        $sql = "UPDATE users SET 
                    full_name = :full_name, 
                    login = :login, 
                    password_hash = :password_hash, 
                    phone1 = :phone1, 
                    phone2 = :phone2, 
                    position_id = :position_id, 
                    department_id = :department_id, 
                    role_id = :role_id, 
                    hire_date = :hire_date, 
                    gender = :gender 
                 WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':login' => $login,
            ':password_hash' => $password_hash,
            ':phone1' => $phone1,
            ':phone2' => $phone2,
            ':position_id' => $position_id,
            ':department_id' => $department_id,
            ':role_id' => $role_id,
            ':hire_date' => $hire_date,
            ':gender' => $gender,
            ':id' => $_GET['id']
        ]);

        $_SESSION['success_message'] = "Данные сотрудника успешно обновлены!";
        header('Location: /pages/user_management.php');
        exit();

    } catch (PDOException $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    }
}

// 5. Если форма не отправлена (или была ошибка), получаем текущие данные пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Ошибка: Пользователь с таким ID не найден.");
    }

    $positions = $pdo->query("SELECT * FROM positions ORDER BY position_name")->fetchAll(PDO::FETCH_ASSOC);
    $departments = $pdo->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// 6. И только теперь, когда вся логика выполнена, выводим HTML
include_once $project_root . '/templates/header.php';
?>

<main>
    <h1>Редактирование сотрудника</h1>
    
    <?php if (isset($error_message)): ?>
        <p class="error-message" style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="/pages/edit_user.php?id=<?php echo $user_id; ?>" method="post">
        <!-- ... вся твоя форма остается без изменений ... -->
        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>

    <div class="form-group">
        <label for="login">Логин:</label>
        <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" required>
    </div>

    <div class="form-group">
        <label for="role_id">Роль в системе:</label>
        <select id="role_id" name="role_id" required>
            <?php
            $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($roles as $role) {
                echo "<option value='{$role['id']}' " . ($user['role_id'] == $role['id'] ? 'selected' : '') . ">{$role['role_name']}</option>";
            }
            ?>
        </select>
    </div>

        <div class="form-group">
            <label for="position_id">Должность:</label>
            <select id="position_id" name="position_id" required>
                <?php foreach ($positions as $position): ?>
                    <option value="<?php echo $position['id']; ?>" <?php if ($position['id'] == $user['position_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($position['position_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="department_id">Отделение:</label>
            <select id="department_id" name="department_id" required>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo $department['id']; ?>" <?php if ($department['id'] == $user['department_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="phone1">Телефон 1:</label>
            <input type="tel" id="phone1" name="phone1" class="phone-mask" value="<?php echo htmlspecialchars($user['phone1']); ?>">
        </div>

        <div class="form-group">
            <label for="phone2">Телефон 2:</label>
            <input type="tel" id="phone2" name="phone2" class="phone-mask" value="<?php echo htmlspecialchars($user['phone2']); ?>">
        </div>

        <div class="form-group">
            <label for="hire_date">Дата найма:</label>
            <input type="date" id="hire_date" name="hire_date" value="<?php echo htmlspecialchars($user['hire_date']); ?>" required>
        </div>

        <button type="submit">Сохранить изменения</button>
        <a href="/pages/user_management.php" class="btn-cancel">Отмена</a>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneElements = document.querySelectorAll('.phone-mask');
        phoneElements.forEach(function(element) {
            IMask(element, {
                mask: '+{7} (000) 000-00-00'
            });
        });
    });
    </script>
</main>

<?php
include_once $project_root . '/templates/footer.php';
?>