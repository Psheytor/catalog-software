<?php
session_start();

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $users = json_decode(file_get_contents('data/users.json'), true)['users'];
    
    foreach($users as $user) {
        if($user['login'] == $login && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'login' => $user['login'],
                'name' => $user['name'],
                'role' => $user['role'],
                'pc_ids' => $user['pc_ids']
            ];
            header('Location: /index.php');
            exit;
        }
    }
    $error = 'Неверный логин или пароль';
}

// Если уже авторизован
if(isset($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Каталог ПО</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/favicon.ico">
</head>
<body>
    <header>
        <nav>
            <div class="logo">ПО Каталог</div>
            <ul class="nav-menu">
                <li><a href="/index.php">Главная</a></li>
                <li><a href="/catalog.php">Каталог ПО</a></li>
                <li><a href="/login.php">Вход</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <h1>Вход в систему</h1>
            <?php if($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <input type="text" name="login" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit">Войти</button>
            </form>
            <p class="demo">Демо: login: <strong>ivanov</strong> или <strong>admin</strong>, пароль: <strong>password</strong></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО</p>
    </footer>
</body>
</html>