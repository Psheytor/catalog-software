<?php
session_start();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    include('403.html');
    exit;
}

$user = $_SESSION['user'];

// Данные администратора
$admin_info = [
    'position' => 'Системный администратор',
    'department' => 'IT-дирекция',
    'email' => $user['login'] . '@company.ru',
    'phone' => '+7 (999) 888-88-88',
    'office' => 'Кабинет 501',
    'managed_employees' => 25,
    'managed_pcs' => 120
];

// Загрузка данных
$software = json_decode(file_get_contents('data/software.json'), true)['software'];
$licenses = json_decode(file_get_contents('data/licenses.json'), true)['licenses'];
$installs = json_decode(file_get_contents('data/installs.json'), true)['installs'];

function getLicenseStatus($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;
    
    if ($today > $expiry) return 'истекла';
    if ($diff <= 30) return 'истекает';
    return 'активна';
}

$licenses_info = [];
foreach ($licenses as $license) {
    $sw = array_values(array_filter($software, fn($s) => $s['id'] == $license['software_id']))[0] ?? null;
    if ($sw) {
        $install_count = count(array_filter($installs, fn($i) => $i['software_id'] == $sw['id']));
        
        $licenses_info[] = [
            'software_name' => $sw['name'],
            'software_type' => $sw['type'],
            'manufacturer' => $sw['manufacturer'],
            'license_key' => $license['license_key'],
            'max_installs' => $license['max_installs'],
            'current_installs' => $install_count,
            'expiry_date' => $license['expiry_date'],
            'status' => getLicenseStatus($license['expiry_date'])
        ];
    }
}

usort($licenses_info, function($a, $b) {
    return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
});

$shortage_licenses = array_filter($licenses_info, fn($l) => $l['current_installs'] > $l['max_installs']);
$expiring_licenses = array_filter($licenses_info, fn($l) => $l['status'] == 'истекает');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администратор | Каталог ПО</title>
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
                <li><a href="/admin.php">Администратор</a></li>
                <li><a href="/logout.php">Выйти</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <nav aria-label="Хлебные крошки">
            <ol class="breadcrumbs">
                <li><a href="/index.php">Главная</a></li>
                <li aria-current="page">Администратор</li>
            </ol>
        </nav>

        <h1>Панель администратора</h1>

        <!-- Карточка администратора -->
        <div class="employee-card">
            <div class="employee-avatar">
                <img src="/images/avatars/admin.png" 
                     alt="Администратор" 
                     onerror="this.src='/images/avatars/default.png'">
            </div>
            <div class="employee-info">
                <h2><?= htmlspecialchars($user['name']) ?></h2>
                <p class="employee-login">Роль: Администратор системы</p>
                <div class="employee-details">
                    <p><strong>Должность:</strong> <?= $admin_info['position'] ?></p>
                    <p><strong>Отдел:</strong> <?= $admin_info['department'] ?></p>
                    <p><strong>Email:</strong> <a href="mailto:<?= $admin_info['email'] ?>"><?= $admin_info['email'] ?></a></p>
                    <p><strong>Телефон:</strong> <?= $admin_info['phone'] ?></p>
                    <p><strong>Кабинет:</strong> <?= $admin_info['office'] ?></p>
                </div>
                <div class="admin-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-number"><?= $admin_info['managed_employees'] ?></span>
                        <span class="mini-stat-label">сотрудников</span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-stat-number"><?= $admin_info['managed_pcs'] ?></span>
                        <span class="mini-stat-label">ПК в управлении</span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-stat-number"><?= count($software) ?></span>
                        <span class="mini-stat-label">программ в каталоге</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Отчеты -->
        <?php if(!empty($shortage_licenses) || !empty($expiring_licenses)): ?>
            <div class="reports-section">
                <h2>Отчеты</h2>
                
                <?php if(!empty($shortage_licenses)): ?>
                    <div class="report-card shortage">
                        <h3>Нехватка лицензий</h3>
                        <ul>
                            <?php foreach($shortage_licenses as $license): ?>
                                <li>
                                    <strong><?= htmlspecialchars($license['software_name']) ?></strong> — 
                                    установлено: <?= $license['current_installs'] ?>, 
                                    доступно: <?= $license['max_installs'] ?>, 
                                    превышение: <span class="alert"><?= $license['current_installs'] - $license['max_installs'] ?> шт.</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($expiring_licenses)): ?>
                    <div class="report-card expiring">
                        <h3>Истекающие лицензии</h3>
                        <ul>
                            <?php foreach($expiring_licenses as $license): ?>
                                <li>
                                    <strong><?= htmlspecialchars($license['software_name']) ?></strong> — 
                                    истекает: <?= date('d.m.Y', strtotime($license['expiry_date'])) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Таблица с информацией о лицензиях -->
        <section class="licenses-section">
            <h2>Реестр лицензий</h2>
            
            <?php if(empty($licenses_info)): ?>
                <p>Лицензии не найдены.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="licenses-table">
                        <thead>
                            <tr>
                                <th>ПО</th>
                                <th>Производитель</th>
                                <th>Тип</th>
                                <th>Лицензионный ключ</th>
                                <th>Установки</th>
                                <th>Срок действия</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($licenses_info as $license): ?>
                                <tr class="<?= $license['current_installs'] > $license['max_installs'] ? 'shortage' : '' ?>">
                                    <td data-label="ПО"><strong><?= htmlspecialchars($license['software_name']) ?></strong></td>
                                    <td data-label="Производитель"><?= htmlspecialchars($license['manufacturer']) ?></td>
                                    <td data-label="Тип"><?= htmlspecialchars($license['software_type']) ?></td>
                                    <td data-label="Лицензионный ключ"><code><?= htmlspecialchars($license['license_key']) ?></code></td>
                                    <td data-label="Установки" class="<?= $license['current_installs'] > $license['max_installs'] ? 'warning' : '' ?>">
                                        <?= $license['current_installs'] ?> / <?= $license['max_installs'] ?>
                                        <?php if($license['current_installs'] > $license['max_installs']): ?>
                                            <span class="alert-icon">⚠️</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Срок действия"><?= date('d.m.Y', strtotime($license['expiry_date'])) ?></td>
                                    <td data-label="Статус">
                                        <span class="status <?= $license['status'] ?>">
                                            <?= $license['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО. Все права защищены.</p>
    </footer>
</body>
</html>