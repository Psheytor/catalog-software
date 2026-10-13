<?php
session_start();

if(!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// Если админ - перенаправляем в админку
if($_SESSION['user']['role'] == 'admin') {
    header('Location: /admin.php');
    exit;
}

$user = $_SESSION['user'];
$installs = json_decode(file_get_contents('data/installs.json'), true)['installs'];
$software = json_decode(file_get_contents('data/software.json'), true)['software'];
$licenses = json_decode(file_get_contents('data/licenses.json'), true)['licenses'];

// ПО на ПК сотрудника
$user_installs = array_filter($installs, fn($i) => in_array($i['pc_id'], $user['pc_ids']));

// Уведомления об истекающих лицензиях
$notifications = [];
$today = new DateTime();
foreach($user_installs as $install) {
    $sw = array_values(array_filter($software, fn($s) => $s['id'] == $install['software_id']))[0] ?? null;
    if($sw) {
        $license = array_values(array_filter($licenses, fn($l) => $l['software_id'] == $sw['id']))[0] ?? null;
        if($license) {
            $expiry = new DateTime($license['expiry_date']);
            $diff = $today->diff($expiry)->days;
            if($today <= $expiry && $diff <= 30) {
                $notifications[] = "Лицензия на {$sw['name']} истекает через {$diff} дней";
            }
        }
    }
}

// Данные сотрудника
$employee_info = [
    'position' => 'Программист',
    'department' => 'IT-отдел',
    'email' => $user['login'] . '@company.ru',
    'phone' => '+7 (999) 123-45-67',
    'office' => 'Кабинет 405'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | Каталог ПО</title>
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
                <li><a href="/profile.php">Личный кабинет</a></li>
                <li><a href="/logout.php">Выйти</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <nav aria-label="Хлебные крошки">
            <ol class="breadcrumbs">
                <li><a href="/index.php">Главная</a></li>
                <li aria-current="page">Личный кабинет</li>
            </ol>
        </nav>

        <h1>Личный кабинет</h1>

        <!-- Карточка сотрудника -->
        <div class="employee-card">
            <div class="employee-avatar">
                <img src="/images/avatars/<?= $user['login'] ?>.png" 
                     alt="<?= htmlspecialchars($user['name']) ?>" 
                     onerror="this.src='/images/avatars/default.png'">
            </div>
            <div class="employee-info">
                <h2><?= htmlspecialchars($user['name']) ?></h2>
                <p class="employee-login">Логин: <?= htmlspecialchars($user['login']) ?></p>
                <div class="employee-details">
                    <p><strong>Должность:</strong> <?= $employee_info['position'] ?></p>
                    <p><strong>Отдел:</strong> <?= $employee_info['department'] ?></p>
                    <p><strong>Email:</strong> <a href="mailto:<?= $employee_info['email'] ?>"><?= $employee_info['email'] ?></a></p>
                    <p><strong>Телефон:</strong> <?= $employee_info['phone'] ?></p>
                    <p><strong>Кабинет:</strong> <?= $employee_info['office'] ?></p>
                </div>
            </div>
        </div>
        
        <?php if(!empty($notifications)): ?>
            <div class="notifications">
                <h3>Уведомления</h3>
                <ul>
                    <?php foreach($notifications as $notif): ?>
                        <li><?= htmlspecialchars($notif) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <h2>ПО, установленное на ваших ПК</h2>
        
        <?php if(empty($user_installs)): ?>
            <p>На ваших ПК не установлено программное обеспечение.</p>
        <?php else: ?>
            <table class="installs-table">
                <thead>
                    <tr><th>ПК</th><th>ПО</th><th>Дата установки</th><th>Срок лицензии</th></tr>
                </thead>
                <tbody>
                    <?php foreach($user_installs as $install): 
                        $sw = array_values(array_filter($software, fn($s) => $s['id'] == $install['software_id']))[0];
                        $license = array_values(array_filter($licenses, fn($l) => $l['software_id'] == $sw['id']))[0];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($install['pc_name']) ?></td>
                            <td><?= htmlspecialchars($sw['name']) ?></td>
                            <td><?= $install['install_date'] ?></td>
                            <td><?= $license ? date('d.m.Y', strtotime($license['expiry_date'])) : 'нет данных' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО</p>
    </footer>
</body>
</html>