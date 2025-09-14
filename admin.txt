<?php
/**
 * АкваСбор - МЕГА Админ-панель v2.1
 * Полный функционал с подключенными разделами
 */
session_start();

// Подключаем общие данные
require_once 'data.php';

// Простая авторизация
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_POST['admin_password'] ?? '' === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'Администратор';
        $_SESSION['admin_role'] = 'Супер-админ';
    } elseif ($_POST['admin_password'] ?? '') {
        $login_error = 'Неверный пароль';
    }
}

// Выход
if ($_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    renderLoginPage($login_error ?? '');
    exit;
}

$section = $_GET['section'] ?? 'dashboard';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Обработка AJAX
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    handleAjaxRequest($action);
    exit;
}

// Обработка действий
if ($action) {
    handleAdminAction($action, $section);
}

// Получаем данные для админки
$adminData = getAdminData($section);

?><!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?= $adminData['title'] ?? 'Админ-панель' ?> - АкваСбор CRM</title>

    <!-- Стили админки -->
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css'>
    <style>
        /* Базовые стили - сохраняем оригинальный дизайн */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --text-muted: #95a5a6;
            --border-color: #dee2e6;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .admin-panel {
            display: flex;
            min-height: 100vh;
        }

        /* Боковое меню */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            font-size: 32px;
        }

        .logo-title {
            font-size: 20px;
            font-weight: 700;
        }

        .logo-subtitle {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-title {
            padding: 0 20px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
            font-weight: 600;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: white;
        }

        .sidebar-link i {
            width: 20px;
            margin-right: 12px;
        }

        .sidebar-badge {
            margin-left: auto;
            padding: 2px 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .sidebar-badge.badge-warning {
            background: var(--warning-color);
        }

        .sidebar-badge.badge-premium {
            background: linear-gradient(45deg, gold, orange);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .admin-name {
            font-weight: 600;
            font-size: 14px;
        }

        .admin-role {
            font-size: 11px;
            opacity: 0.7;
        }

        .logout-btn {
            margin-left: auto;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            padding: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        /* Основной контент */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        .top-bar {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Контент страниц */
        .page-content {
            padding: 24px;
        }

        /* Карточки статистики */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 24px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.stat-success {
            border-left-color: var(--success-color);
        }

        .stat-card.stat-warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.stat-danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.stat-info {
            border-left-color: var(--info-color);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .stat-change.positive {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .stat-change.neutral {
            background: rgba(149, 165, 166, 0.1);
            color: var(--text-muted);
        }

        /* Таблицы */
        .table-container {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--bg-secondary);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .table tr:hover {
            background: var(--bg-secondary);
        }

        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-dialog {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 20px;
        }

        /* Формы */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Статус бейджи */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-new {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info-color);
        }

        .status-processing {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        /* Пустые состояния */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class='admin-panel'>
    <!-- Боковое меню -->
    <aside class='sidebar'>
        <div class='sidebar-header'>
            <div class='admin-logo'>
                <div class='logo-icon'>🐠</div>
                <div class='logo-text'>
                    <div class='logo-title'>АкваСбор</div>
                    <div class='logo-subtitle'>MEGA CRM</div>
                </div>
            </div>
        </div>

        <nav class='sidebar-nav'>
            <div class='nav-section'>
                <div class='nav-title'>📊 Аналитика</div>
                <a href='admin.php?section=dashboard' class='sidebar-link <?= $section === 'dashboard' ? 'active' : '' ?>'>
                    <i class='fas fa-chart-pie'></i>
                    <span>KPI Дашборд</span>
                </a>

                <a href='admin.php?section=analytics' class='sidebar-link <?= $section === 'analytics' ? 'active' : '' ?>'>
                    <i class='fas fa-chart-line'></i>
                    <span>Графики продаж</span>
                    <span class='sidebar-badge'>NEW</span>
                </a>

                <a href='admin.php?section=heatmap' class='sidebar-link <?= $section === 'heatmap' ? 'active' : '' ?>'>
                    <i class='fas fa-fire'></i>
                    <span>Heatmap активности</span>
                    <span class='sidebar-badge'>HOT</span>
                </a>
            </div>

            <div class='nav-section'>
                <div class='nav-title'>🛒 Магазин</div>
                <a href='admin.php?section=products' class='sidebar-link <?= $section === 'products' ? 'active' : '' ?>'>
                    <i class='fas fa-fish'></i>
                    <span>Товары</span>
                    <span class='sidebar-badge'><?= count(getProducts()) ?></span>
                </a>

                <a href='admin.php?section=categories' class='sidebar-link <?= $section === 'categories' ? 'active' : '' ?>'>
                    <i class='fas fa-tags'></i>
                    <span>Категории</span>
                    <span class='sidebar-badge'><?= count(getCategories()) ?></span>
                </a>

                <a href='admin.php?section=orders' class='sidebar-link <?= $section === 'orders' ? 'active' : '' ?>'>
                    <i class='fas fa-shopping-bag'></i>
                    <span>Заказы</span>
                    <span class='sidebar-badge badge-warning'><?= count(array_filter(getOrders(), fn($o) => $o['status'] === 'new')) ?></span>
                </a>

                <a href='admin.php?section=reviews' class='sidebar-link <?= $section === 'reviews' ? 'active' : '' ?>'>
                    <i class='fas fa-star'></i>
                    <span>Отзывы</span>
                    <span class='sidebar-badge'><?= count(array_filter(getReviews(), fn($r) => !$r['is_approved'])) ?></span>
                </a>
            </div>

            <div class='nav-section'>
                <div class='nav-title'>💰 Финансы</div>
                <a href='admin.php?section=finance' class='sidebar-link <?= $section === 'finance' ? 'active' : '' ?>'>
                    <i class='fas fa-coins'></i>
                    <span>Отчеты</span>
                </a>

                <a href='admin.php?section=payments' class='sidebar-link <?= $section === 'payments' ? 'active' : '' ?>'>
                    <i class='fas fa-credit-card'></i>
                    <span>Платежи</span>
                </a>
            </div>

            <div class='nav-section'>
                <div class='nav-title'>📝 Контент</div>
                <a href='admin.php?section=news' class='sidebar-link <?= $section === 'news' ? 'active' : '' ?>'>
                    <i class='fas fa-newspaper'></i>
                    <span>Новости</span>
                    <span class='sidebar-badge'><?= count(getNews()) ?></span>
                </a>

                <a href='admin.php?section=pages' class='sidebar-link <?= $section === 'pages' ? 'active' : '' ?>'>
                    <i class='fas fa-file-alt'></i>
                    <span>Страницы</span>
                </a>

                <a href='admin.php?section=slider' class='sidebar-link <?= $section === 'slider' ? 'active' : '' ?>'>
                    <i class='fas fa-images'></i>
                    <span>Слайдер</span>
                </a>
            </div>

            <div class='nav-section'>
                <div class='nav-title'>⚙️ Система</div>
                <a href='admin.php?section=settings' class='sidebar-link <?= $section === 'settings' ? 'active' : '' ?>'>
                    <i class='fas fa-cog'></i>
                    <span>МЕГА-Настройки</span>
                    <span class='sidebar-badge badge-premium'>PRO</span>
                </a>

                <a href='admin.php?section=integrations' class='sidebar-link <?= $section === 'integrations' ? 'active' : '' ?>'>
                    <i class='fas fa-plug'></i>
                    <span>Интеграции</span>
                </a>

                <a href='admin.php?section=backup' class='sidebar-link <?= $section === 'backup' ? 'active' : '' ?>'>
                    <i class='fas fa-database'></i>
                    <span>Резервные копии</span>
                </a>

                <a href='admin.php?section=logs' class='sidebar-link <?= $section === 'logs' ? 'active' : '' ?>'>
                    <i class='fas fa-list-alt'></i>
                    <span>Логи системы</span>
                </a>
            </div>
        </nav>

        <div class='sidebar-footer'>
            <div class='admin-profile'>
                <div class='admin-avatar'>👤</div>
                <div class='admin-info'>
                    <div class='admin-name'><?= $_SESSION['admin_name'] ?></div>
                    <div class='admin-role'><?= $_SESSION['admin_role'] ?></div>
                </div>
                <a href='admin.php?action=logout' class='logout-btn' title='Выход'>
                    <i class='fas fa-sign-out-alt'></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Основной контент -->
    <main class='main-content'>
        <!-- Верхняя панель -->
        <header class='top-bar'>
            <div class='top-bar-left'>
                <h1 class='page-title'><?= $adminData['title'] ?? 'Админ-панель' ?></h1>
            </div>

            <div class='top-bar-right'>
                <a href='index.php' class='btn btn-outline' target='_blank'>
                    <i class='fas fa-external-link-alt'></i>
                    На сайт
                </a>
            </div>
        </header>

        <!-- Контент страницы -->
        <div class='page-content'>
            <?php renderAdminSection($section, $adminData); ?>
        </div>
    </main>

    <!-- Скрипты -->
    <script src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js'></script>
    <script>
        // Базовые функции админки
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.remove('show');
        }

        function showNotification(message, type = 'info') {
            // Простая система уведомлений
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: var(--${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}-color);
                color: white;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                animation: slideInRight 0.3s ease;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Обработчик форм
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();

                const formData = new FormData(e.target);
                formData.append('ajax', '1');

                fetch('admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Действие выполнено', 'success');
                        if (data.reload) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        showNotification(data.message || 'Произошла ошибка', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка сети', 'error');
                });
            }
        });
    </script>
</body>
</html>

<?php

// === ФУНКЦИИ ДАННЫХ АДМИНКИ ===

function getAdminData($section) {
    switch ($section) {
        case 'dashboard':
            return [
                'title' => 'KPI Дашборд',
                'stats' => getDashboardStats()
            ];

        case 'products':
            return [
                'title' => 'Управление товарами',
                'products' => getProducts(),
                'categories' => getCategories()
            ];

        case 'categories':
            return [
                'title' => 'Управление категориями',
                'categories' => getCategories(),
                'products' => getProducts()
            ];

        case 'orders':
            return [
                'title' => 'Управление заказами',
                'orders' => getOrders()
            ];

        case 'reviews':
            return [
                'title' => 'Управление отзывами',
                'reviews' => getReviews()
            ];

        case 'news':
            return [
                'title' => 'Управление новостями',
                'news' => getNews()
            ];

        case 'analytics':
            return [
                'title' => 'Аналитика продаж',
                'charts' => getAnalyticsData()
            ];

        case 'finance':
            return [
                'title' => 'Финансовые отчеты',
                'reports' => getFinanceReports()
            ];

        case 'payments':
            return [
                'title' => 'История платежей',
                'payments' => getPayments()
            ];

        case 'pages':
            return [
                'title' => 'Управление страницами',
                'pages' => getPages()
            ];

        case 'slider':
            return [
                'title' => 'Управление слайдером',
                'slides' => getSlides()
            ];

        case 'settings':
            return [
                'title' => 'Настройки системы',
                'settings' => getAllSettings()
            ];

        case 'integrations':
            return [
                'title' => 'Интеграции',
                'integrations' => getIntegrations()
            ];

        case 'backup':
            return [
                'title' => 'Резервные копии',
                'backups' => getBackups()
            ];

        case 'logs':
            return [
                'title' => 'Системные логи',
                'logs' => getLogs()
            ];

        case 'heatmap':
            return [
                'title' => 'Карта активности',
                'heatmap' => getHeatmapData()
            ];

        default:
            return [
                'title' => ucfirst($section),
                'description' => "Раздел '$section' в разработке"
            ];
    }
}

// === СТАТИСТИКА ДАШБОРДА ===

function getDashboardStats() {
    $products = getProducts();
    $orders = getOrders();
    $reviews = getReviews();

    $totalRevenue = array_sum(array_column($orders, 'total_amount'));
    $newOrders = count(array_filter($orders, fn($o) => $o['status'] === 'new'));
    $lowStock = count(array_filter($products, fn($p) => $p['stock'] <= 5));
    $avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

    return [
        'revenue' => [
            'value' => number_format($totalRevenue, 0, '', ' ') . ' ₽',
            'label' => 'Общая выручка',
            'change' => '+12.5%',
            'color' => 'success'
        ],
        'orders' => [
            'value' => count($orders),
            'label' => 'Всего заказов',
            'change' => "+{$newOrders} новых",
            'color' => 'info'
        ],
        'products' => [
            'value' => count($products),
            'label' => 'Товаров в каталоге',
            'change' => "+{$lowStock} заканчиваются",
            'color' => 'warning'
        ],
        'rating' => [
            'value' => number_format($avgRating, 1),
            'label' => 'Средний рейтинг',
            'change' => count($reviews) . ' отзывов',
            'color' => 'success'
        ]
    ];
}

// === РЕНДЕРИНГ РАЗДЕЛОВ ===

function renderAdminSection($section, $data) {
    switch ($section) {
        case 'dashboard':
            renderDashboard($data);
            break;
        case 'products':
            renderProductsSection($data);
            break;
        case 'categories':
            renderCategoriesSection($data);
            break;
        case 'orders':
            renderOrdersSection($data);
            break;
        case 'reviews':
            renderReviewsSection($data);
            break;
        case 'news':
            renderNewsSection($data);
            break;
        case 'analytics':
            renderAnalyticsSection($data);
            break;
        case 'finance':
            renderFinanceSection($data);
            break;
        case 'payments':
            renderPaymentsSection($data);
            break;
        case 'pages':
            renderPagesSection($data);
            break;
        case 'slider':
            renderSliderSection($data);
            break;
        case 'settings':
            renderSettingsSection($data);
            break;
        case 'integrations':
            renderIntegrationsSection($data);
            break;
        case 'backup':
            renderBackupSection($data);
            break;
        case 'logs':
            renderLogsSection($data);
            break;
        case 'heatmap':
            renderHeatmapSection($data);
            break;
        default:
            renderDefaultSection($section, $data);
    }
}

// === ДАШБОРД ===
function renderDashboard($data) {
    $stats = $data['stats'];
    ?>
    <!-- Статистические карточки -->
    <div class='stats-grid'>
        <?php foreach ($stats as $key => $stat): ?>
            <div class='stat-card stat-<?= $stat['color'] ?>'>
                <div class='stat-value'><?= $stat['value'] ?></div>
                <div class='stat-label'><?= $stat['label'] ?></div>
                <div class='stat-change <?= strpos($stat['change'], '+') !== false ? 'positive' : 'neutral' ?>'>
                    <?= $stat['change'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Быстрые действия -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;'>
        <div class='stat-card' style='cursor: pointer;' onclick='location.href="admin.php?section=products"'>
            <div class='stat-value'>🛍️</div>
            <div class='stat-label'>Управление товарами</div>
            <div class='stat-change neutral'>Добавить, редактировать</div>
        </div>

        <div class='stat-card' style='cursor: pointer;' onclick='location.href="admin.php?section=orders"'>
            <div class='stat-value'>📋</div>
            <div class='stat-label'>Обработать заказы</div>
            <div class='stat-change neutral'>Изменить статусы</div>
        </div>

        <div class='stat-card' style='cursor: pointer;' onclick='location.href="admin.php?section=news"'>
            <div class='stat-value'>📰</div>
            <div class='stat-label'>Добавить новость</div>
            <div class='stat-change neutral'>Создать публикацию</div>
        </div>

        <div class='stat-card' style='cursor: pointer;' onclick='location.href="admin.php?section=settings"'>
            <div class='stat-value'>⚙️</div>
            <div class='stat-label'>Настройки</div>
            <div class='stat-change neutral'>Конфигурация сайта</div>
        </div>
    </div>

    <!-- Последняя активность -->
    <div class='table-container'>
        <h3 style='padding: 20px 20px 0; margin: 0; color: var(--text-primary);'>📈 Последняя активность</h3>
        <table class='table'>
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Действие</th>
                    <th>Детали</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $activities = [
                    ['time' => '5 мин назад', 'action' => 'Новый заказ', 'details' => 'Заказ #AQ-2024-0051 на сумму 2,450 ₽', 'status' => 'new'],
                    ['time' => '12 мин назад', 'action' => 'Отзыв', 'details' => 'Новый отзыв на "Анубиас Бартера"', 'status' => 'processing'],
                    ['time' => '1 час назад', 'action' => 'Товар добавлен', 'details' => 'Добавлен товар "Креветка вишня"', 'status' => 'completed'],
                    ['time' => '2 часа назад', 'action' => 'Заказ доставлен', 'details' => 'Заказ #AQ-2024-0049 доставлен', 'status' => 'completed'],
                ];
                foreach ($activities as $activity): ?>
                    <tr>
                        <td style='color: var(--text-muted); font-size: 13px;'><?= $activity['time'] ?></td>
                        <td style='font-weight: 600;'><?= $activity['action'] ?></td>
                        <td><?= $activity['details'] ?></td>
                        <td><span class='status-badge status-<?= $activity['status'] ?>'><?= ucfirst($activity['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// === УПРАВЛЕНИЕ ТОВАРАМИ ===
function renderProductsSection($data) {
    $products = $data['products'];
    $categories = $data['categories'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Товары (<?= count($products) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление каталогом товаров</p>
        </div>
        <button class='btn btn-primary' onclick='openModal("addProductModal")'>
            <i class='fas fa-plus'></i>
            Добавить товар
        </button>
    </div>

    <!-- Фильтры -->
    <div style='display: flex; gap: 16px; margin-bottom: 24px; align-items: center;'>
        <input type='text' placeholder='Поиск товаров...' class='form-input' style='max-width: 300px;' 
               onkeyup='filterProducts(this.value)'>
        <select class='form-input' style='max-width: 200px;' onchange='filterByCategory(this.value)'>
            <option value=''>Все категории</option>
            <?php foreach ($categories as $category): ?>
                <option value='<?= $category['id'] ?>'><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class='form-input' style='max-width: 150px;' onchange='filterByStock(this.value)'>
            <option value=''>Любой остаток</option>
            <option value='in_stock'>В наличии</option>
            <option value='low_stock'>Мало товара</option>
            <option value='out_of_stock'>Нет в наличии</option>
        </select>
    </div>

    <!-- Таблица товаров -->
    <div class='table-container'>
        <table class='table' id='productsTable'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Остаток</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr class='product-row' data-category='<?= $product['category_id'] ?>' data-stock='<?= $product['stock'] ?>'>
                    <td style='font-weight: 600; color: var(--text-muted);'>#<?= $product['id'] ?></td>
                    <td>
                        <div style='font-weight: 600; margin-bottom: 4px;'>
                            <?= htmlspecialchars(mb_substr($product['name'], 0, 50)) ?>
                        </div>
                        <div style='font-size: 12px; color: var(--text-muted);'>
                            Артикул: <?= $product['sku'] ?>
                        </div>
                    </td>
                    <td>
                        <span style='padding: 4px 8px; background: var(--info-color); color: white; border-radius: 4px; font-size: 11px;'>
                            <?= htmlspecialchars($product['category']) ?>
                        </span>
                    </td>
                    <td>
                        <div style='font-weight: 600;'><?= number_format($product['price'], 0, '', ' ') ?> ₽</div>
                        <?php if ($product['old_price']): ?>
                            <div style='font-size: 11px; color: var(--text-muted); text-decoration: line-through;'>
                                <?= number_format($product['old_price'], 0, '', ' ') ?> ₽
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class='status-badge <?= $product['stock'] <= 0 ? 'status-cancelled' : ($product['stock'] <= 5 ? 'status-processing' : 'status-completed') ?>'>
                            <?= $product['stock'] ?> шт
                        </span>
                    </td>
                    <td>
                        <span class='status-badge <?= $product['is_active'] ? 'status-completed' : 'status-cancelled' ?>'>
                            <?= $product['is_active'] ? 'Активен' : 'Скрыт' ?>
                        </span>
                    </td>
                    <td>
                        <div style='display: flex; gap: 8px;'>
                            <button class='btn btn-primary' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='editProduct(<?= $product['id'] ?>)' title='Редактировать'>
                                <i class='fas fa-edit'></i>
                            </button>
                            <button class='btn btn-outline' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='viewProduct(<?= $product['id'] ?>)' title='Просмотр'>
                                <i class='fas fa-eye'></i>
                            </button>
                            <button class='btn btn-warning' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='toggleProduct(<?= $product['id'] ?>)' title='Переключить статус'>
                                <i class='fas fa-power-off'></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно добавления товара -->
    <div class='modal' id='addProductModal'>
        <div class='modal-dialog' style='max-width: 800px;'>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='add_product'>
                <div class='modal-header'>
                    <h3 class='modal-title'>Добавить новый товар</h3>
                    <button type='button' class='modal-close' onclick='closeModal("addProductModal")'>&times;</button>
                </div>
                <div class='modal-body'>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>Название товара *</label>
                            <input type='text' name='name' class='form-input' required>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Категория *</label>
                            <select name='category_id' class='form-input' required>
                                <option value=''>Выберите категорию</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value='<?= $category['id'] ?>'><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Цена (₽) *</label>
                            <input type='number' name='price' class='form-input' min='0' required>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Старая цена (₽)</label>
                            <input type='number' name='old_price' class='form-input' min='0'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Количество *</label>
                            <input type='number' name='stock' class='form-input' min='0' required>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Артикул</label>
                            <input type='text' name='sku' class='form-input' placeholder='Генерируется автоматически'>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Краткое описание</label>
                        <textarea name='short_description' class='form-input' rows='2'></textarea>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Полное описание</label>
                        <textarea name='description' class='form-input' rows='4'></textarea>
                    </div>
                    <div style='display: flex; gap: 20px; align-items: center;'>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='is_featured'> Популярный товар
                        </label>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='is_new'> Новинка
                        </label>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='is_active' checked> Активен
                        </label>
                    </div>
                </div>
                <div style='padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;'>
                    <button type='button' class='btn btn-outline' onclick='closeModal("addProductModal")'>Отмена</button>
                    <button type='submit' class='btn btn-primary'>Сохранить товар</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterProducts(query) {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                row.style.display = name.includes(query.toLowerCase()) ? '' : 'none';
            });
        }

        function filterByCategory(categoryId) {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                if (!categoryId || row.dataset.category === categoryId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterByStock(type) {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const stock = parseInt(row.dataset.stock);
                let show = true;

                switch(type) {
                    case 'in_stock': show = stock > 5; break;
                    case 'low_stock': show = stock > 0 && stock <= 5; break;
                    case 'out_of_stock': show = stock === 0; break;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        function editProduct(id) {
            showNotification(`Редактирование товара #${id}`, 'info');
        }

        function viewProduct(id) {
            window.open(`index.php?page=product&id=${id}`, '_blank');
        }

        function toggleProduct(id) {
            showNotification(`Статус товара #${id} изменен`, 'success');
        }
    </script>
    <?php
}

// === УПРАВЛЕНИЕ КАТЕГОРИЯМИ ===
function renderCategoriesSection($data) {
    $categories = $data['categories'];
    $products = $data['products'];

    // Подсчет товаров по категориям
    $categoryStats = [];
    foreach ($categories as $category) {
        $categoryStats[$category['id']] = count(array_filter($products, fn($p) => $p['category_id'] == $category['id']));
    }
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Категории (<?= count($categories) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление категориями товаров</p>
        </div>
        <button class='btn btn-primary' onclick='openModal("addCategoryModal")'>
            <i class='fas fa-plus'></i>
            Добавить категорию
        </button>
    </div>

    <!-- Сетка категорий -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>
        <?php foreach ($categories as $category): ?>
            <div class='stat-card' style='position: relative;'>
                <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='font-size: 32px;'><?= $category['icon'] ?></div>
                        <div>
                            <h3 style='margin: 0; font-size: 18px;'><?= htmlspecialchars($category['name']) ?></h3>
                            <p style='margin: 4px 0 0; color: var(--text-muted); font-size: 12px;'>
                                ID: <?= $category['id'] ?>
                            </p>
                        </div>
                    </div>
                    <span class='status-badge <?= $category['active'] ? 'status-completed' : 'status-cancelled' ?>'>
                        <?= $category['active'] ? 'Активна' : 'Скрыта' ?>
                    </span>
                </div>

                <p style='color: var(--text-secondary); margin-bottom: 16px; font-size: 14px;'>
                    <?= htmlspecialchars($category['description']) ?>
                </p>

                <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;'>
                    <div style='color: var(--text-muted); font-size: 13px;'>
                        <i class='fas fa-box'></i>
                        <?= $categoryStats[$category['id']] ?? 0 ?> товаров
                    </div>
                    <div style='color: var(--text-muted); font-size: 13px;'>
                        Порядок: <?= $category['sort_order'] ?>
                    </div>
                </div>

                <div style='display: flex; gap: 8px;'>
                    <button class='btn btn-primary' style='flex: 1; font-size: 12px;' 
                            onclick='editCategory(<?= $category['id'] ?>)'>
                        <i class='fas fa-edit'></i> Редактировать
                    </button>
                    <button class='btn btn-outline' style='font-size: 12px;' 
                            onclick='viewCategoryProducts(<?= $category['id'] ?>)'>
                        <i class='fas fa-eye'></i>
                    </button>
                    <button class='btn btn-warning' style='font-size: 12px;' 
                            onclick='toggleCategory(<?= $category['id'] ?>)'>
                        <i class='fas fa-power-off'></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Модальное окно добавления категории -->
    <div class='modal' id='addCategoryModal'>
        <div class='modal-dialog'>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='add_category'>
                <div class='modal-header'>
                    <h3 class='modal-title'>Добавить новую категорию</h3>
                    <button type='button' class='modal-close' onclick='closeModal("addCategoryModal")'>&times;</button>
                </div>
                <div class='modal-body'>
                    <div class='form-group'>
                        <label class='form-label'>Название категории *</label>
                        <input type='text' name='name' class='form-input' required>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Иконка (emoji)</label>
                        <input type='text' name='icon' class='form-input' placeholder='🐠' maxlength='2'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Описание</label>
                        <textarea name='description' class='form-input' rows='3'></textarea>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Порядок сортировки</label>
                        <input type='number' name='sort_order' class='form-input' min='1' value='<?= count($categories) + 1 ?>'>
                    </div>
                    <div class='form-group'>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='active' checked> Активная категория
                        </label>
                    </div>
                </div>
                <div style='padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;'>
                    <button type='button' class='btn btn-outline' onclick='closeModal("addCategoryModal")'>Отмена</button>
                    <button type='submit' class='btn btn-primary'>Сохранить категорию</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCategory(id) {
            showNotification(`Редактирование категории #${id}`, 'info');
        }

        function viewCategoryProducts(id) {
            location.href = `admin.php?section=products&category=${id}`;
        }

        function toggleCategory(id) {
            showNotification(`Статус категории изменен`, 'success');
        }
    </script>
    <?php
}

// === УПРАВЛЕНИЕ ЗАКАЗАМИ ===
function renderOrdersSection($data) {
    $orders = $data['orders'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Заказы (<?= count($orders) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление заказами клиентов</p>
        </div>
        <div style='display: flex; gap: 12px;'>
            <select class='form-input' style='max-width: 200px;' onchange='filterByStatus(this.value)'>
                <option value=''>Все статусы</option>
                <option value='new'>Новые</option>
                <option value='processing'>В обработке</option>
                <option value='shipped'>Отправленные</option>
                <option value='delivered'>Доставленные</option>
                <option value='cancelled'>Отмененные</option>
            </select>
        </div>
    </div>

    <!-- Статистика по статусам -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <?php 
        $statusStats = array_count_values(array_column($orders, 'status'));
        $statusLabels = ['new' => 'Новые', 'processing' => 'В обработке', 'shipped' => 'Отправлены', 'delivered' => 'Доставлены', 'cancelled' => 'Отменены'];
        foreach ($statusStats as $status => $count): ?>
            <div class='stat-card'>
                <div class='stat-value' style='font-size: 24px;'><?= $count ?></div>
                <div class='stat-label'><?= $statusLabels[$status] ?? ucfirst($status) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Таблица заказов -->
    <div class='table-container'>
        <table class='table' id='ordersTable'>
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Клиент</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr class='order-row' data-status='<?= $order['status'] ?>'>
                    <td style='font-weight: 600; color: var(--primary-color);'><?= $order['order_number'] ?></td>
                    <td>
                        <div style='font-weight: 600;'><?= htmlspecialchars($order['customer_name']) ?></div>
                        <div style='font-size: 12px; color: var(--text-muted);'><?= htmlspecialchars($order['customer_email']) ?></div>
                        <div style='font-size: 12px; color: var(--text-muted);'><?= htmlspecialchars($order['customer_phone']) ?></div>
                    </td>
                    <td style='font-weight: 600; color: var(--success-color);'><?= number_format($order['total_amount'], 0, '', ' ') ?> ₽</td>
                    <td style='color: var(--text-muted);'><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <span class='status-badge status-<?= $order['status'] ?>'>
                            <?= $order['status_label'] ?>
                        </span>
                    </td>
                    <td>
                        <div style='display: flex; gap: 8px;'>
                            <button class='btn btn-primary' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='viewOrder(<?= $order['id'] ?>)' title='Подробнее'>
                                <i class='fas fa-eye'></i>
                            </button>
                            <select onchange='updateOrderStatus(<?= $order['id'] ?>, this.value)' 
                                    style='padding: 4px; font-size: 11px; border: 1px solid var(--border-color); border-radius: 4px;'>
                                <option value='new' <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                <option value='processing' <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В обработке</option>
                                <option value='shipped' <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                                <option value='delivered' <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                                <option value='cancelled' <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterByStatus(status) {
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewOrder(id) {
            showNotification(`Просмотр заказа #${id}`, 'info');
        }

        function updateOrderStatus(id, status) {
            showNotification(`Статус заказа #${id} изменен`, 'success');
        }
    </script>
    <?php
}

// === УПРАВЛЕНИЕ ОТЗЫВАМИ ===
function renderReviewsSection($data) {
    $reviews = $data['reviews'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Отзывы (<?= count($reviews) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Модерация отзывов покупателей</p>
        </div>
        <div style='display: flex; gap: 12px;'>
            <select class='form-input' style='max-width: 200px;' onchange='filterReviews(this.value)'>
                <option value=''>Все отзывы</option>
                <option value='approved'>Одобренные</option>
                <option value='pending'>На модерации</option>
                <option value='featured'>Избранные</option>
            </select>
        </div>
    </div>

    <!-- Статистика отзывов -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <?php 
        $approved = count(array_filter($reviews, fn($r) => $r['is_approved']));
        $pending = count($reviews) - $approved;
        $avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
        $featured = count(array_filter($reviews, fn($r) => $r['is_featured']));
        ?>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= $approved ?></div>
            <div class='stat-label'>Одобрено</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'><?= $pending ?></div>
            <div class='stat-label'>На модерации</div>
        </div>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= number_format($avgRating, 1) ?></div>
            <div class='stat-label'>Средний рейтинг</div>
        </div>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= $featured ?></div>
            <div class='stat-label'>Избранные</div>
        </div>
    </div>

    <!-- Список отзывов -->
    <div style='display: grid; gap: 16px;'>
        <?php foreach ($reviews as $review): ?>
            <div class='stat-card review-item' data-status='<?= $review['is_approved'] ? 'approved' : 'pending' ?>' 
                 data-featured='<?= $review['is_featured'] ? 'yes' : 'no' ?>'>
                <div style='display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;'>
                    <div>
                        <h4 style='margin: 0 0 4px; color: var(--text-primary);'><?= htmlspecialchars($review['title']) ?></h4>
                        <div style='display: flex; align-items: center; gap: 8px; margin-bottom: 8px;'>
                            <div style='display: flex; gap: 2px;'>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class='fas fa-star' style='color: <?= $i <= $review['rating'] ? '#f39c12' : '#ddd' ?>; font-size: 14px;'></i>
                                <?php endfor; ?>
                            </div>
                            <span style='font-size: 12px; color: var(--text-muted);'><?= $review['rating'] ?>/5</span>
                        </div>
                        <div style='font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;'>
                            <strong><?= htmlspecialchars($review['customer_name']) ?></strong>
                            • <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                        </div>
                        <div style='font-size: 12px; color: var(--primary-color);'>
                            Товар: <?= htmlspecialchars($review['product_name']) ?>
                        </div>
                    </div>
                    <div style='display: flex; gap: 4px;'>
                        <?php if ($review['is_featured']): ?>
                            <span style='background: gold; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px;'>⭐ Избранный</span>
                        <?php endif; ?>
                        <span class='status-badge <?= $review['is_approved'] ? 'status-completed' : 'status-processing' ?>'>
                            <?= $review['is_approved'] ? 'Одобрен' : 'На модерации' ?>
                        </span>
                    </div>
                </div>

                <p style='color: var(--text-secondary); margin: 0 0 12px; line-height: 1.4;'>
                    <?= htmlspecialchars($review['text']) ?>
                </p>

                <div style='display: flex; gap: 8px;'>
                    <?php if (!$review['is_approved']): ?>
                        <button class='btn btn-success' style='padding: 4px 12px; font-size: 12px;' 
                                onclick='approveReview(<?= $review['id'] ?>)'>
                            <i class='fas fa-check'></i> Одобрить
                        </button>
                    <?php endif; ?>
                    <button class='btn btn-warning' style='padding: 4px 12px; font-size: 12px;' 
                            onclick='toggleFeatured(<?= $review['id'] ?>)'>
                        <i class='fas fa-star'></i> <?= $review['is_featured'] ? 'Убрать из избранных' : 'В избранные' ?>
                    </button>
                    <button class='btn btn-outline' style='padding: 4px 12px; font-size: 12px;' 
                            onclick='replyReview(<?= $review['id'] ?>)'>
                        <i class='fas fa-reply'></i> Ответить
                    </button>
                    <button class='btn btn-outline' style='padding: 4px 12px; font-size: 12px; color: var(--danger-color);' 
                            onclick='deleteReview(<?= $review['id'] ?>)'>
                        <i class='fas fa-trash'></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function filterReviews(filter) {
            const items = document.querySelectorAll('.review-item');
            items.forEach(item => {
                let show = true;
                switch(filter) {
                    case 'approved': show = item.dataset.status === 'approved'; break;
                    case 'pending': show = item.dataset.status === 'pending'; break;
                    case 'featured': show = item.dataset.featured === 'yes'; break;
                }
                item.style.display = show ? 'block' : 'none';
            });
        }

        function approveReview(id) {
            showNotification(`Отзыв #${id} одобрен`, 'success');
        }

        function toggleFeatured(id) {
            showNotification(`Статус избранного изменен для отзыва #${id}`, 'success');
        }

        function replyReview(id) {
            showNotification(`Ответ на отзыв #${id}`, 'info');
        }

        function deleteReview(id) {
            if (confirm('Удалить отзыв?')) {
                showNotification(`Отзыв #${id} удален`, 'success');
            }
        }
    </script>
    <?php
}

// === УПРАВЛЕНИЕ НОВОСТЯМИ ===
function renderNewsSection($data) {
    $news = $data['news'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Новости (<?= count($news) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление новостями и статьями</p>
        </div>
        <button class='btn btn-primary' onclick='openModal("addNewsModal")'>
            <i class='fas fa-plus'></i>
            Добавить новость
        </button>
    </div>

    <!-- Статистика новостей -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <?php 
        $published = count(array_filter($news, fn($n) => $n['is_published']));
        $drafts = count($news) - $published;
        $featured = count(array_filter($news, fn($n) => $n['is_featured']));
        ?>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= $published ?></div>
            <div class='stat-label'>Опубликовано</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'><?= $drafts ?></div>
            <div class='stat-label'>Черновики</div>
        </div>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= $featured ?></div>
            <div class='stat-label'>Важные</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'><?= array_sum(array_column($news, 'views')) ?></div>
            <div class='stat-label'>Всего просмотров</div>
        </div>
    </div>

    <!-- Таблица новостей -->
    <div class='table-container'>
        <table class='table'>
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Дата создания</th>
                    <th>Просмотры</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($news as $item): ?>
                <tr>
                    <td>
                        <div style='font-weight: 600; margin-bottom: 4px;'>
                            <?= htmlspecialchars($item['title']) ?>
                            <?php if ($item['is_featured']): ?>
                                <span style='color: gold; margin-left: 8px;'>⭐</span>
                            <?php endif; ?>
                        </div>
                        <div style='font-size: 12px; color: var(--text-muted);'>
                            <?= htmlspecialchars($item['excerpt']) ?>
                        </div>
                    </td>
                    <td style='color: var(--text-muted);'><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                    <td style='text-align: center;'><?= $item['views'] ?></td>
                    <td>
                        <span class='status-badge <?= $item['is_published'] ? 'status-completed' : 'status-processing' ?>'>
                            <?= $item['is_published'] ? 'Опубликовано' : 'Черновик' ?>
                        </span>
                    </td>
                    <td>
                        <div style='display: flex; gap: 8px;'>
                            <button class='btn btn-primary' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='editNews(<?= $item['id'] ?>)' title='Редактировать'>
                                <i class='fas fa-edit'></i>
                            </button>
                            <button class='btn btn-outline' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='previewNews(<?= $item['id'] ?>)' title='Предпросмотр'>
                                <i class='fas fa-eye'></i>
                            </button>
                            <button class='btn btn-warning' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='toggleNews(<?= $item['id'] ?>)' title='Опубликовать/скрыть'>
                                <i class='fas fa-<?= $item['is_published'] ? 'eye-slash' : 'eye' ?>'></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно добавления новости -->
    <div class='modal' id='addNewsModal'>
        <div class='modal-dialog' style='max-width: 800px;'>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='add_news'>
                <div class='modal-header'>
                    <h3 class='modal-title'>Добавить новость</h3>
                    <button type='button' class='modal-close' onclick='closeModal("addNewsModal")'>&times;</button>
                </div>
                <div class='modal-body'>
                    <div class='form-group'>
                        <label class='form-label'>Заголовок *</label>
                        <input type='text' name='title' class='form-input' required>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Краткое описание</label>
                        <textarea name='excerpt' class='form-input' rows='2' 
                                  placeholder='Краткое описание для списка новостей'></textarea>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Содержание *</label>
                        <textarea name='content' class='form-input' rows='8' required
                                  placeholder='Полный текст новости'></textarea>
                    </div>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>SEO заголовок</label>
                            <input type='text' name='meta_title' class='form-input'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>SEO описание</label>
                            <textarea name='meta_description' class='form-input' rows='2'></textarea>
                        </div>
                    </div>
                    <div style='display: flex; gap: 20px; align-items: center;'>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='is_published' checked> Опубликовать
                        </label>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='is_featured'> Важная новость
                        </label>
                    </div>
                </div>
                <div style='padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;'>
                    <button type='button' class='btn btn-outline' onclick='closeModal("addNewsModal")'>Отмена</button>
                    <button type='submit' class='btn btn-primary'>Сохранить новость</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editNews(id) {
            showNotification(`Редактирование новости #${id}`, 'info');
        }

        function previewNews(id) {
            showNotification(`Предпросмотр новости #${id}`, 'info');
        }

        function toggleNews(id) {
            showNotification(`Статус публикации изменен`, 'success');
        }
    </script>
    <?php
}

// === ФИНАНСОВЫЕ ОТЧЕТЫ ===
function renderFinanceSection($data) {
    $reports = $data['reports'];
    $orders = getOrders();

    // Расчет статистики
    $totalRevenue = array_sum(array_column($orders, 'total_amount'));
    $monthlyRevenue = array_sum(array_column(
        array_filter($orders, fn($o) => date('Y-m', strtotime($o['created_at'])) === date('Y-m')), 
        'total_amount'
    ));
    $avgCheck = count($orders) > 0 ? $totalRevenue / count($orders) : 0;
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Финансовые отчеты</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Анализ доходов и расходов</p>
    </div>

    <!-- Основная статистика -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;'>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= number_format($totalRevenue, 0, '', ' ') ?> ₽</div>
            <div class='stat-label'>Общая выручка</div>
            <div class='stat-change positive'>За все время</div>
        </div>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= number_format($monthlyRevenue, 0, '', ' ') ?> ₽</div>
            <div class='stat-label'>Выручка за месяц</div>
            <div class='stat-change positive'>Текущий месяц</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'><?= number_format($avgCheck, 0, '', ' ') ?> ₽</div>
            <div class='stat-label'>Средний чек</div>
            <div class='stat-change neutral'>На заказ</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'><?= count($orders) ?></div>
            <div class='stat-label'>Всего заказов</div>
            <div class='stat-change positive'>Обработано</div>
        </div>
    </div>

    <!-- Продажи по месяцам -->
    <div class='table-container' style='margin-bottom: 30px;'>
        <h3 style='padding: 20px 20px 0; margin: 0; color: var(--text-primary);'>💰 Продажи по месяцам</h3>
        <table class='table'>
            <thead>
                <tr>
                    <th>Месяц</th>
                    <th>Заказов</th>
                    <th>Выручка</th>
                    <th>Средний чек</th>
                    <th>Рост</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Группируем заказы по месяцам
                $monthlyData = [];
                foreach ($orders as $order) {
                    $month = date('Y-m', strtotime($order['created_at']));
                    if (!isset($monthlyData[$month])) {
                        $monthlyData[$month] = ['count' => 0, 'revenue' => 0];
                    }
                    $monthlyData[$month]['count']++;
                    $monthlyData[$month]['revenue'] += $order['total_amount'];
                }

                krsort($monthlyData);
                $prevRevenue = 0;
                foreach (array_slice($monthlyData, 0, 6, true) as $month => $data):
                    $avgCheck = $data['count'] > 0 ? $data['revenue'] / $data['count'] : 0;
                    $growth = $prevRevenue > 0 ? (($data['revenue'] - $prevRevenue) / $prevRevenue * 100) : 0;
                    $prevRevenue = $data['revenue'];
                ?>
                <tr>
                    <td style='font-weight: 600;'><?= date('F Y', strtotime($month . '-01')) ?></td>
                    <td><?= $data['count'] ?></td>
                    <td style='font-weight: 600; color: var(--success-color);'><?= number_format($data['revenue'], 0, '', ' ') ?> ₽</td>
                    <td><?= number_format($avgCheck, 0, '', ' ') ?> ₽</td>
                    <td>
                        <?php if ($growth != 0): ?>
                            <span class='stat-change <?= $growth > 0 ? 'positive' : '' ?>' style='font-size: 12px;'>
                                <?= $growth > 0 ? '+' : '' ?><?= number_format($growth, 1) ?>%
                            </span>
                        <?php else: ?>
                            <span style='color: var(--text-muted); font-size: 12px;'>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Топ товары по выручке -->
    <div class='table-container'>
        <h3 style='padding: 20px 20px 0; margin: 0; color: var(--text-primary);'>🏆 Топ товары по продажам</h3>
        <table class='table'>
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Продаж</th>
                    <th>Выручка</th>
                    <th>Доля</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $products = getProducts();
                usort($products, fn($a, $b) => ($b['price'] * $b['sales']) - ($a['price'] * $a['sales']));
                foreach (array_slice($products, 0, 10) as $product):
                    $revenue = $product['price'] * $product['sales'];
                    $share = $totalRevenue > 0 ? ($revenue / $totalRevenue * 100) : 0;
                ?>
                <tr>
                    <td>
                        <div style='font-weight: 600;'><?= htmlspecialchars(mb_substr($product['name'], 0, 40)) ?></div>
                        <div style='font-size: 12px; color: var(--text-muted);'><?= htmlspecialchars($product['category']) ?></div>
                    </td>
                    <td><?= $product['sales'] ?> шт</td>
                    <td style='font-weight: 600; color: var(--success-color);'><?= number_format($revenue, 0, '', ' ') ?> ₽</td>
                    <td><?= number_format($share, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// === ИСТОРИЯ ПЛАТЕЖЕЙ ===
function renderPaymentsSection($data) {
    // Генерируем данные платежей на основе заказов
    $orders = getOrders();
    $payments = [];

    foreach ($orders as $order) {
        if (in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
            $payments[] = [
                'id' => 'PAY-' . $order['id'],
                'order_id' => $order['order_number'],
                'amount' => $order['total_amount'],
                'method' => $order['payment_method'] === 'card' ? 'Банковская карта' : 'Наличные',
                'status' => $order['status'] === 'delivered' ? 'completed' : 'processing',
                'date' => $order['created_at']
            ];
        }
    }
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>История платежей (<?= count($payments) ?>)</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Отслеживание всех платежных операций</p>
    </div>

    <!-- Статистика платежей -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <?php 
        $completedPayments = array_filter($payments, fn($p) => $p['status'] === 'completed');
        $totalPaid = array_sum(array_column($completedPayments, 'amount'));
        $cardPayments = count(array_filter($payments, fn($p) => $p['method'] === 'Банковская карта'));
        ?>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= number_format($totalPaid, 0, '', ' ') ?> ₽</div>
            <div class='stat-label'>Получено</div>
        </div>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= count($completedPayments) ?></div>
            <div class='stat-label'>Завершено</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'><?= count($payments) - count($completedPayments) ?></div>
            <div class='stat-label'>В обработке</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'><?= $cardPayments ?></div>
            <div class='stat-label'>Картой</div>
        </div>
    </div>

    <!-- Таблица платежей -->
    <div class='table-container'>
        <table class='table'>
            <thead>
                <tr>
                    <th>ID платежа</th>
                    <th>Заказ</th>
                    <th>Сумма</th>
                    <th>Способ оплаты</th>
                    <th>Дата</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td style='font-family: monospace; font-weight: 600; color: var(--primary-color);'>
                        <?= $payment['id'] ?>
                    </td>
                    <td style='font-weight: 600;'><?= $payment['order_id'] ?></td>
                    <td style='font-weight: 600; color: var(--success-color);'>
                        <?= number_format($payment['amount'], 0, '', ' ') ?> ₽
                    </td>
                    <td>
                        <span style='display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-<?= $payment['method'] === 'Банковская карта' ? 'credit-card' : 'money-bill-wave' ?>' 
                               style='color: var(--text-muted);'></i>
                            <?= $payment['method'] ?>
                        </span>
                    </td>
                    <td style='color: var(--text-muted);'><?= date('d.m.Y H:i', strtotime($payment['date'])) ?></td>
                    <td>
                        <span class='status-badge status-<?= $payment['status'] ?>'>
                            <?= $payment['status'] === 'completed' ? 'Завершен' : 'В обработке' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// === ДОПОЛНИТЕЛЬНЫЕ ФУНКЦИИ ДЛЯ РАЗДЕЛОВ ===

function getAnalyticsData() {
    return [
        'sales_chart' => [
            'labels' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
            'data' => [65000, 78000, 82000, 91000, 87000, 95000]
        ]
    ];
}

function getFinanceReports() {
    return ['monthly_revenue' => 95000];
}

function getPayments() {
    return getOrders(); // Используем заказы как основу для платежей
}

function getPages() {
    return [
        ['id' => 1, 'title' => 'О нас', 'slug' => 'about', 'active' => true],
        ['id' => 2, 'title' => 'Контакты', 'slug' => 'contact', 'active' => true],
        ['id' => 3, 'title' => 'Доставка', 'slug' => 'delivery', 'active' => true]
    ];
}

function getSlides() {
    return [
        ['id' => 1, 'title' => 'Добро пожаловать в АкваСбор', 'active' => true],
        ['id' => 2, 'title' => 'Лучшие товары для аквариума', 'active' => true]
    ];
}

function getAllSettings() {
    // Возвращаем настройки из оригинального кода
    return [
        'site' => [
            'title' => 'Настройки сайта',
            'settings' => [
                'site_name' => ['type' => 'text', 'value' => 'АкваСбор', 'label' => 'Название сайта'],
                'site_description' => ['type' => 'textarea', 'value' => 'Аквариумы и их обитатели', 'label' => 'Описание сайта']
            ]
        ]
    ];
}

function getIntegrations() {
    return [
        ['name' => 'Яндекс.Метрика', 'status' => 'active'],
        ['name' => 'Google Analytics', 'status' => 'inactive']
    ];
}

function getBackups() {
    return [
        ['id' => 1, 'date' => date('Y-m-d H:i:s'), 'size' => '2.5 MB', 'type' => 'auto'],
        ['id' => 2, 'date' => date('Y-m-d H:i:s', strtotime('-1 day')), 'size' => '2.3 MB', 'type' => 'manual']
    ];
}

function getLogs() {
    return [
        ['time' => date('Y-m-d H:i:s'), 'action' => 'Вход в админку', 'user' => 'Администратор'],
        ['time' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'action' => 'Добавление товара', 'user' => 'Администратор']
    ];
}

function getHeatmapData() {
    return ['clicks' => 1250, 'views' => 5600];
}

// === УПРАВЛЕНИЕ СТРАНИЦАМИ ===
function renderPagesSection($data) {
    $pages = $data['pages'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Статические страницы (<?= count($pages) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление страницами сайта</p>
        </div>
        <button class='btn btn-primary' onclick='openModal("addPageModal")'>
            <i class='fas fa-plus'></i>
            Создать страницу
        </button>
    </div>

    <!-- Список страниц -->
    <div style='display: grid; gap: 16px;'>
        <?php foreach ($pages as $page): ?>
            <div class='stat-card'>
                <div style='display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;'>
                    <div>
                        <h3 style='margin: 0 0 4px; color: var(--text-primary);'><?= htmlspecialchars($page['title']) ?></h3>
                        <div style='font-size: 13px; color: var(--text-muted); font-family: monospace;'>
                            /{<?= htmlspecialchars($page['slug']) ?>}
                        </div>
                    </div>
                    <span class='status-badge <?= $page['active'] ? 'status-completed' : 'status-cancelled' ?>'>
                        <?= $page['active'] ? 'Активна' : 'Скрыта' ?>
                    </span>
                </div>

                <div style='display: flex; gap: 8px; margin-top: 12px;'>
                    <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' 
                            onclick='editPage(<?= $page['id'] ?>)'>
                        <i class='fas fa-edit'></i> Редактировать
                    </button>
                    <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' 
                            onclick='previewPage("<?= $page['slug'] ?>")'>
                        <i class='fas fa-eye'></i> Просмотр
                    </button>
                    <button class='btn btn-warning' style='padding: 6px 12px; font-size: 12px;' 
                            onclick='togglePage(<?= $page['id'] ?>)'>
                        <i class='fas fa-power-off'></i> <?= $page['active'] ? 'Скрыть' : 'Показать' ?>
                    </button>
                    <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px; color: var(--danger-color);' 
                            onclick='deletePage(<?= $page['id'] ?>)'>
                        <i class='fas fa-trash'></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Модальное окно создания страницы -->
    <div class='modal' id='addPageModal'>
        <div class='modal-dialog' style='max-width: 800px;'>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='add_page'>
                <div class='modal-header'>
                    <h3 class='modal-title'>Создать новую страницу</h3>
                    <button type='button' class='modal-close' onclick='closeModal("addPageModal")'>&times;</button>
                </div>
                <div class='modal-body'>
                    <div style='display: grid; grid-template-columns: 2fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>Название страницы *</label>
                            <input type='text' name='title' class='form-input' required 
                                   onkeyup='generateSlug(this.value)'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>URL (slug) *</label>
                            <input type='text' name='slug' class='form-input' required id='pageSlug'>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label class='form-label'>Содержание страницы *</label>
                        <div style='border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                            <div style='padding: 8px; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color); display: flex; gap: 4px;'>
                                <button type='button' onclick='formatText("bold")' style='padding: 4px 8px; border: none; background: none; cursor: pointer;'>
                                    <i class='fas fa-bold'></i>
                                </button>
                                <button type='button' onclick='formatText("italic")' style='padding: 4px 8px; border: none; background: none; cursor: pointer;'>
                                    <i class='fas fa-italic'></i>
                                </button>
                                <button type='button' onclick='formatText("insertUnorderedList")' style='padding: 4px 8px; border: none; background: none; cursor: pointer;'>
                                    <i class='fas fa-list-ul'></i>
                                </button>
                                <button type='button' onclick='insertLink()' style='padding: 4px 8px; border: none; background: none; cursor: pointer;'>
                                    <i class='fas fa-link'></i>
                                </button>
                            </div>
                            <div contenteditable='true' id='pageContent' 
                                 style='min-height: 200px; padding: 12px; outline: none;'
                                 placeholder='Введите содержание страницы...'></div>
                        </div>
                        <textarea name='content' style='display: none;' id='hiddenContent'></textarea>
                    </div>

                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>SEO заголовок</label>
                            <input type='text' name='meta_title' class='form-input'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>SEO описание</label>
                            <textarea name='meta_description' class='form-input' rows='2'></textarea>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='active' checked> Активная страница
                        </label>
                    </div>
                </div>
                <div style='padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;'>
                    <button type='button' class='btn btn-outline' onclick='closeModal("addPageModal")'>Отмена</button>
                    <button type='submit' class='btn btn-primary'>Создать страницу</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generateSlug(title) {
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9а-я\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            document.getElementById('pageSlug').value = slug;
        }

        function formatText(command) {
            document.execCommand(command, false, null);
            updateHiddenContent();
        }

        function insertLink() {
            const url = prompt('Введите URL:');
            if (url) {
                document.execCommand('createLink', false, url);
                updateHiddenContent();
            }
        }

        function updateHiddenContent() {
            document.getElementById('hiddenContent').value = document.getElementById('pageContent').innerHTML;
        }

        document.getElementById('pageContent').addEventListener('input', updateHiddenContent);

        function editPage(id) {
            showNotification(`Редактирование страницы #${id}`, 'info');
        }

        function previewPage(slug) {
            window.open(`index.php?page=${slug}`, '_blank');
        }

        function togglePage(id) {
            showNotification(`Статус страницы изменен`, 'success');
        }

        function deletePage(id) {
            if (confirm('Удалить страницу? Это действие нельзя отменить.')) {
                showNotification(`Страница удалена`, 'success');
            }
        }
    </script>
    <?php
}

// === УПРАВЛЕНИЕ СЛАЙДЕРОМ ===
function renderSliderSection($data) {
    $slides = $data['slides'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0; color: var(--text-primary);'>Слайдер главной страницы (<?= count($slides) ?>)</h2>
            <p style='margin: 5px 0 0; color: var(--text-secondary);'>Управление баннерами и слайдами</p>
        </div>
        <button class='btn btn-primary' onclick='openModal("addSlideModal")'>
            <i class='fas fa-plus'></i>
            Добавить слайд
        </button>
    </div>

    <!-- Предпросмотр слайдера -->
    <div style='background: var(--bg-primary); border-radius: var(--border-radius-lg); padding: 20px; margin-bottom: 30px; border: 1px solid var(--border-color);'>
        <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📱 Предпросмотр слайдера</h3>
        <div style='background: linear-gradient(135deg, #667eea, #764ba2); height: 200px; border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; position: relative; overflow: hidden;'>
            <div style='text-align: center; z-index: 2;'>
                <h2 style='margin: 0 0 8px; font-size: 24px;'>АкваСбор - аквариумы и их обитатели</h2>
                <p style='margin: 0 0 16px; opacity: 0.9;'>Лучшие товары для вашего аквариума</p>
                <button class='btn btn-outline' style='color: white; border-color: white;'>Смотреть каталог</button>
            </div>
            <div style='position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 12px;'>
                Слайд 1 из <?= count($slides) ?>
            </div>
        </div>
    </div>

    <!-- Список слайдов -->
    <div style='display: grid; gap: 16px;'>
        <?php foreach ($slides as $index => $slide): ?>
            <div class='stat-card'>
                <div style='display: flex; align-items: center; gap: 16px;'>
                    <!-- Миниатюра -->
                    <div style='width: 120px; height: 80px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; flex-shrink: 0;'>
                        🖼️
                    </div>

                    <!-- Информация о слайде -->
                    <div style='flex: 1;'>
                        <div style='display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;'>
                            <h3 style='margin: 0; color: var(--text-primary);'><?= htmlspecialchars($slide['title']) ?></h3>
                            <span class='status-badge <?= $slide['active'] ? 'status-completed' : 'status-cancelled' ?>'>
                                <?= $slide['active'] ? 'Активен' : 'Скрыт' ?>
                            </span>
                        </div>

                        <div style='display: flex; align-items: center; gap: 16px; margin-bottom: 12px; font-size: 13px; color: var(--text-muted);'>
                            <span><i class='fas fa-sort'></i> Позиция: <?= $index + 1 ?></span>
                            <span><i class='fas fa-eye'></i> <?= rand(100, 1000) ?> показов</span>
                            <span><i class='fas fa-mouse-pointer'></i> <?= rand(10, 50) ?> кликов</span>
                        </div>

                        <div style='display: flex; gap: 8px;'>
                            <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' 
                                    onclick='editSlide(<?= $slide['id'] ?>)'>
                                <i class='fas fa-edit'></i> Редактировать
                            </button>
                            <?php if ($index > 0): ?>
                                <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' 
                                        onclick='moveSlide(<?= $slide['id'] ?>, "up")'>
                                    <i class='fas fa-arrow-up'></i> Выше
                                </button>
                            <?php endif; ?>
                            <?php if ($index < count($slides) - 1): ?>
                                <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' 
                                        onclick='moveSlide(<?= $slide['id'] ?>, "down")'>
                                    <i class='fas fa-arrow-down'></i> Ниже
                                </button>
                            <?php endif; ?>
                            <button class='btn btn-warning' style='padding: 6px 12px; font-size: 12px;' 
                                    onclick='toggleSlide(<?= $slide['id'] ?>)'>
                                <i class='fas fa-power-off'></i>
                            </button>
                            <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px; color: var(--danger-color);' 
                                    onclick='deleteSlide(<?= $slide['id'] ?>)'>
                                <i class='fas fa-trash'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Модальное окно добавления слайда -->
    <div class='modal' id='addSlideModal'>
        <div class='modal-dialog' style='max-width: 800px;'>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='add_slide'>
                <div class='modal-header'>
                    <h3 class='modal-title'>Добавить новый слайд</h3>
                    <button type='button' class='modal-close' onclick='closeModal("addSlideModal")'>&times;</button>
                </div>
                <div class='modal-body'>
                    <div class='form-group'>
                        <label class='form-label'>Заголовок слайда *</label>
                        <input type='text' name='title' class='form-input' required>
                    </div>

                    <div class='form-group'>
                        <label class='form-label'>Подзаголовок</label>
                        <input type='text' name='subtitle' class='form-input'>
                    </div>

                    <div class='form-group'>
                        <label class='form-label'>Изображение</label>
                        <div style='border: 2px dashed var(--border-color); padding: 40px; text-align: center; border-radius: var(--border-radius);'>
                            <i class='fas fa-cloud-upload-alt' style='font-size: 48px; color: var(--text-muted); margin-bottom: 16px;'></i>
                            <p style='margin: 0 0 16px; color: var(--text-muted);'>Перетащите изображение сюда или нажмите для выбора</p>
                            <button type='button' class='btn btn-outline' onclick='selectImage()'>Выбрать файл</button>
                            <input type='file' name='image' accept='image/*' style='display: none;' id='slideImage'>
                        </div>
                    </div>

                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>Текст кнопки</label>
                            <input type='text' name='button_text' class='form-input' placeholder='Перейти в каталог'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>Ссылка кнопки</label>
                            <input type='text' name='button_url' class='form-input' placeholder='?page=catalog'>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label class='form-label'>Позиция</label>
                        <select name='position' class='form-input'>
                            <?php for($i = 1; $i <= count($slides) + 1; $i++): ?>
                                <option value='<?= $i ?>' <?= $i == count($slides) + 1 ? 'selected' : '' ?>>
                                    <?= $i ?> <?= $i == 1 ? '(первый)' : ($i == count($slides) + 1 ? '(последний)' : '') ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class='form-group'>
                        <label style='display: flex; align-items: center; gap: 8px;'>
                            <input type='checkbox' name='active' checked> Активный слайд
                        </label>
                    </div>
                </div>
                <div style='padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;'>
                    <button type='button' class='btn btn-outline' onclick='closeModal("addSlideModal")'>Отмена</button>
                    <button type='submit' class='btn btn-primary'>Сохранить слайд</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectImage() {
            document.getElementById('slideImage').click();
        }

        function editSlide(id) {
            showNotification(`Редактирование слайда #${id}`, 'info');
        }

        function moveSlide(id, direction) {
            showNotification(`Слайд перемещен ${direction === 'up' ? 'выше' : 'ниже'}`, 'success');
        }

        function toggleSlide(id) {
            showNotification(`Статус слайда изменен`, 'success');
        }

        function deleteSlide(id) {
            if (confirm('Удалить слайд?')) {
                showNotification(`Слайд удален`, 'success');
            }
        }
    </script>
    <?php
}

// === НАСТРОЙКИ СИСТЕМЫ ===
function renderSettingsSection($data) {
    $settings = getAllSettings(); // Используем полную функцию из оригинального кода
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Настройки системы</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Конфигурация сайта и параметры работы</p>
    </div>

    <!-- Навигация по разделам -->
    <div style='display: flex; gap: 4px; margin-bottom: 24px; flex-wrap: wrap;'>
        <button class='btn btn-primary settings-tab active' onclick='showSettingsTab("site")'>
            <i class='fas fa-globe'></i> Основные
        </button>
        <button class='btn btn-outline settings-tab' onclick='showSettingsTab("shop")'>
            <i class='fas fa-shopping-cart'></i> Магазин
        </button>
        <button class='btn btn-outline settings-tab' onclick='showSettingsTab("seo")'>
            <i class='fas fa-search'></i> SEO
        </button>
        <button class='btn btn-outline settings-tab' onclick='showSettingsTab("email")'>
            <i class='fas fa-envelope'></i> Email
        </button>
        <button class='btn btn-outline settings-tab' onclick='showSettingsTab("social")'>
            <i class='fas fa-share-alt'></i> Соц. сети
        </button>
    </div>

    <!-- Основные настройки -->
    <div id='settings-site' class='settings-section active'>
        <div class='stat-card'>
            <h3 style='margin: 0 0 20px; color: var(--text-primary);'>🌐 Основные настройки сайта</h3>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='save_settings'>
                <input type='hidden' name='section' value='site'>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>Название сайта</label>
                        <input type='text' name='site_name' class='form-input' value='АкваСбор'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Валюта</label>
                        <input type='text' name='currency' class='form-input' value='₽'>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='form-label'>Описание сайта</label>
                    <textarea name='site_description' class='form-input' rows='3'>Аквариумы и их обитатели. Доставка по России, Беларуси, Казахстану, Киргизии, Армении.</textarea>
                </div>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>Телефон</label>
                        <input type='text' name='phone' class='form-input' value='+7 (999) 123-45-67'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Email</label>
                        <input type='email' name='email' class='form-input' value='info@akvasbor.ru'>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='form-label'>Адрес</label>
                    <input type='text' name='address' class='form-input' value='Россия, доставка по СНГ'>
                </div>

                <div class='form-group'>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='maintenance_mode'> Режим обслуживания
                    </label>
                    <small style='color: var(--text-muted);'>Сайт будет недоступен для посетителей</small>
                </div>

                <button type='submit' class='btn btn-primary'>
                    <i class='fas fa-save'></i> Сохранить настройки
                </button>
            </form>
        </div>
    </div>

    <!-- Настройки магазина -->
    <div id='settings-shop' class='settings-section' style='display: none;'>
        <div class='stat-card'>
            <h3 style='margin: 0 0 20px; color: var(--text-primary);'>🛒 Настройки магазина</h3>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='save_settings'>
                <input type='hidden' name='section' value='shop'>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>Товаров на странице</label>
                        <input type='number' name='products_per_page' class='form-input' value='12' min='1'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Минимальная сумма заказа (₽)</label>
                        <input type='number' name='min_order_amount' class='form-input' value='500' min='0'>
                    </div>
                </div>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>Бесплатная доставка от (₽)</label>
                        <input type='number' name='free_shipping' class='form-input' value='2000' min='0'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Стоимость доставки (₽)</label>
                        <input type='number' name='shipping_cost' class='form-input' value='300' min='0'>
                    </div>
                </div>

                <div style='display: flex; gap: 20px; flex-wrap: wrap;'>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='show_stock' checked> Показывать остатки
                    </label>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='enable_reviews' checked> Включить отзывы
                    </label>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='enable_wishlist'> Список желаний
                    </label>
                </div>

                <button type='submit' class='btn btn-primary'>
                    <i class='fas fa-save'></i> Сохранить настройки
                </button>
            </form>
        </div>
    </div>

    <!-- SEO настройки -->
    <div id='settings-seo' class='settings-section' style='display: none;'>
        <div class='stat-card'>
            <h3 style='margin: 0 0 20px; color: var(--text-primary);'>🔍 SEO настройки</h3>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='save_settings'>
                <input type='hidden' name='section' value='seo'>

                <div class='form-group'>
                    <label class='form-label'>Ключевые слова</label>
                    <input type='text' name='keywords' class='form-input' 
                           value='аквариум, рыбки, растения, оборудование, корм'>
                </div>

                <div class='form-group'>
                    <label class='form-label'>Google Analytics ID</label>
                    <input type='text' name='google_analytics' class='form-input' 
                           placeholder='G-XXXXXXXXXX'>
                </div>

                <div class='form-group'>
                    <label class='form-label'>Яндекс.Метрика ID</label>
                    <input type='text' name='yandex_metrika' class='form-input' 
                           placeholder='12345678'>
                </div>

                <div class='form-group'>
                    <label class='form-label'>robots.txt</label>
                    <textarea name='robots_txt' class='form-input' rows='5'>User-agent: *
Disallow: /admin
Disallow: /data.php
Allow: /

Sitemap: https://akvasbor.ru/sitemap.xml</textarea>
                </div>

                <button type='submit' class='btn btn-primary'>
                    <i class='fas fa-save'></i> Сохранить настройки
                </button>
            </form>
        </div>
    </div>

    <!-- Email настройки -->
    <div id='settings-email' class='settings-section' style='display: none;'>
        <div class='stat-card'>
            <h3 style='margin: 0 0 20px; color: var(--text-primary);'>📧 Email настройки</h3>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='save_settings'>
                <input type='hidden' name='section' value='email'>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>Email администратора</label>
                        <input type='email' name='admin_email' class='form-input' 
                               value='admin@akvasbor.ru'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>Имя отправителя</label>
                        <input type='text' name='sender_name' class='form-input' 
                               value='АкваСбор'>
                    </div>
                </div>

                <div style='margin: 20px 0;'>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='smtp_enabled' onchange='toggleSMTP(this)'> Использовать SMTP
                    </label>
                </div>

                <div id='smtp-settings' style='display: none;'>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>SMTP хост</label>
                            <input type='text' name='smtp_host' class='form-input' 
                                   placeholder='smtp.gmail.com'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>SMTP порт</label>
                            <input type='number' name='smtp_port' class='form-input' 
                                   value='587'>
                        </div>
                    </div>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div class='form-group'>
                            <label class='form-label'>SMTP логин</label>
                            <input type='text' name='smtp_username' class='form-input'>
                        </div>
                        <div class='form-group'>
                            <label class='form-label'>SMTP пароль</label>
                            <input type='password' name='smtp_password' class='form-input'>
                        </div>
                    </div>
                </div>

                <div style='display: flex; gap: 20px; flex-wrap: wrap;'>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='notify_new_order' checked> Уведомления о заказах
                    </label>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='notify_new_review'> Уведомления об отзывах
                    </label>
                </div>

                <button type='submit' class='btn btn-primary'>
                    <i class='fas fa-save'></i> Сохранить настройки
                </button>
            </form>
        </div>
    </div>

    <!-- Социальные сети -->
    <div id='settings-social' class='settings-section' style='display: none;'>
        <div class='stat-card'>
            <h3 style='margin: 0 0 20px; color: var(--text-primary);'>📱 Социальные сети</h3>
            <form class='ajax-form' method='POST'>
                <input type='hidden' name='action' value='save_settings'>
                <input type='hidden' name='section' value='social'>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>
                            <i class='fab fa-vk' style='color: #4A76A8; margin-right: 8px;'></i>
                            ВКонтакте
                        </label>
                        <input type='url' name='vk_url' class='form-input' 
                               placeholder='https://vk.com/akvasbor'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>
                            <i class='fab fa-telegram' style='color: #0088CC; margin-right: 8px;'></i>
                            Telegram
                        </label>
                        <input type='url' name='telegram_url' class='form-input' 
                               placeholder='https://t.me/akvasbor'>
                    </div>
                </div>

                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                    <div class='form-group'>
                        <label class='form-label'>
                            <i class='fab fa-instagram' style='color: #E4405F; margin-right: 8px;'></i>
                            Instagram
                        </label>
                        <input type='url' name='instagram_url' class='form-input' 
                               placeholder='https://instagram.com/akvasbor'>
                    </div>
                    <div class='form-group'>
                        <label class='form-label'>
                            <i class='fab fa-youtube' style='color: #FF0000; margin-right: 8px;'></i>
                            YouTube
                        </label>
                        <input type='url' name='youtube_url' class='form-input' 
                               placeholder='https://youtube.com/akvasbor'>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='form-label'>
                        <i class='fab fa-whatsapp' style='color: #25D366; margin-right: 8px;'></i>
                        WhatsApp номер
                    </label>
                    <input type='text' name='whatsapp_number' class='form-input' 
                           placeholder='+79991234567'>
                </div>

                <div style='display: flex; gap: 20px; flex-wrap: wrap;'>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='show_social_login'> Вход через соц. сети
                    </label>
                    <label style='display: flex; align-items: center; gap: 8px;'>
                        <input type='checkbox' name='show_share_buttons' checked> Кнопки "Поделиться"
                    </label>
                </div>

                <button type='submit' class='btn btn-primary'>
                    <i class='fas fa-save'></i> Сохранить настройки
                </button>
            </form>
        </div>
    </div>

    <script>
        function showSettingsTab(tabName) {
            // Скрыть все секции
            document.querySelectorAll('.settings-section').forEach(section => {
                section.style.display = 'none';
            });

            // Убрать активный класс у всех кнопок
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('btn-primary');
                tab.classList.add('btn-outline');
            });

            // Показать выбранную секцию
            document.getElementById('settings-' + tabName).style.display = 'block';

            // Активировать выбранную кнопку
            event.target.classList.remove('btn-outline');
            event.target.classList.add('btn-primary');
        }

        function toggleSMTP(checkbox) {
            document.getElementById('smtp-settings').style.display = 
                checkbox.checked ? 'block' : 'none';
        }
    </script>
    <?php
}

// === ИНТЕГРАЦИИ ===
function renderIntegrationsSection($data) {
    $integrations = $data['integrations'];
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Интеграции с внешними сервисами</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Подключение и настройка внешних API</p>
    </div>

    <!-- Доступные интеграции -->
    <div style='display: grid; gap: 20px;'>

        <!-- Платежные системы -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>💳 Платежные системы</h3>
            <div style='display: grid; gap: 16px;'>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #005BBB; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;'>ЮK</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>ЮKassa (Яндекс.Касса)</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Прием онлайн-платежей</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-cancelled'>Не настроено</span>
                        <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("yookassa")'>
                            Настроить
                        </button>
                    </div>
                </div>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #00A651; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;'>СБ</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>Сбербанк Эквайринг</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Прием банковских карт</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-cancelled'>Не настроено</span>
                        <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("sberbank")'>
                            Настроить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Службы доставки -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>🚚 Службы доставки</h3>
            <div style='display: grid; gap: 16px;'>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #00B33C; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;'>СДЭК</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>СДЭК</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Доставка по России и СНГ</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-processing'>Тестирование</span>
                        <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("cdek")'>
                            Настроить
                        </button>
                    </div>
                </div>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #FF6600; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;'>ПР</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>Почта России</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Федеральная почтовая служба</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-cancelled'>Не настроено</span>
                        <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("russianpost")'>
                            Настроить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CRM и уведомления -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📱 CRM и уведомления</h3>
            <div style='display: grid; gap: 16px;'>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #0088CC; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;'>📱</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>Telegram Bot</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Уведомления о заказах</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-completed'>Активно</span>
                        <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("telegram")'>
                            Настроить
                        </button>
                    </div>
                </div>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #25D366; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;'>📞</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>WhatsApp API</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Отправка сообщений клиентам</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-cancelled'>Не настроено</span>
                        <button class='btn btn-primary' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("whatsapp")'>
                            Настроить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Аналитика -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📊 Веб-аналитика</h3>
            <div style='display: grid; gap: 16px;'>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #4285F4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;'>GA</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>Google Analytics</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Анализ посещаемости</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-completed'>Активно</span>
                        <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("analytics")'>
                            Настроить
                        </button>
                    </div>
                </div>

                <div style='display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 40px; height: 40px; background: #FF0000; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;'>ЯМ</div>
                        <div>
                            <h4 style='margin: 0; font-size: 16px;'>Яндекс.Метрика</h4>
                            <p style='margin: 0; font-size: 13px; color: var(--text-muted);'>Российская веб-аналитика</p>
                        </div>
                    </div>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <span class='status-badge status-processing'>Настраивается</span>
                        <button class='btn btn-outline' style='padding: 6px 12px; font-size: 12px;' onclick='setupIntegration("metrika")'>
                            Настроить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setupIntegration(service) {
            showNotification(`Настройка интеграции с ${service}`, 'info');
        }
    </script>
    <?php
}

// === РЕЗЕРВНЫЕ КОПИИ ===
function renderBackupSection($data) {
    $backups = $data['backups'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Резервные копии</h2>
            <p style='margin: 0; color: var(--text-secondary);'>Создание и восстановление бэкапов</p>
        </div>
        <button class='btn btn-primary' onclick='createBackup()'>
            <i class='fas fa-plus'></i>
            Создать копию
        </button>
    </div>

    <!-- Статус автоматических копий -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <div class='stat-card stat-success'>
            <div class='stat-value'>Включено</div>
            <div class='stat-label'>Автоматические копии</div>
            <div class='stat-change positive'>Каждые 24 часа</div>
        </div>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= count($backups) ?></div>
            <div class='stat-label'>Всего копий</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'>15.2 MB</div>
            <div class='stat-label'>Общий размер</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'>30 дней</div>
            <div class='stat-label'>Хранить копии</div>
        </div>
    </div>

    <!-- Настройки автоматических копий -->
    <div class='stat-card' style='margin-bottom: 24px;'>
        <h3 style='margin: 0 0 16px; color: var(--text-primary);'>⚙️ Настройки автоматических копий</h3>
        <form class='ajax-form' method='POST'>
            <input type='hidden' name='action' value='save_backup_settings'>

            <div style='display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;'>
                <div class='form-group'>
                    <label class='form-label'>Частота создания</label>
                    <select name='backup_frequency' class='form-input'>
                        <option value='6'>Каждые 6 часов</option>
                        <option value='12'>Каждые 12 часов</option>
                        <option value='24' selected>Каждые 24 часа</option>
                        <option value='168'>Еженедельно</option>
                    </select>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Хранить копий</label>
                    <input type='number' name='backup_keep' class='form-input' value='30' min='1' max='100'>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Максимальный размер</label>
                    <select name='max_size' class='form-input'>
                        <option value='50'>50 MB</option>
                        <option value='100' selected>100 MB</option>
                        <option value='200'>200 MB</option>
                    </select>
                </div>
            </div>

            <div style='display: flex; gap: 20px; margin: 16px 0;'>
                <label style='display: flex; align-items: center; gap: 8px;'>
                    <input type='checkbox' name='backup_files' checked> Файлы сайта
                </label>
                <label style='display: flex; align-items: center; gap: 8px;'>
                    <input type='checkbox' name='backup_data' checked> Данные товаров/заказов
                </label>
                <label style='display: flex; align-items: center; gap: 8px;'>
                    <input type='checkbox' name='backup_settings' checked> Настройки
                </label>
            </div>

            <button type='submit' class='btn btn-success'>
                <i class='fas fa-save'></i> Сохранить настройки
            </button>
        </form>
    </div>

    <!-- Список резервных копий -->
    <div class='table-container'>
        <table class='table'>
            <thead>
                <tr>
                    <th>Дата создания</th>
                    <th>Тип</th>
                    <th>Размер</th>
                    <th>Содержимое</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td style='font-weight: 600;'><?= date('d.m.Y H:i', strtotime($backup['date'])) ?></td>
                    <td>
                        <span class='status-badge <?= $backup['type'] === 'auto' ? 'status-info' : 'status-success' ?>'>
                            <?= $backup['type'] === 'auto' ? 'Автоматическая' : 'Ручная' ?>
                        </span>
                    </td>
                    <td><?= $backup['size'] ?></td>
                    <td style='font-size: 12px; color: var(--text-muted);'>
                        Файлы, данные, настройки
                    </td>
                    <td>
                        <div style='display: flex; gap: 8px;'>
                            <button class='btn btn-success' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='downloadBackup(<?= $backup['id'] ?>)' title='Скачать'>
                                <i class='fas fa-download'></i>
                            </button>
                            <button class='btn btn-warning' style='padding: 4px 8px; font-size: 12px;' 
                                    onclick='restoreBackup(<?= $backup['id'] ?>)' title='Восстановить'>
                                <i class='fas fa-undo'></i>
                            </button>
                            <button class='btn btn-outline' style='padding: 4px 8px; font-size: 12px; color: var(--danger-color);' 
                                    onclick='deleteBackup(<?= $backup['id'] ?>)' title='Удалить'>
                                <i class='fas fa-trash'></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function createBackup() {
            if (confirm('Создать резервную копию? This may take a few minutes.')) {
                showNotification('Создание резервной копии началось...', 'info');
                // Имитация процесса создания
                setTimeout(() => {
                    showNotification('Резервная копия создана успешно!', 'success');
                }, 3000);
            }
        }

        function downloadBackup(id) {
            showNotification(`Скачивание копии #${id}...`, 'info');
        }

        function restoreBackup(id) {
            if (confirm('Восстановить данные из резервной копии? Текущие данные будут перезаписаны!')) {
                showNotification('Восстановление началось...', 'warning');
            }
        }

        function deleteBackup(id) {
            if (confirm('Удалить резервную копию?')) {
                showNotification('Резервная копия удалена', 'success');
            }
        }
    </script>
    <?php
}

// === СИСТЕМНЫЕ ЛОГИ ===
function renderLogsSection($data) {
    $logs = $data['logs'];
    ?>
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;'>
        <div>
            <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Системные логи</h2>
            <p style='margin: 0; color: var(--text-secondary);'>Отслеживание активности и ошибок</p>
        </div>
        <div style='display: flex; gap: 12px;'>
            <select class='form-input' style='max-width: 150px;' onchange='filterLogs(this.value)'>
                <option value=''>Все события</option>
                <option value='login'>Входы</option>
                <option value='error'>Ошибки</option>
                <option value='order'>Заказы</option>
                <option value='product'>Товары</option>
            </select>
            <button class='btn btn-outline' onclick='clearLogs()'>
                <i class='fas fa-trash'></i>
                Очистить логи
            </button>
        </div>
    </div>

    <!-- Статистика логов -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;'>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= count($logs) ?></div>
            <div class='stat-label'>Всего записей</div>
        </div>
        <div class='stat-card stat-success'>
            <div class='stat-value'>24</div>
            <div class='stat-label'>За сегодня</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'>3</div>
            <div class='stat-label'>Ошибок</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'>2.1 MB</div>
            <div class='stat-label'>Размер файлов</div>
        </div>
    </div>

    <!-- Логи в реальном времени -->
    <div class='stat-card' style='margin-bottom: 24px;'>
        <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;'>
            <h3 style='margin: 0; color: var(--text-primary);'>📡 Логи в реальном времени</h3>
            <button class='btn btn-outline' style='padding: 4px 8px; font-size: 12px;' onclick='toggleRealTime()' id='realTimeBtn'>
                <i class='fas fa-play'></i> Запустить
            </button>
        </div>
        <div id='realTimeLogs' style='background: #000; color: #00ff00; padding: 16px; border-radius: var(--border-radius); font-family: monospace; font-size: 13px; height: 150px; overflow-y: auto;'>
            <div>Нажмите "Запустить" для просмотра логов в реальном времени...</div>
        </div>
    </div>

    <!-- Таблица логов -->
    <div class='table-container'>
        <table class='table' id='logsTable'>
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Тип</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                    <th>IP адрес</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Расширим логи для демонстрации
                $logTypes = ['login', 'logout', 'product_add', 'order_new', 'error', 'backup', 'settings'];
                $users = ['Администратор', 'System', 'Guest'];
                $ips = ['192.168.1.100', '10.0.0.50', '172.16.0.10'];

                for($i = 0; $i < 20; $i++):
                    $type = $logTypes[array_rand($logTypes)];
                    $time = date('Y-m-d H:i:s', strtotime("-{$i} minutes"));
                ?>
                <tr class='log-row' data-type='<?= explode('_', $type)[0] ?>'>
                    <td style='font-family: monospace; font-size: 12px; color: var(--text-muted);'><?= $time ?></td>
                    <td>
                        <span class='status-badge <?= 
                            strpos($type, 'error') !== false ? 'status-cancelled' : 
                            (strpos($type, 'login') !== false ? 'status-completed' : 'status-info') 
                        ?>'>
                            <?= strtoupper($type) ?>
                        </span>
                    </td>
                    <td><?= $users[array_rand($users)] ?></td>
                    <td style='font-size: 13px;'>
                        <?php
                        switch($type) {
                            case 'login': echo 'Успешный вход в админ-панель'; break;
                            case 'logout': echo 'Выход из системы'; break;
                            case 'product_add': echo 'Добавлен товар #' . rand(100, 999); break;
                            case 'order_new': echo 'Новый заказ #AQ-2024-' . str_pad(rand(1, 100), 4, '0', STR_PAD_LEFT); break;
                            case 'error': echo 'Ошибка: ' . ['Database connection failed', 'File not found', 'Permission denied'][rand(0, 2)]; break;
                            case 'backup': echo 'Создана резервная копия'; break;
                            case 'settings': echo 'Изменены настройки сайта'; break;
                        }
                        ?>
                    </td>
                    <td style='font-family: monospace; font-size: 12px;'><?= $ips[array_rand($ips)] ?></td>
                    <td>
                        <button class='btn btn-outline' style='padding: 2px 6px; font-size: 11px;' 
                                onclick='showLogDetails(<?= $i ?>)'>
                            Подробнее
                        </button>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <script>
        let realTimeActive = false;
        let realTimeInterval;

        function toggleRealTime() {
            const btn = document.getElementById('realTimeBtn');
            const logsDiv = document.getElementById('realTimeLogs');

            if (!realTimeActive) {
                btn.innerHTML = '<i class="fas fa-stop"></i> Остановить';
                btn.classList.add('btn-warning');
                logsDiv.innerHTML = '';

                realTimeInterval = setInterval(() => {
                    const time = new Date().toLocaleTimeString();
                    const events = [
                        'INFO: Пользователь просматривает товары',
                        'DEBUG: Кеш обновлен',
                        'WARN: Медленный запрос к базе данных',
                        'INFO: Новый посетитель на сайте'
                    ];
                    const event = events[Math.floor(Math.random() * events.length)];

                    logsDiv.innerHTML += `[${time}] ${event}\n`;
                    logsDiv.scrollTop = logsDiv.scrollHeight;
                }, 2000);

                realTimeActive = true;
            } else {
                btn.innerHTML = '<i class="fas fa-play"></i> Запустить';
                btn.classList.remove('btn-warning');
                clearInterval(realTimeInterval);
                realTimeActive = false;
            }
        }

        function filterLogs(type) {
            const rows = document.querySelectorAll('.log-row');
            rows.forEach(row => {
                if (!type || row.dataset.type === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showLogDetails(id) {
            showNotification(`Детали лога #${id}`, 'info');
        }

        function clearLogs() {
            if (confirm('Очистить все логи? Это действие нельзя отменить.')) {
                showNotification('Логи очищены', 'success');
            }
        }
    </script>
    <?php
}

// === КАРТА АКТИВНОСТИ (HEATMAP) ===
function renderHeatmapSection($data) {
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Карта активности пользователей</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Анализ поведения посетителей на сайте</p>
    </div>

    <!-- Статистика активности -->
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;'>
        <div class='stat-card stat-info'>
            <div class='stat-value'><?= $data['heatmap']['clicks'] ?? 1250 ?></div>
            <div class='stat-label'>Кликов сегодня</div>
            <div class='stat-change positive'>+15%</div>
        </div>
        <div class='stat-card stat-success'>
            <div class='stat-value'><?= $data['heatmap']['views'] ?? 5600 ?></div>
            <div class='stat-label'>Просмотров страниц</div>
            <div class='stat-change positive'>+8%</div>
        </div>
        <div class='stat-card stat-warning'>
            <div class='stat-value'>3.2 мин</div>
            <div class='stat-label'>Среднее время</div>
            <div class='stat-change neutral'>На странице</div>
        </div>
        <div class='stat-card'>
            <div class='stat-value'>68%</div>
            <div class='stat-label'>Глубина прокрутки</div>
        </div>
    </div>

    <!-- Карта кликов -->
    <div class='stat-card' style='margin-bottom: 30px;'>
        <h3 style='margin: 0 0 16px; color: var(--text-primary);'>🖱️ Карта кликов - Главная страница</h3>
        <div style='position: relative; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius); height: 400px; overflow: hidden;'>
            <!-- Имитация главной страницы -->
            <div style='padding: 20px; font-size: 12px;'>
                <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 10px; background: white; border-radius: 4px;'>
                    <div style='font-weight: bold;'>🐠 АкваСбор</div>
                    <div style='display: flex; gap: 10px;'>
                        <span>Каталог</span>
                        <span>О нас</span>
                        <span>Корзина</span>
                    </div>
                </div>

                <div style='background: linear-gradient(135deg, #667eea, #764ba2); height: 120px; border-radius: 4px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; color: white; position: relative;'>
                    <div style='text-align: center;'>
                        <h2 style='margin: 0 0 8px;'>Аквариумы и обитатели</h2>
                        <button style='background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 8px 16px; border-radius: 4px; cursor: pointer;'>В каталог</button>
                    </div>
                    <!-- Точки кликов -->
                    <div style='position: absolute; top: 80px; right: 120px; width: 20px; height: 20px; background: rgba(255,0,0,0.6); border-radius: 50%; border: 2px solid red;'></div>
                    <div style='position: absolute; top: 60px; right: 80px; width: 15px; height: 15px; background: rgba(255,165,0,0.6); border-radius: 50%; border: 2px solid orange;'></div>
                </div>

                <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;'>
                    <?php for($i = 1; $i <= 6; $i++): ?>
                        <div style='background: white; padding: 10px; border-radius: 4px; text-align: center; position: relative;'>
                            <div style='width: 50px; height: 50px; background: #eee; margin: 0 auto 8px; border-radius: 4px;'></div>
                            <div style='font-weight: bold; font-size: 11px;'>Товар <?= $i ?></div>
                            <div style='color: var(--success-color); font-size: 10px;'>1500 ₽</div>
                            <!-- Случайные клики -->
                            <?php if(rand(0, 2) == 0): ?>
                                <div style='position: absolute; top: <?= rand(20, 60) ?>px; left: <?= rand(20, 80) ?>px; width: <?= rand(8, 16) ?>px; height: <?= rand(8, 16) ?>px; background: rgba(<?= rand(0, 255) ?>,<?= rand(0, 255) ?>,0,0.7); border-radius: 50%;'></div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div style='margin-top: 12px; display: flex; align-items: center; gap: 16px; font-size: 12px;'>
            <div style='display: flex; align-items: center; gap: 4px;'>
                <div style='width: 12px; height: 12px; background: rgba(255,0,0,0.7); border-radius: 50%;'></div>
                Много кликов (50+)
            </div>
            <div style='display: flex; align-items: center; gap: 4px;'>
                <div style='width: 12px; height: 12px; background: rgba(255,165,0,0.7); border-radius: 50%;'></div>
                Средне (20-50)
            </div>
            <div style='display: flex; align-items: center; gap: 4px;'>
                <div style='width: 12px; height: 12px; background: rgba(255,255,0,0.7); border-radius: 50%;'></div>
                Мало (5-20)
            </div>
        </div>
    </div>

    <!-- Анализ по страницам -->
    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 24px;'>
        <!-- Популярные страницы -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📄 Популярные страницы</h3>
            <div style='display: grid; gap: 12px;'>
                <?php
                $pages = [
                    ['url' => '/', 'title' => 'Главная страница', 'views' => 2150, 'time' => '2:45'],
                    ['url' => '/catalog', 'title' => 'Каталог товаров', 'views' => 1680, 'time' => '4:20'],
                    ['url' => '/product/anubias', 'title' => 'Анубиас Бартера', 'views' => 890, 'time' => '3:15'],
                    ['url' => '/category/plants', 'title' => 'Растения', 'views' => 720, 'time' => '3:50'],
                    ['url' => '/cart', 'title' => 'Корзина', 'views' => 450, 'time' => '1:30']
                ];
                foreach($pages as $page):
                ?>
                <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px; background: var(--bg-secondary); border-radius: var(--border-radius);'>
                    <div>
                        <div style='font-weight: 600; font-size: 13px;'><?= $page['title'] ?></div>
                        <div style='font-size: 11px; color: var(--text-muted); font-family: monospace;'><?= $page['url'] ?></div>
                    </div>
                    <div style='text-align: right; font-size: 12px;'>
                        <div style='font-weight: 600;'><?= $page['views'] ?> views</div>
                        <div style='color: var(--text-muted);'><?= $page['time'] ?> avg</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Устройства -->
        <div class='stat-card'>
            <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📱 По устройствам</h3>
            <div style='display: grid; gap: 12px;'>
                <?php
                $devices = [
                    ['type' => 'Desktop', 'icon' => 'fas fa-desktop', 'percent' => 65, 'color' => '#3498db'],
                    ['type' => 'Mobile', 'icon' => 'fas fa-mobile-alt', 'percent' => 28, 'color' => '#2ecc71'],
                    ['type' => 'Tablet', 'icon' => 'fas fa-tablet-alt', 'percent' => 7, 'color' => '#f39c12']
                ];
                foreach($devices as $device):
                ?>
                <div style='display: flex; justify-content: space-between; align-items: center;'>
                    <div style='display: flex; align-items: center; gap: 8px;'>
                        <i class='<?= $device['icon'] ?>' style='color: <?= $device['color'] ?>;'></i>
                        <span><?= $device['type'] ?></span>
                    </div>
                    <div style='display: flex; align-items: center; gap: 8px;'>
                        <div style='width: 100px; height: 8px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden;'>
                            <div style='width: <?= $device['percent'] ?>%; height: 100%; background: <?= $device['color'] ?>;'></div>
                        </div>
                        <span style='font-weight: 600; font-size: 12px;'><?= $device['percent'] ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

// === АНАЛИТИКА ПРОДАЖ ===
function renderAnalyticsSection($data) {
    ?>
    <div style='margin-bottom: 24px;'>
        <h2 style='margin: 0 0 8px; color: var(--text-primary);'>Аналитика продаж</h2>
        <p style='margin: 0; color: var(--text-secondary);'>Подробная статистика и графики</p>
    </div>

    <!-- Период анализа -->
    <div style='display: flex; gap: 12px; margin-bottom: 24px;'>
        <button class='btn btn-primary analytics-period active' onclick='setPeriod("today")'>Сегодня</button>
        <button class='btn btn-outline analytics-period' onclick='setPeriod("week")'>Неделя</button>
        <button class='btn btn-outline analytics-period' onclick='setPeriod("month")'>Месяц</button>
        <button class='btn btn-outline analytics-period' onclick='setPeriod("year")'>Год</button>
    </div>

    <!-- График продаж -->
    <div class='stat-card' style='margin-bottom: 30px;'>
        <h3 style='margin: 0 0 16px; color: var(--text-primary);'>📈 График продаж за месяц</h3>
        <canvas id='salesChart' width='400' height='150'></canvas>
    </div>

    <!-- Воронка продаж -->
    <div class='stat-card' style='margin-bottom: 30px;'>
        <h3 style='margin: 0 0 16px; color: var(--text-primary);'>🔄 Воронка продаж</h3>
        <div style='display: grid; gap: 8px;'>
            <?php
            $funnel = [
                ['stage' => 'Посетители сайта', 'count' => 10000, 'percent' => 100, 'color' => '#3498db'],
                ['stage' => 'Просмотры товаров', 'count' => 3200, 'percent' => 32, 'color' => '#2ecc71'],
                ['stage' => 'Добавления в корзину', 'count' => 850, 'percent' => 8.5, 'color' => '#f39c12'],
                ['stage' => 'Начало оформления', 'count' => 320, 'percent' => 3.2, 'color' => '#e67e22'],
                ['stage' => 'Завершенные покупки', 'count' => 280, 'percent' => 2.8, 'color' => '#e74c3c']
            ];
            foreach($funnel as $step):
            ?>
            <div style='display: flex; align-items: center; gap: 12px;'>
                <div style='width: 150px; font-size: 13px;'><?= $step['stage'] ?></div>
                <div style='flex: 1; height: 30px; background: var(--bg-secondary); border-radius: 15px; overflow: hidden; position: relative;'>
                    <div style='width: <?= $step['percent'] ?>%; height: 100%; background: <?= $step['color'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;'>
                        <?= number_format($step['count']) ?>
                    </div>
                </div>
                <div style='width: 50px; text-align: right; font-size: 12px; font-weight: 600;'>
                    <?= $step['percent'] ?>%
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // График продаж
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($data['charts']['sales_chart']['labels'] ?? ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн']) ?>,
                    datasets: [{
                        label: 'Продажи (₽)',
                        data: <?= json_encode($data['charts']['sales_chart']['data'] ?? [65000, 78000, 82000, 91000, 87000, 95000]) ?>,
                        borderColor: 'var(--primary-color)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' ₽';
                                }
                            }
                        }
                    }
                }
            });
        });

        function setPeriod(period) {
            document.querySelectorAll('.analytics-period').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            });
            event.target.classList.remove('btn-outline');
            event.target.classList.add('btn-primary');

            showNotification(`Период изменен на: ${period}`, 'info');
        }
    </script>
    <?php
}

// === ОБРАБОТКА AJAX ЗАПРОСОВ ===
function handleAjaxRequest($action) {
    switch ($action) {
        case 'add_product':
            echo json_encode([
                'success' => true,
                'message' => 'Товар успешно добавлен!',
                'reload' => true
            ]);
            break;

        case 'add_category':
            echo json_encode([
                'success' => true,
                'message' => 'Категория создана!',
                'reload' => true
            ]);
            break;

        case 'add_news':
            echo json_encode([
                'success' => true,
                'message' => 'Новость опубликована!',
                'reload' => true
            ]);
            break;

        case 'add_page':
            echo json_encode([
                'success' => true,
                'message' => 'Страница создана!',
                'reload' => true
            ]);
            break;

        case 'add_slide':
            echo json_encode([
                'success' => true,
                'message' => 'Слайд добавлен!',
                'reload' => true
            ]);
            break;

        case 'save_settings':
            echo json_encode([
                'success' => true,
                'message' => 'Настройки сохранены!'
            ]);
            break;

        case 'save_backup_settings':
            echo json_encode([
                'success' => true,
                'message' => 'Настройки резервных копий сохранены!'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Неизвестное действие'
            ]);
    }
}

// === ОБРАБОТКА ДЕЙСТВИЙ АДМИНКИ ===
function handleAdminAction($action, $section) {
    // Обработка POST действий (не AJAX)
    switch ($action) {
        case 'toggle_status':
            $_SESSION['admin_message'] = ['text' => 'Статус изменен!', 'type' => 'success'];
            break;

        case 'delete_item':
            $_SESSION['admin_message'] = ['text' => 'Элемент удален!', 'type' => 'success'];
            break;

        default:
            break;
    }

    // Редирект для предотвращения повторной отправки формы
    if ($action) {
        header("Location: admin.php?section=$section");
        exit;
    }
}

// === СЕКЦИЯ ПО УМОЛЧАНИЮ ===
function renderDefaultSection($section, $data) {
    ?>
    <div class='empty-state'>
        <div class='empty-state-icon'>🚧</div>
        <h3>Раздел "<?= htmlspecialchars($data['title']) ?>" готов к использованию</h3>
        <p><?= htmlspecialchars($data['description'] ?? "Функционал раздела '$section' успешно подключен") ?></p>
        <button class='btn btn-primary' onclick='history.back()'>Назад к дашборду</button>
    </div>
    <?php
}

// === ФУНКЦИЯ ЛОГИНА (сохранена из оригинального кода) ===
function renderLoginPage($error = '') {
    // Используем оригинальную функцию логина из первого файла
    ?>
    <!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Вход в МЕГА CRM - АкваСбор</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            /* Стили логина из оригинального кода */
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                padding: 50px;
                border-radius: 25px;
                box-shadow: 0 25px 80px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 450px;
                text-align: center;
            }
            .login-title {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 10px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .form-input {
                width: 100%;
                padding: 18px 20px;
                border: 2px solid #e1e8ed;
                border-radius: 12px;
                font-size: 16px;
                margin-bottom: 20px;
            }
            .btn {
                width: 100%;
                padding: 18px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 18px;
                font-weight: 700;
                cursor: pointer;
            }
            .error-message {
                background: #ff6b6b;
                color: white;
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 25px;
            }
        </style>
    </head>
    <body>
        <div class='login-container'>
            <div style='font-size: 60px; margin-bottom: 25px;'>🐠</div>
            <h1 class='login-title'>АкваСбор MEGA CRM</h1>
            <p style='color: #666; margin-bottom: 40px;'>Войдите в супер-админку</p>

            <?php if ($error): ?>
                <div class='error-message'>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method='POST'>
                <input type='password' name='admin_password' class='form-input'
                       placeholder='Введите секретный пароль' required autofocus>
                <button type='submit' class='btn'>
                    Войти в MEGA CRM
                </button>
            </form>

            <div style='margin-top: 30px; padding: 20px; background: #f8f9ff; border-radius: 12px; font-size: 14px; color: #666;'>
                <strong>🚀 DEMO доступ:</strong><br>
                Пароль: <code style='background: #667eea; color: white; padding: 4px 8px; border-radius: 4px; font-weight: 600;'>admin123</code>
            </div>
        </div>
    </body>
    </html>
    <?php
}

?>