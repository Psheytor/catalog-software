<?php
session_start();

// Загрузка данных для популярного ПО
$software = json_decode(file_get_contents('data/software.json'), true)['software'];
$licenses = json_decode(file_get_contents('data/licenses.json'), true)['licenses'];
$installs = json_decode(file_get_contents('data/installs.json'), true)['installs'];

// Функция получения статуса лицензии
function getLicenseStatus($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    if ($today > $expiry) return 'истекла';
    if ($today->diff($expiry)->days <= 30) return 'истекает';
    return 'активна';
}

// Подсчет количества установок для каждого ПО
foreach ($software as &$item) {
    $install_count = count(array_filter($installs, fn($i) => $i['software_id'] == $item['id']));
    $item['install_count'] = $install_count;
    
    $license = array_values(array_filter($licenses, fn($l) => $l['software_id'] == $item['id']))[0] ?? null;
    $item['license_status'] = $license ? getLicenseStatus($license['expiry_date']) : 'нет лицензии';
}

// Получаем топ-4 популярных ПО по количеству установок
$popular_software = $software;
usort($popular_software, function($a, $b) {
    return $b['install_count'] - $a['install_count'];
});
$popular_software = array_slice($popular_software, 0, 4);

// Вопросы и ответы
$faq = [
    [
        'question' => 'Как добавить новое программное обеспечение в каталог?',
        'answer' => 'Для добавления нового ПО необходимо войти в систему под учетной записью администратора. Затем в админ-панели нажмите "Добавить ПО", заполните форму и сохраните изменения.'
    ],
    [
        'question' => 'Как отслеживать сроки действия лицензий?',
        'answer' => 'Все лицензии отображаются в админ-панели с указанием срока действия. Система автоматически подсвечивает истекающие (желтый) и истекшие (красный) лицензии. Также администратор получает визуальные уведомления.'
    ],
    [
        'question' => 'Что делать при нехватке лицензий?',
        'answer' => 'При превышении количества установок над доступными лицензиями, система отображает предупреждение в разделе "Отчеты" админ-панели. Рекомендуется приобрести дополнительные лицензии или оптимизировать использование ПО.'
    ],
    [
        'question' => 'Может ли сотрудник видеть лицензионные ключи?',
        'answer' => 'Нет, лицензионные ключи видны только администратору. Сотрудник видит только информацию о сроке действия и статусе лицензии.'
    ],
    [
        'question' => 'Как часто обновляется информация о лицензиях?',
        'answer' => 'Информация обновляется автоматически при каждом изменении данных администратором. Статус лицензий пересчитывается при каждом просмотре страницы.'
    ],
    [
        'question' => 'Можно ли экспортировать данные о лицензиях?',
        'answer' => 'Функция экспорта данных в формате CSV/XML будет добавлена в следующих версиях системы.'
    ]
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог ПО | Контроль лицензионной чистоты</title>
    <meta name="description" content="Система учета программного обеспечения, контроля лицензий и управления установками">
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
        <!-- Блок 1: с описанием -->
        <section class="hero">
            <div class="hero-content">
                <h1>Каталог программного обеспечения</h1>
                <p class="hero-description">
                    Единая система учета ПО, контроля лицензионной чистоты и управления установками.
                    Отслеживайте сроки действия лицензий, контролируйте количество установок и 
                    будьте уверены в легальности используемого программного обеспечения.
                </p>
                <div class="hero-buttons">
                    <a href="/catalog.php" class="btn btn-primary">Перейти в каталог</a>
                    <?php if(!isset($_SESSION['user'])): ?>
                        <a href="/login.php" class="btn btn-secondary">Войти в систему</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number"><?= count($software) ?></span>
                        <span class="stat-label">программ</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?= count($licenses) ?></span>
                        <span class="stat-label">лицензий</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?= count($installs) ?></span>
                        <span class="stat-label">установок</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Блок 2: Популярное ПО -->
        <section class="popular-section">
            <h2>Самые популярные программные продукты</h2>
            <p class="section-subtitle">Наиболее часто устанавливаемое ПО в вашей организации</p>
            
            <div class="popular-grid">
                <?php foreach($popular_software as $item): ?>
                    <div class="popular-card">
                        <div class="popular-card-inner">
                            <img src="<?= !empty($item['image']) ? $item['image'] : '/images/products/placeholder.png' ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 onerror="this.src='/images/products/placeholder.png'">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="manufacturer"><?= htmlspecialchars($item['manufacturer']) ?></p>
                            <div class="popular-stats">
                                <span class="install-count">📊 <?= $item['install_count'] ?> установок</span>
                                <span class="status <?= $item['license_status'] ?>"><?= $item['license_status'] ?></span>
                            </div>
                            <a href="/software/<?= $item['slug'] ?>.html" class="btn btn-sm">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center">
                <a href="/catalog.php" class="btn btn-outline">Смотреть весь каталог</a>
            </div>
        </section>

        <!-- Блок 3: Вопросы-ответы -->
        <section class="faq-section">
            <h2>Часто задаваемые вопросы</h2>
            <p class="section-subtitle">Ответы на самые популярные вопросы о системе</p>
            
            <div class="faq-grid">
                <?php foreach($faq as $index => $item): ?>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span><?= htmlspecialchars($item['question']) ?></span>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p><?= htmlspecialchars($item['answer']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Каталог ПО. Все права защищены.</p>
        <p><a href="/catalog.php">Каталог ПО</a> | <a href="#">Политика конфиденциальности</a></p>
    </footer>

    <script>
    function toggleFaq(element) {
        const faqItem = element.parentElement;
        const answer = faqItem.querySelector('.faq-answer');
        const icon = element.querySelector('.faq-icon');
        
        if (answer.style.display === 'block') {
            answer.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        } else {
            answer.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const firstAnswer = document.querySelector('.faq-answer');
        if (firstAnswer) {
            firstAnswer.style.display = 'block';
            const firstIcon = document.querySelector('.faq-question .faq-icon');
            if (firstIcon) firstIcon.style.transform = 'rotate(180deg)';
        }
    });
    </script>
</body>
</html>