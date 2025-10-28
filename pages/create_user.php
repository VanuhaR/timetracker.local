<?php
// 1. Проверяем авторизацию что то там
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

// 3. Обработка формы, если она была отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $full_name = trim($_POST['full_name']);
    $login = trim($_POST['login']);
    $password = $_POST['password']; // Пароль в открытом виде
    $phone1 = trim($_POST['phone1']);
    $phone2 = trim($_POST['phone2']);
    $position_id = (int)$_POST['position_id'];
    $department_id = (int)$_POST['department_id'];
    $role_id = (int)$_POST['role_id']; // Роль тоже нужно выбрать
    $hire_date = $_POST['hire_date'];
    $gender = $_POST['gender'];

    // Простая валидация
    if (empty($full_name) || empty($login) || empty($password)) {
        $error_message = "ФИО, логин и пароль не могут быть пустыми.";
    } else {
        try {
            // Проверяем, не занят ли логин
            $stmt = $pdo->prepare("SELECT id FROM users WHERE login = :login");
            $stmt->execute([':login' => $login]);
            if ($stmt->fetch()) {
                $error_message = "Этот логин уже занят. Выберите другой.";
            } else {
                // Хешируем пароль
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Вставляем нового пользователя в базу
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, login, password_hash, phone1, phone2, position_id, department_id, role_id, hire_date, gender) 
                     VALUES (:full_name, :login, :password_hash, :phone1, :phone2, :position_id, :department_id, :role_id, :hire_date, :gender)"
                );
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
                    ':gender' => $gender
                ]);

                $_SESSION['success_message'] = "Новый сотрудник успешно добавлен!";
                header('Location: /pages/user_management.php');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}

// 4. Получаем списки для выпадающих списков
try {
    $positions = $pdo->query("SELECT * FROM positions ORDER BY position_name")->fetchAll(PDO::FETCH_ASSOC);
    $departments = $pdo->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC); // Добавляем роли
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// 5. Выводим HTML
include_once $project_root . '/templates/header.php';
?>

<main>
    <h1>Добавление нового сотрудника</h1>
    
    <?php if (isset($error_message)): ?>
        <p class="error-message" style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="/pages/create_user.php" method="post">
        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" required>
        </div>

        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required>
        </div>

        <div class="form-group">
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="role_id">Роль в системе:</label>
            <select id="role_id" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="position_id">Должность:</label>
            <select id="position_id" name="position_id" required>
                <?php foreach ($positions as $position): ?>
                    <option value="<?php echo $position['id']; ?>"><?php echo htmlspecialchars($position['position_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="department_id">Отделение:</label>
            <select id="department_id" name="department_id" required>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['department_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="gender">Пол:</label>
            <select id="gender" name="gender" required>
                <option value="male">Мужской</option>
                <option value="female">Женский</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone1">Телефон 1:</label>
            <input type="tel" id="phone1" name="phone1" class="phone-mask">

        </div>
        <div class="form-group">
            <label for="phone2">Телефон 2:</label>
            <input type="tel" id="phone2" name="phone2" class="phone-mask">

        </div>

        <div class="form-group">
            <label for="hire_date">Дата найма:</label>
            <input type="date" id="hire_date" name="hire_date" required>
        </div>

        <button type="submit">Добавить сотрудника</button>
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