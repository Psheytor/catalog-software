<?php
session_start();

// Загрузка данных
$software = json_decode(file_get_contents('data/software.json'), true)['software'];
$licenses = json_decode(file_get_contents('data/licenses.json'), true)['licenses'];

// Функция получения статуса лицензии
function getLicenseStatus($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;
    
    if ($today > $expiry) return 'истекла';
    if ($diff <= 30) return 'истекает';
    return 'активна';
}

// Добавляем статус к каждому ПО
foreach ($software as &$item) {
    $license = array_values(array_filter($licenses, fn($l) => $l['software_id'] == $item['id']))[0] ?? null;
    $item['license_status'] = $license ? getLicenseStatus($license['expiry_date']) : 'нет лицензии';
    $item['expiry_date'] = $license['expiry_date'] ?? null;
}

// Поиск
$search_query = trim($_GET['search'] ?? '');
$filtered = $software;

if ($search_query !== '') {
    $filtered = array_filter($filtered, function($item) use ($search_query) {
        return stripos($item['name'], $search_query) !== false ||
               stripos($item['manufacturer'], $search_query) !== false ||
               stripos($item['type'], $search_query) !== false;
    });
}

// Фильтрация
$type_filter = $_GET['type'] ?? '';
$manufacturer_filter = $_GET['manufacturer'] ?? '';
$status_filter = $_GET['status'] ?? '';

$filtered = array_filter($filtered, function($item) use ($type_filter, $manufacturer_filter, $status_filter) {
    if ($type_filter && $item['type'] != $type_filter) return false;
    if ($manufacturer_filter && $item['manufacturer'] != $manufacturer_filter) return false;
    if ($status_filter && $item['license_status'] != $status_filter) return false;
    return true;
});

// Сортировка
$sort_by = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'asc';

usort($filtered, function($a, $b) use ($sort_by, $sort_order) {
    if ($sort_by == 'expiry_date') {
        $cmp = strtotime($a[$sort_by] ?? '0') - strtotime($b[$sort_by] ?? '0');
    } else {
        $cmp = strcmp($a[$sort_by] ?? '', $b[$sort_by] ?? '');
    }
    return $sort_order == 'asc' ? $cmp : -$cmp;
});

// Пагинация - 6 продуктов на страницу
$per_page = 6;
$total = count($filtered);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $per_page;
$paged_items = array_slice($filtered, $offset, $per_page);
$total_pages = ceil($total / $per_page);

$query_params = array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY);
$query_string = http_build_query($query_params);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог ПО | Лицензионная чистота</title>
    <meta name="description" content="Каталог программного обеспечения с контролем лицензий">
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
        <h1>Каталог программного обеспечения</h1>

        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <select name="type">
                    <option value="">Все типы</option>
                    <option value="ОС" <?= $type_filter == 'ОС' ? 'selected' : '' ?>>ОС</option>
                    <option value="Офисное" <?= $type_filter == 'Офисное' ? 'selected' : '' ?>>Офисное</option>
                    <option value="Специальное" <?= $type_filter == 'Специальное' ? 'selected' : '' ?>>Специальное</option>
                    <option value="Антивирус" <?= $type_filter == 'Антивирус' ? 'selected' : '' ?>>Антивирус</option>
                    <option value="СУБД" <?= $type_filter == 'СУБД' ? 'selected' : '' ?>>СУБД</option>
                    <option value="Разработка" <?= $type_filter == 'Разработка' ? 'selected' : '' ?>>Разработка</option>
                </select>
                
                <select name="manufacturer">
                    <option value="">Все производители</option>
                    <option value="Microsoft" <?= $manufacturer_filter == 'Microsoft' ? 'selected' : '' ?>>Microsoft</option>
                    <option value="Adobe" <?= $manufacturer_filter == 'Adobe' ? 'selected' : '' ?>>Adobe</option>
                    <option value="Kaspersky" <?= $manufacturer_filter == 'Kaspersky' ? 'selected' : '' ?>>Kaspersky</option>
                    <option value="Oracle" <?= $manufacturer_filter == 'Oracle' ? 'selected' : '' ?>>Oracle</option>
                    <option value="JetBrains" <?= $manufacturer_filter == 'JetBrains' ? 'selected' : '' ?>>JetBrains</option>
                    <option value="Autodesk" <?= $manufacturer_filter == 'Autodesk' ? 'selected' : '' ?>>Autodesk</option>
                </select>
                
                <select name="status">
                    <option value="">Все статусы</option>
                    <option value="активна" <?= $status_filter == 'активна' ? 'selected' : '' ?>>Активна</option>
                    <option value="истекает" <?= $status_filter == 'истекает' ? 'selected' : '' ?>>Истекает</option>
                    <option value="истекла" <?= $status_filter == 'истекла' ? 'selected' : '' ?>>Истекла</option>
                </select>
                
                <select name="sort">
                    <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>По названию</option>
                    <option value="manufacturer" <?= $sort_by == 'manufacturer' ? 'selected' : '' ?>>По производителю</option>
                    <option value="expiry_date" <?= $sort_by == 'expiry_date' ? 'selected' : '' ?>>По дате лицензии</option>
                </select>
                
                <select name="order">
                    <option value="asc" <?= $sort_order == 'asc' ? 'selected' : '' ?>>Возрастание</option>
                    <option value="desc" <?= $sort_order == 'desc' ? 'selected' : '' ?>>Убывание</option>
                </select>
                
                <button type="submit">Применить</button>
                <a href="/catalog.php" class="btn-reset">Сбросить</a>
            </form>
            
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Поиск по названию, производителю..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Найти</button>
            </form>
        </div>

        <div class="software-list">
            <?php if(empty($paged_items)): ?>
                <p>Программное обеспечение не найдено.</p>
            <?php else: ?>
                <?php foreach($paged_items as $item): ?>
                    <div class="software-card">
                        <img src="<?= !empty($item['image']) ? $item['image'] : '/images/products/placeholder.png' ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>" 
                             onerror="this.src='/images/products/placeholder.png'">
                        <div class="card-content">
                            <h3><a href="/software/<?= $item['slug'] ?>.html"><?= htmlspecialchars($item['name']) ?></a></h3>
                            <p>Производитель: <?= htmlspecialchars($item['manufacturer']) ?></p>
                            <p>Версия: <?= htmlspecialchars($item['version']) ?></p>
                            <p>Тип: <?= htmlspecialchars($item['type']) ?></p>
                            <p class="status <?= $item['license_status'] ?>">
                                Статус лицензии: <?= $item['license_status'] ?>
                                <?php if($item['expiry_date']): ?>
                                    (до <?= date('d.m.Y', strtotime($item['expiry_date'])) ?>)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if($total_pages > 1): ?>
            <nav class="pagination">
                <?php if($current_page > 1): ?>
                    <a href="?page=<?= $current_page-1 ?>&<?= $query_string ?>" rel="prev">Назад</a>
                <?php endif; ?>
                <span class="page-current">Страница <?= $current_page ?> из <?= $total_pages ?></span>
                <?php if($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page+1 ?>&<?= $query_string ?>" rel="next">Вперёд</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО. Все права защищены.</p>
    </footer>
</body>
</html>