<?php
// Временный файл для генерации правильного хэша
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Сгенерированный хэш для пароля '{$password}':<br>";
echo "<strong style='font-family: monospace; background: #eee; padding: 5px;'>" . $hash . "</strong>";
?>