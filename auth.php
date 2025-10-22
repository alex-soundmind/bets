<?php
require_once 'config.php';
session_start();

// Если пользователь уже вошел, перенаправляем на главную
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$page_mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

        // Логика входа
        if (!empty($phone) && !empty($password)) {
            $stmt = $pdo->prepare('SELECT * FROM employees WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();

            if ($user && $password === $user['password']) { // В реальном приложении используйте password_hash() и password_verify()
                $_SESSION['user'] = $user;
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Неверный номер или пароль.';
            }
        } else {
            $error_message = 'Введите номер и пароль.';
        }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $page_mode === 'login' ? 'Вход' : 'Регистрация' ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 5rem;">
        <h2><?= $page_mode === 'login' ? 'Вход в систему' : 'Создание аккаунта' ?></h2>

        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="phone">Номер</label>
            <input type="phone" id="phone" name="phone" required>

            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="<?= $page_mode === 'login' ? 'Войти' : 'Зарегистрироваться' ?>">
        </form>
    </div>
</body>
</html>