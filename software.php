<?php
session_start();

$slug = $_GET['slug'] ?? '';
if(!$slug) {
    header('HTTP/1.1 404 Not Found');
    include('404.html');
    exit;
}

$software = json_decode(file_get_contents('data/software.json'), true)['software'];
$licenses = json_decode(file_get_contents('data/licenses.json'), true)['licenses'];
$installs = json_decode(file_get_contents('data/installs.json'), true)['installs'];

$item = null;
foreach($software as $s) {
    if($s['slug'] == $slug) {
        $item = $s;
        break;
    }
}

if(!$item) {
    header('HTTP/1.1 404 Not Found');
    include('404.html');
    exit;
}

$license = array_values(array_filter($licenses, fn($l) => $l['software_id'] == $item['id']))[0] ?? null;
$install_list = array_filter($installs, fn($i) => $i['software_id'] == $item['id']);

function getLicenseStatus($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    if ($today > $expiry) return 'истекла';
    if ($today->diff($expiry)->days <= 30) return 'истекает';
    return 'активна';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['name']) ?> | Каталог ПО</title>
    <meta name="description" content="<?= htmlspecialchars($item['description']) ?>">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/favicon.ico">
    
    <meta property="og:title" content="<?= htmlspecialchars($item['name']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($item['description']) ?>">
    <meta property="og:type" content="product">
</head>
<body>
    <header>
        <nav>
            <div class="logo">ПО Каталог</div>
            <ul class="nav-menu">
                <li><a href="/index.php">Главная</a></li>
                <li><a href="/catalog.php">Каталог ПО</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <?php if($_SESSION['user']['role'] == 'admin'): ?>
                        <li><a href="/admin.php">Администратор</a></li>
                    <?php else: ?>
                        <li><a href="/profile.php">Личный кабинет</a></li>
                    <?php endif; ?>
                    <li><a href="/logout.php">Выйти</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Вход</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="back-to-catalog">
            <a href="/catalog.php" class="btn btn-secondary">← Назад к каталогу</a>
        </div>

        <div class="software-detail">
            <!-- Верхняя часть: логотип и информация -->
            <div class="software-header">
                <div class="software-logo">
                    <img src="<?= !empty($item['image']) ? $item['image'] : '/images/products/placeholder.png' ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         onerror="this.src='/images/products/placeholder.png'">
                </div>
                <div class="software-info">
                    <h1><?= htmlspecialchars($item['name']) ?></h1>
                    <div class="info-row">
                        <span class="info-label">Производитель:</span>
                        <span class="info-value"><?= htmlspecialchars($item['manufacturer']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Версия:</span>
                        <span class="info-value"><?= htmlspecialchars($item['version']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Тип:</span>
                        <span class="info-value"><?= htmlspecialchars($item['type']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Описание:</span>
                        <span class="info-value"><?= htmlspecialchars($item['description']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Нижняя часть: Лицензионная информация -->
            <?php if($license): ?>
                <div class="license-section">
                    <h2>Лицензионная информация</h2>
                    <div class="license-grid">
                        <div class="license-item">
                            <span class="license-label">Лицензионный ключ:</span>
                            <span class="license-value">
                                <?php if(isset($_SESSION['user'])): ?>
                                    <code><?= htmlspecialchars($license['license_key']) ?></code>
                                <?php else: ?>
                                    <em>Доступно только авторизованным пользователям</em>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="license-item">
                            <span class="license-label">Доступно установок:</span>
                            <span class="license-value"><?= $license['max_installs'] ?></span>
                        </div>
                        <div class="license-item">
                            <span class="license-label">Срок действия:</span>
                            <span class="license-value"><?= date('d.m.Y', strtotime($license['expiry_date'])) ?></span>
                        </div>
                        <div class="license-item">
                            <span class="license-label">Статус:</span>
                            <span class="license-value">
                                <span class="status <?= getLicenseStatus($license['expiry_date']) ?>">
                                    <?= getLicenseStatus($license['expiry_date']) ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Установки на ПК -->
            <div class="installs-section">
                <h2>Установки на ПК</h2>
                <?php if(empty($install_list)): ?>
                    <p class="no-installs">Данное программное обеспечение не установлено ни на одном ПК.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="installs-table">
                            <thead>
                                <tr>
                                    <th>ПК</th>
                                    <th>Ответственный</th>
                                    <th>Дата установки</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($install_list as $install): ?>
                                    <tr>
                                        <td data-label="ПК"><?= htmlspecialchars($install['pc_name']) ?></td>
                                        <td data-label="Ответственный"><?= htmlspecialchars($install['responsible']) ?></td>
                                        <td data-label="Дата установки"><?= date('d.m.Y', strtotime($install['install_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО. Все права защищены.</p>
    </footer>
</body>
</html>