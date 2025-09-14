<?php
/**
 * АкваСбор - Главная страница v5.0
 * Полная синхронизация с админ-панелью
 */
session_start();

// Подключаем общие данные
require_once 'data.php';

$page = $_GET['page'] ?? 'home';
$action = $_POST['action'] ?? '';

// Валидация страницы
$allowedPages = ['home', 'catalog', 'categories', 'product', 'category', 'cart', 'checkout', 'about', 'contact', 'news', 'search'];
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

// Обработка действий корзины
if ($action) {
    switch ($action) {
        case 'add_to_cart':
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId > 0 && $quantity > 0) {
                $product = getProductById($productId);
                if ($product && $product['is_active'] && $product['stock'] >= $quantity) {
                    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
                    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
                    $_SESSION['message'] = [
                        'text' => "Товар «{$product['name']}» добавлен в корзину", 
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['message'] = ['text' => 'Товар недоступен', 'type' => 'error'];
                }
            }
            break;

        case 'update_cart':
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if (isset($_SESSION['cart'][$productId])) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$productId] = $quantity;
                    $_SESSION['message'] = ['text' => 'Количество обновлено', 'type' => 'success'];
                } else {
                    unset($_SESSION['cart'][$productId]);
                    $_SESSION['message'] = ['text' => 'Товар удален из корзины', 'type' => 'success'];
                }
            }
            break;

        case 'remove_from_cart':
            $productId = (int)($_POST['product_id'] ?? 0);
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $_SESSION['message'] = ['text' => 'Товар удален из корзины', 'type' => 'success'];
            }
            break;

        case 'clear_cart':
            $_SESSION['cart'] = [];
            $_SESSION['message'] = ['text' => 'Корзина очищена', 'type' => 'success'];
            break;

        case 'place_order':
            if (!empty($_SESSION['cart'])) {
                $orderId = 'AQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $_SESSION['last_order'] = $orderId;
                $_SESSION['cart'] = [];
                $_SESSION['message'] = [
                    'text' => "Заказ $orderId успешно оформлен! Мы свяжемся с вами в ближайшее время.", 
                    'type' => 'success'
                ];
            }
            break;
    }

    // Редирект после POST
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?page=' . $page));
    exit;
}

// Получаем данные для страницы
$pageData = getPageData($page);

?><!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?= htmlspecialchars($pageData['title']) ?> - <?= getSiteSettings()['site_name'] ?></title>
    <meta name='description' content='<?= htmlspecialchars($pageData['description']) ?>'>
    <meta name='keywords' content='<?= htmlspecialchars($pageData['keywords'] ?? getSiteSettings()['site_keywords']) ?>'>

    <!-- Стили -->
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>

    <!-- Фавикон -->
    <link rel='icon' href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">🐠</text></svg>'>

    <!-- SEO и социальные сети -->
    <meta property='og:title' content='<?= htmlspecialchars($pageData['title']) ?> - <?= getSiteSettings()['site_name'] ?>'>
    <meta property='og:description' content='<?= htmlspecialchars($pageData['description']) ?>'>
    <meta property='og:type' content='<?= $page === 'product' ? 'product' : 'website' ?>'>
    <meta property='og:site_name' content='<?= getSiteSettings()['site_name'] ?>'>

    <style>
        /* Современные CSS переменные */
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --success-color: #2ecc71;
            --info-color: #3498db;

            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --text-muted: #95a5a6;

            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-dark: #2c3e50;

            --border-color: #e9ecef;
            --border-radius: 8px;
            --border-radius-lg: 12px;

            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);

            --container-width: 1200px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
        }

        .container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Кнопки */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        .btn-lg {
            padding: 16px 32px;
            font-size: 16px;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* Заголовки */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            margin-bottom: 0.5em;
            color: var(--text-primary);
        }

        h1 { font-size: 2.5em; }
        h2 { font-size: 2em; }
        h3 { font-size: 1.5em; }

        /* Шапка сайта */
        .header {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
        }

        /* Логотип */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 40px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .logo-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .logo-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Навигация */
        .nav {
            display: flex;
            gap: 32px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            position: relative;
            transition: var(--transition);
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -16px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        /* Действия в шапке */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-form {
            position: relative;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            transition: var(--transition);
        }

        .search-input-group:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        .search-input {
            border: none;
            outline: none;
            padding: 12px 16px;
            background: transparent;
            min-width: 200px;
        }

        .search-btn {
            padding: 12px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-btn:hover {
            background: var(--secondary-color);
        }

        /* Корзина */
        .cart-widget {
            position: relative;
        }

        .cart-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .cart-link:hover {
            background: var(--bg-secondary);
        }

        .cart-icon {
            position: relative;
            font-size: 20px;
            color: var(--primary-color);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
        }

        .cart-text {
            font-weight: 500;
            font-size: 14px;
        }

        .cart-total {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Мобильное меню */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-primary);
            cursor: pointer;
        }

        /* Уведомления */
        .alert {
            padding: 16px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(90deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background: linear-gradient(90deg, rgba(231, 76, 60, 0.1), rgba(231, 76, 60, 0.05));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Главный баннер */
        .hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.15)"/><circle cx="40" cy="80" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float-bg 20s linear infinite;
        }

        @keyframes float-bg {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5em;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .text-accent {
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2em;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Преимущества */
        .features {
            padding: 80px 0;
            background: var(--bg-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .feature-card {
            text-align: center;
            padding: 40px 20px;
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 16px;
        }

        /* Секции */
        .section {
            padding: 80px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.5em;
            color: var(--text-primary);
        }

        .section-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .section-link:hover {
            gap: 12px;
        }

        /* Сетка товаров */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, var(--bg-secondary), #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: var(--text-muted);
            position: relative;
            overflow: hidden;
        }

        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .product-badge.sale {
            background: var(--danger-color);
        }

        .product-badge.new {
            background: var(--info-color);
        }

        .product-info {
            padding: 24px;
        }

        .product-name {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .product-description {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .current-price {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--primary-color);
        }

        .old-price {
            font-size: 1.1em;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .add-to-cart-form {
            margin: 0;
        }

        .btn-disabled {
            background: var(--text-muted);
            cursor: not-allowed;
        }

        /* Подвал */
        .footer {
            background: var(--bg-dark);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            color: white;
            margin-bottom: 20px;
        }

        .footer-description {
            line-height: 1.6;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 8px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 50%;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .contact-info {
            display: grid;
            gap: 16px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .contact-item i {
            margin-top: 4px;
            color: var(--primary-color);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .copyright {
            opacity: 0.7;
        }

        .footer-bottom .footer-links {
            display: flex;
            gap: 20px;
        }

        /* Кнопка "Наверх" */
        .scroll-top-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
        }

        .scroll-top-btn.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .scroll-top-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-5px);
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .header-content {
                flex-wrap: wrap;
                gap: 16px;
            }

            .nav {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-title {
                font-size: 2.5em;
            }

            .search-input {
                min-width: 150px;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
            }

            .section-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }

            .hero {
                padding: 60px 0;
            }

            .hero-title {
                font-size: 2em;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Страницы каталога */
        .page-header {
            background: var(--bg-secondary);
            padding: 40px 0;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5em;
            margin-bottom: 16px;
        }

        /* Фильтры */
        .filters-bar {
            background: var(--bg-primary);
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 40px;
        }

        .filters-content {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-primary);
        }

        /* Пустые состояния */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }

        .empty-state h3 {
            margin-bottom: 16px;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
        }

        /* Корзина */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .cart-items {
            display: grid;
            gap: 20px;
            margin-bottom: 40px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
        }

        .cart-item-image {
            width: 80px;
            height: 80px;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            color: var(--text-muted);
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-info h3 {
            margin-bottom: 8px;
        }

        .cart-item-price {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--primary-color);
        }

        .cart-summary {
            text-align: center;
            padding: 40px;
            background: var(--bg-secondary);
            border-radius: var(--border-radius-lg);
        }

        .cart-summary h3 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        /* Анимации */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class='<?= $page ?>-page'>
    <!-- Шапка -->
    <header class='header' id='header'>
        <div class='container'>
            <div class='header-content'>
                <!-- Логотип -->
                <div class='logo'>
                    <a href='?page=home' class='logo' style='text-decoration: none;'>
                        <div class='logo-icon'>🐠</div>
                        <div class='logo-text'>
                            <div class='logo-title'><?= getSiteSettings()['site_name'] ?></div>
                            <div class='logo-subtitle'><?= getSiteSettings()['site_description'] ?></div>
                        </div>
                    </a>
                </div>

                <!-- Навигация -->
                <nav class='nav' id='mainNav'>
                    <a href='?page=home' class='nav-link <?= $page === 'home' ? 'active' : '' ?>'>
                        <i class='fas fa-home'></i>
                        <span>Главная</span>
                    </a>
                    <a href='?page=catalog' class='nav-link <?= $page === 'catalog' ? 'active' : '' ?>'>
                        <i class='fas fa-fish'></i>
                        <span>Каталог</span>
                    </a>
                    <a href='?page=categories' class='nav-link <?= $page === 'categories' ? 'active' : '' ?>'>
                        <i class='fas fa-th-large'></i>
                        <span>Категории</span>
                    </a>
                    <a href='?page=news' class='nav-link <?= $page === 'news' ? 'active' : '' ?>'>
                        <i class='fas fa-newspaper'></i>
                        <span>Новости</span>
                    </a>
                    <a href='?page=about' class='nav-link <?= $page === 'about' ? 'active' : '' ?>'>
                        <i class='fas fa-info-circle'></i>
                        <span>О нас</span>
                    </a>
                    <a href='?page=contact' class='nav-link <?= $page === 'contact' ? 'active' : '' ?>'>
                        <i class='fas fa-envelope'></i>
                        <span>Контакты</span>
                    </a>
                </nav>

                <!-- Действия -->
                <div class='header-actions'>
                    <!-- Поиск -->
                    <div class='search-widget'>
                        <form method='GET' class='search-form'>
                            <input type='hidden' name='page' value='search'>
                            <div class='search-input-group'>
                                <input type='text' name='q' placeholder='Поиск товаров...'
                                       value='<?= htmlspecialchars($_GET['q'] ?? '') ?>' class='search-input'>
                                <button type='submit' class='search-btn'>
                                    <i class='fas fa-search'></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Корзина -->
                    <div class='cart-widget'>
                        <a href='?page=cart' class='cart-link'>
                            <div class='cart-icon'>
                                <i class='fas fa-shopping-cart'></i>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <span class='cart-count'><?= array_sum($_SESSION['cart']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class='cart-info'>
                                <div class='cart-text'>Корзина</div>
                                <div class='cart-total'>
                                    <?php if (!empty($_SESSION['cart'])): ?>
                                        <?= getCartTotal() ?> <?= getSiteSettings()['currency'] ?>
                                    <?php else: ?>
                                        Пуста
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Мобильное меню -->
                    <button class='mobile-menu-btn' onclick='toggleMobileMenu()'>
                        <i class='fas fa-bars'></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Основной контент -->
    <main class='main-content'>
        <?php if (isset($_SESSION['message'])): ?>
            <div class='alert alert-<?= htmlspecialchars($_SESSION['message']['type']) ?>'>
                <div class='container'>
                    <i class='fas fa-<?= $_SESSION['message']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>'></i>
                    <?= htmlspecialchars($_SESSION['message']['text']) ?>
                </div>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php renderPage($page, $pageData); ?>
    </main>

    <!-- Подвал -->
    <footer class='footer'>
        <div class='container'>
            <div class='footer-content'>
                <!-- О магазине -->
                <div class='footer-section'>
                    <div style='display: flex; align-items: center; gap: 12px; margin-bottom: 16px;'>
                        <div class='logo-icon'>🐠</div>
                        <div class='logo-text'>
                            <div class='logo-title'><?= getSiteSettings()['site_name'] ?></div>
                            <div class='logo-subtitle'><?= getSiteSettings()['site_description'] ?></div>
                        </div>
                    </div>
                    <p class='footer-description'>
                        🍀 Доставка по всей РОССИИ, БЕЛАРУСИ, КАЗАХСТАНУ, КИРГИЗИИ, АРМЕНИИ ✅<br>
                        Общение об аквариумах, аквариумных рыбках, растениях и оборудовании.
                    </p>
                    <div class='social-links'>
                        <?php 
                        $social = getSiteSettings();
                        $socialLinks = [
                            ['icon' => 'fab fa-vk', 'url' => $social['social_vk'] ?? '#', 'title' => 'ВКонтакте'],
                            ['icon' => 'fab fa-telegram', 'url' => $social['social_telegram'] ?? '#', 'title' => 'Telegram'],
                            ['icon' => 'fab fa-instagram', 'url' => $social['social_instagram'] ?? '#', 'title' => 'Instagram'],
                            ['icon' => 'fab fa-youtube', 'url' => $social['social_youtube'] ?? '#', 'title' => 'YouTube']
                        ];
                        foreach ($socialLinks as $link):
                        ?>
                            <a href='<?= $link['url'] ?>' class='social-link' title='<?= $link['title'] ?>' target='_blank'>
                                <i class='<?= $link['icon'] ?>'></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Каталог -->
                <div class='footer-section'>
                    <h3>Каталог</h3>
                    <ul class='footer-links'>
                        <?php foreach (getCategories() as $category): ?>
                            <li><a href='?page=catalog&category=<?= $category['id'] ?>'><?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Информация -->
                <div class='footer-section'>
                    <h3>Информация</h3>
                    <ul class='footer-links'>
                        <li><a href='?page=about'>О нас</a></li>
                        <li><a href='?page=delivery'>Доставка и оплата</a></li>
                        <li><a href='?page=returns'>Возврат товаров</a></li>
                        <li><a href='?page=warranty'>Гарантия</a></li>
                        <li><a href='?page=faq'>Вопросы и ответы</a></li>
                        <li><a href='?page=news'>Новости</a></li>
                    </ul>
                </div>

                <!-- Контакты -->
                <div class='footer-section'>
                    <h3>Контакты</h3>
                    <div class='contact-info'>
                        <div class='contact-item'>
                            <i class='fas fa-phone'></i>
                            <div>
                                <strong><?= getSiteSettings()['phone'] ?></strong>
                                <small><?= getSiteSettings()['working_hours'] ?></small>
                            </div>
                        </div>
                        <div class='contact-item'>
                            <i class='fas fa-envelope'></i>
                            <div>
                                <strong><?= getSiteSettings()['admin_email'] ?></strong>
                                <small>Ответим в течение часа</small>
                            </div>
                        </div>
                        <div class='contact-item'>
                            <i class='fas fa-map-marker-alt'></i>
                            <div>
                                <strong><?= getSiteSettings()['address'] ?></strong>
                                <small>Работаем по всему СНГ</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class='footer-bottom'>
                <div class='footer-bottom-content'>
                    <div class='copyright'>
                        © <?= date('Y') ?> <?= getSiteSettings()['site_name'] ?>. Все права защищены.
                    </div>
                    <div class='footer-links'>
                        <a href='?page=privacy'>Политика конфиденциальности</a>
                        <a href='?page=terms'>Пользовательское соглашение</a>
                        <a href='admin.php' target='_blank' style='color: rgba(255,255,255,0.5);'>Админ-панель</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Кнопка 'Наверх' -->
    <button class='scroll-top-btn' id='scrollTopBtn' onclick='scrollToTop()'>
        <i class='fas fa-arrow-up'></i>
    </button>

    <!-- Скрипты -->
    <script>
        // Плавная прокрутка наверх
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Показ кнопки "Наверх"
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('scrollTopBtn');
            if (window.pageYOffset > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        });

        // Мобильное меню
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('mobile-open');
        }

        // Анимации при появлении
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Наблюдаем за карточками товаров
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.product-card, .feature-card').forEach(card => {
                observer.observe(card);
            });
        });

        // Обновление количества в корзине
        function updateCartQuantity(productId, change) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_cart">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="${change}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <?php if (!empty(getSiteSettings()['google_analytics'])): ?>
        <!-- Google Analytics -->
        <script async src='https://www.googletagmanager.com/gtag/js?id=<?= getSiteSettings()['google_analytics'] ?>'></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= getSiteSettings()['google_analytics'] ?>');
        </script>
    <?php endif; ?>

    <?php if (!empty(getSiteSettings()['yandex_metrika'])): ?>
        <!-- Яндекс.Метрика -->
        <script type="text/javascript">
            (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym(<?= getSiteSettings()['yandex_metrika'] ?>, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true });
        </script>
    <?php endif; ?>
</body>
</html>

<?php

// === ФУНКЦИИ РЕНДЕРИНГА СТРАНИЦ ===

function renderPage($page, $pageData) {
    switch ($page) {
        case 'home':
            renderHomePage($pageData);
            break;
        case 'catalog':
            renderCatalogPage($pageData);
            break;
        case 'categories':
            renderCategoriesPage($pageData);
            break;
        case 'product':
            renderProductPage($pageData);
            break;
        case 'category':
            renderCategoryPage($pageData);
            break;
        case 'cart':
            renderCartPage($pageData);
            break;
        case 'checkout':
            renderCheckoutPage($pageData);
            break;
        case 'news':
            renderNewsPage($pageData);
            break;
        case 'search':
            renderSearchPage($pageData);
            break;
        default:
            renderStaticPage($page, $pageData);
    }
}

// === ГЛАВНАЯ СТРАНИЦА ===
function renderHomePage($pageData) {
    $featured = getFeaturedProducts(9);
    $newProducts = getNewProducts(6);
    ?>
    <!-- Главный экран -->
    <section class='hero'>
        <div class='container'>
            <div class='hero-content'>
                <h1 class='hero-title'>
                    <?= getSiteSettings()['site_name'] ?> - <span class='text-accent'>аквариумы и их обитатели</span>
                </h1>
                <p class='hero-subtitle'>
                    🍀 Доставка по всей РОССИИ, БЕЛАРУСИ, КАЗАХСТАНУ, КИРГИЗИИ, АРМЕНИИ ✅<br>
                    Общение об аквариумах, аквариумных рыбках, растениях и оборудовании
                </p>
                <div class='hero-actions'>
                    <a href='?page=catalog' class='btn btn-primary btn-lg'>
                        <i class='fas fa-fish'></i>
                        Смотреть каталог
                    </a>
                    <a href='?page=categories' class='btn btn-outline btn-lg'>
                        <i class='fas fa-th-large'></i>
                        Категории
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section class='features'>
        <div class='container'>
            <div class='features-grid'>
                <div class='feature-card'>
                    <div class='feature-icon'>🚚</div>
                    <h3>Доставка по СНГ</h3>
                    <p>Быстрая и надежная доставка в Россию, Беларусь, Казахстан, Киргизию, Армению</p>
                </div>
                <div class='feature-card'>
                    <div class='feature-icon'>🐠</div>
                    <h3>Живые обитатели</h3>
                    <p>Здоровые рыбки и растения с гарантией качества</p>
                </div>
                <div class='feature-card'>
                    <div class='feature-icon'>👥</div>
                    <h3>Сообщество</h3>
                    <p>Общение и обмен опытом между аквариумистами</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Популярные товары -->
    <?php if (!empty($featured)): ?>
    <section class='section'>
        <div class='container'>
            <div class='section-header'>
                <h2 class='section-title'>
                    🔥 Популярные товары
                </h2>
                <a href='?page=catalog&filter=featured' class='section-link'>
                    Все популярные <i class='fas fa-arrow-right'></i>
                </a>
            </div>

            <div class='products-grid'>
                <?php foreach ($featured as $product): ?>
                    <?= renderProductCard($product) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Новые товары -->
    <?php if (!empty($newProducts)): ?>
    <section class='section' style='background: var(--bg-secondary);'>
        <div class='container'>
            <div class='section-header'>
                <h2 class='section-title'>
                    ✨ Новинки
                </h2>
                <a href='?page=catalog&filter=new' class='section-link'>
                    Все новинки <i class='fas fa-arrow-right'></i>
                </a>
            </div>

            <div class='products-grid'>
                <?php foreach ($newProducts as $product): ?>
                    <?= renderProductCard($product) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Категории -->
    <section class='section'>
        <div class='container'>
            <div class='section-header'>
                <h2 class='section-title'>
                    📂 Категории товаров
                </h2>
                <a href='?page=categories' class='section-link'>
                    Все категории <i class='fas fa-arrow-right'></i>
                </a>
            </div>

            <div class='features-grid'>
                <?php foreach (array_slice(getCategories(), 0, 6) as $category): ?>
                    <div class='feature-card' style='cursor: pointer;' onclick='location.href="?page=catalog&category=<?= $category['id'] ?>"'>
                        <div class='feature-icon'><?= $category['icon'] ?></div>
                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                        <p><?= htmlspecialchars($category['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

// === КАТАЛОГ ===
function renderCatalogPage($pageData) {
    $search = $_GET['search'] ?? $_GET['q'] ?? '';
    $categoryId = (int)($_GET['category'] ?? 0);
    $filter = $_GET['filter'] ?? '';

    // Получаем товары
    $products = getProducts();

    // Фильтрация
    if ($search) {
        $products = searchProducts($search);
    }

    if ($categoryId) {
        $products = getProductsByCategory($categoryId);
    }

    if ($filter) {
        switch ($filter) {
            case 'featured':
                $products = array_filter($products, fn($p) => $p['is_featured']);
                break;
            case 'new':
                $products = array_filter($products, fn($p) => $p['is_new']);
                break;
            case 'sale':
                $products = array_filter($products, fn($p) => $p['old_price'] > $p['price']);
                break;
        }
    }

    // Только активные товары
    $products = array_filter($products, fn($p) => $p['is_active']);

    $category = $categoryId ? getCategoryById($categoryId) : null;
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1>
                <?php if ($search): ?>
                    Поиск: "<?= htmlspecialchars($search) ?>"
                <?php elseif ($category): ?>
                    <?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?>
                <?php elseif ($filter): ?>
                    <?php
                    $filterNames = [
                        'featured' => '🔥 Популярные товары',
                        'new' => '✨ Новинки',
                        'sale' => '🏷️ Скидки'
                    ];
                    echo $filterNames[$filter] ?? 'Каталог';
                    ?>
                <?php else: ?>
                    Каталог товаров
                <?php endif; ?>
            </h1>
            <?php if ($search): ?>
                <p>Найдено товаров: <?= count($products) ?></p>
            <?php elseif ($category): ?>
                <p><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Фильтры -->
    <div class='filters-bar'>
        <div class='container'>
            <div class='filters-content'>
                <div class='filter-group'>
                    <label>Категория:</label>
                    <select class='filter-select' onchange='filterByCategory(this.value)'>
                        <option value=''>Все категории</option>
                        <?php foreach (getCategories() as $cat): ?>
                            <option value='<?= $cat['id'] ?>' <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class='filter-group'>
                    <label>Сортировка:</label>
                    <select class='filter-select' onchange='sortProducts(this.value)'>
                        <option value='default'>По умолчанию</option>
                        <option value='price_asc'>Цена: по возрастанию</option>
                        <option value='price_desc'>Цена: по убыванию</option>
                        <option value='name'>По названию</option>
                        <option value='new'>Сначала новые</option>
                    </select>
                </div>

                <div class='filter-group'>
                    <button class='btn btn-outline btn-sm' onclick='showOnlyDiscounted()'>
                        <i class='fas fa-tags'></i> Только со скидкой
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <?php if (!empty($products)): ?>
            <div class='products-grid' id='productsGrid'>
                <?php foreach ($products as $product): ?>
                    <?= renderProductCard($product) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty-state'>
                <div class='empty-icon'>🔍</div>
                <h3>Ничего не найдено</h3>
                <p>
                    <?php if ($search): ?>
                        По запросу "<?= htmlspecialchars($search) ?>" товары не найдены
                    <?php else: ?>
                        В данной категории пока нет товаров
                    <?php endif; ?>
                </p>
                <a href='?page=catalog' class='btn btn-primary'>Весь каталог</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterByCategory(categoryId) {
            const url = new URL(window.location);
            if (categoryId) {
                url.searchParams.set('category', categoryId);
            } else {
                url.searchParams.delete('category');
            }
            window.location = url.toString();
        }

        function sortProducts(sort) {
            // Простая сортировка на клиенте
            const grid = document.getElementById('productsGrid');
            const products = Array.from(grid.children);

            products.sort((a, b) => {
                switch(sort) {
                    case 'price_asc':
                        return getPrice(a) - getPrice(b);
                    case 'price_desc':
                        return getPrice(b) - getPrice(a);
                    case 'name':
                        return getName(a).localeCompare(getName(b));
                    default:
                        return 0;
                }
            });

            products.forEach(product => grid.appendChild(product));
        }

        function getPrice(element) {
            const priceText = element.querySelector('.current-price').textContent;
            return parseInt(priceText.replace(/[^\d]/g, ''));
        }

        function getName(element) {
            return element.querySelector('.product-name').textContent;
        }

        function showOnlyDiscounted() {
            const products = document.querySelectorAll('.product-card');
            products.forEach(product => {
                const hasDiscount = product.querySelector('.product-badge.sale');
                product.style.display = hasDiscount ? 'block' : 'none';
            });
        }
    </script>
    <?php
}

// === КАРТОЧКА ТОВАРА ===
function renderProductCard($product) {
    if (!$product['is_active']) return '';

    $hasDiscount = $product['old_price'] && $product['old_price'] > $product['price'];
    $isNew = $product['is_new'];
    $outOfStock = $product['stock'] <= 0;

    ob_start();
    ?>
    <div class='product-card' data-id='<?= $product['id'] ?>'>
        <?php if ($hasDiscount): ?>
            <div class='product-badge sale'>
                -<?= round((1 - $product['price'] / $product['old_price']) * 100) ?>%
            </div>
        <?php elseif ($isNew): ?>
            <div class='product-badge new'>Новинка</div>
        <?php endif; ?>

        <div class='product-image' onclick='viewProduct(<?= $product['id'] ?>)' style='cursor: pointer;'>
            <?php if (!empty($product['images'])): ?>
                <img src='<?= htmlspecialchars($product['images'][0]) ?>' alt='<?= htmlspecialchars($product['name']) ?>'>
            <?php else: ?>
                <div style='font-size: 3em; color: var(--primary-color);'>
                    <?php
                    $icons = ['🐠', '🌱', '🏠', '⚙️', '🍽️', '🗿', '🏔️', '🧪'];
                    echo $icons[($product['id'] - 1) % count($icons)];
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class='product-info'>
            <h3 class='product-name' onclick='viewProduct(<?= $product['id'] ?>)' style='cursor: pointer;'>
                <?= htmlspecialchars($product['name']) ?>
            </h3>

            <p class='product-description'>
                <?= htmlspecialchars($product['short_description']) ?>
            </p>

            <div class='product-price'>
                <?php if ($hasDiscount): ?>
                    <span class='old-price'><?= number_format($product['old_price'], 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?></span>
                <?php endif; ?>
                <span class='current-price'><?= number_format($product['price'], 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?></span>
            </div>

            <?php if (!$outOfStock): ?>
                <form method='POST' class='add-to-cart-form'>
                    <input type='hidden' name='action' value='add_to_cart'>
                    <input type='hidden' name='product_id' value='<?= $product['id'] ?>'>
                    <input type='hidden' name='quantity' value='1'>
                    <button type='submit' class='btn btn-success btn-block'>
                        <i class='fas fa-cart-plus'></i>
                        В корзину
                    </button>
                </form>
            <?php else: ?>
                <button class='btn btn-disabled btn-block' disabled>
                    <i class='fas fa-times'></i>
                    Нет в наличии
                </button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewProduct(id) {
            window.location.href = `?page=product&id=${id}`;
        }
    </script>
    <?php
    return ob_get_clean();
}

// === КОРЗИНА ===
function renderCartPage($pageData) {
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1>Корзина покупок</h1>
            <?php if (!empty($_SESSION['cart'])): ?>
                <p>Товаров в корзине: <?= array_sum($_SESSION['cart']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <?php if (empty($_SESSION['cart'])): ?>
            <div class='empty-cart'>
                <div class='empty-icon'>🛒</div>
                <h2>Корзина пуста</h2>
                <p>Добавьте товары из каталога, чтобы сделать заказ</p>
                <a href='?page=catalog' class='btn btn-primary btn-lg'>
                    <i class='fas fa-fish'></i>
                    Перейти в каталог
                </a>
            </div>
        <?php else: ?>
            <div class='cart-items'>
                <?php 
                $total = 0;
                foreach ($_SESSION['cart'] as $productId => $quantity):
                    $product = getProductById($productId);
                    if (!$product) continue;
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                ?>
                    <div class='cart-item'>
                        <div class='cart-item-image'>
                            <?php if (!empty($product['images'])): ?>
                                <img src='<?= htmlspecialchars($product['images'][0]) ?>' alt='<?= htmlspecialchars($product['name']) ?>'>
                            <?php else: ?>
                                <div style='font-size: 2em; color: var(--primary-color);'>🐠</div>
                            <?php endif; ?>
                        </div>
                        <div class='cart-item-info'>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['short_description']) ?></p>
                            <div class='cart-item-quantity' style='margin-top: 12px;'>
                                <div style='display: flex; align-items: center; gap: 12px;'>
                                    <span>Количество:</span>
                                    <div style='display: flex; align-items: center; gap: 8px;'>
                                        <button class='btn btn-outline btn-sm' onclick='updateQuantity(<?= $product['id'] ?>, <?= $quantity - 1 ?>)'>
                                            <i class='fas fa-minus'></i>
                                        </button>
                                        <span style='min-width: 30px; text-align: center; font-weight: 600;'><?= $quantity ?></span>
                                        <button class='btn btn-outline btn-sm' onclick='updateQuantity(<?= $product['id'] ?>, <?= $quantity + 1 ?>)'>
                                            <i class='fas fa-plus'></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cart-item-price'>
                            <div style='font-size: 1.5em; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;'>
                                <?= number_format($subtotal, 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?>
                            </div>
                            <div style='font-size: 0.9em; color: var(--text-muted);'>
                                <?= number_format($product['price'], 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?> × <?= $quantity ?>
                            </div>
                        </div>
                        <div class='cart-item-actions'>
                            <form method='POST' style='display: inline;'>
                                <input type='hidden' name='action' value='remove_from_cart'>
                                <input type='hidden' name='product_id' value='<?= $product['id'] ?>'>
                                <button type='submit' class='btn btn-danger btn-sm' onclick='return confirm("Удалить товар из корзины?")'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class='cart-summary'>
                <h3>Итого: <?= number_format($total, 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?></h3>

                <?php if ($total < getSiteSettings()['min_order_amount']): ?>
                    <p style='color: var(--warning-color); margin: 16px 0;'>
                        <i class='fas fa-info-circle'></i>
                        Минимальная сумма заказа: <?= number_format(getSiteSettings()['min_order_amount'], 0, '', ' ') ?> <?= getSiteSettings()['currency'] ?>
                    </p>
                <?php endif; ?>

                <?php if ($total >= getSiteSettings()['free_shipping_from']): ?>
                    <p style='color: var(--success-color); margin: 16px 0;'>
                        <i class='fas fa-truck'></i>
                        Бесплатная доставка!
                    </p>
                <?php endif; ?>

                <div style='display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 24px;'>
                    <a href='?page=catalog' class='btn btn-outline btn-lg'>
                        <i class='fas fa-arrow-left'></i>
                        Продолжить покупки
                    </a>

                    <?php if ($total >= getSiteSettings()['min_order_amount']): ?>
                        <a href='?page=checkout' class='btn btn-success btn-lg'>
                            <i class='fas fa-credit-card'></i>
                            Оформить заказ
                        </a>
                    <?php endif; ?>

                    <form method='POST' style='display: inline;'>
                        <input type='hidden' name='action' value='clear_cart'>
                        <button type='submit' class='btn btn-outline btn-lg' 
                                onclick='return confirm("Очистить корзину?")'>
                            <i class='fas fa-trash'></i>
                            Очистить корзину
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(productId, quantity) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_cart">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="${quantity}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <?php
}

// === ОСТАЛЬНЫЕ СТРАНИЦЫ ===
function renderCategoriesPage($pageData) {
    $categories = getCategories();
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1>Категории товаров</h1>
            <p>Выберите категорию для просмотра товаров</p>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <div class='features-grid'>
            <?php foreach ($categories as $category): ?>
                <div class='feature-card' style='cursor: pointer;' onclick='location.href="?page=catalog&category=<?= $category['id'] ?>"'>
                    <div class='feature-icon'><?= $category['icon'] ?></div>
                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                    <p><?= htmlspecialchars($category['description']) ?></p>
                    <div style='margin-top: 16px; color: var(--primary-color); font-weight: 600;'>
                        <?= count(getProductsByCategory($category['id'])) ?> товаров
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderNewsPage($pageData) {
    $news = array_filter(getNews(), fn($n) => $n['is_published']);
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1>Новости и статьи</h1>
            <p>Последние новости из мира аквариумистики</p>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <div style='display: grid; gap: 30px;'>
            <?php foreach ($news as $item): ?>
                <article class='feature-card' style='text-align: left; cursor: pointer;'>
                    <div style='display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;'>
                        <h2><?= htmlspecialchars($item['title']) ?></h2>
                        <time style='color: var(--text-muted); white-space: nowrap; margin-left: 20px;'>
                            <?= date('d.m.Y', strtotime($item['created_at'])) ?>
                        </time>
                    </div>
                    <p><?= htmlspecialchars($item['excerpt']) ?></p>
                    <div style='margin-top: 16px;'>
                        <span class='btn btn-outline btn-sm'>Читать далее</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderCheckoutPage($pageData) {
    if (empty($_SESSION['cart'])) {
        header('Location: ?page=cart');
        exit;
    }
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1>Оформление заказа</h1>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <form method='POST' style='max-width: 600px; margin: 0 auto;'>
            <input type='hidden' name='action' value='place_order'>

            <div class='feature-card' style='margin-bottom: 30px;'>
                <h3>Контактная информация</h3>
                <div style='display: grid; gap: 20px; margin-top: 20px;'>
                    <input type='text' name='name' placeholder='Ваше имя' required style='padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <input type='tel' name='phone' placeholder='Телефон' required style='padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <input type='email' name='email' placeholder='Email' required style='padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);'>
                    <textarea name='address' placeholder='Адрес доставки' required style='padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);' rows='3'></textarea>
                    <textarea name='notes' placeholder='Комментарий к заказу (необязательно)' style='padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius);' rows='2'></textarea>
                </div>
            </div>

            <div class='feature-card' style='margin-bottom: 30px;'>
                <h3>Итого к оплате: <?= getCartTotal() ?> <?= getSiteSettings()['currency'] ?></h3>
                <p style='margin-top: 16px;'>После оформления заказа с вами свяжется наш менеджер для подтверждения.</p>
            </div>

            <div style='text-align: center;'>
                <button type='submit' class='btn btn-success btn-lg'>
                    <i class='fas fa-check'></i>
                    Оформить заказ
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderStaticPage($page, $pageData) {
    ?>
    <div class='page-header'>
        <div class='container'>
            <h1><?= htmlspecialchars($pageData['title']) ?></h1>
        </div>
    </div>

    <div class='container' style='padding: 40px 20px;'>
        <div class='feature-card' style='text-align: center;'>
            <div style='font-size: 4em; margin-bottom: 20px;'>📄</div>
            <h3>Страница "<?= htmlspecialchars($pageData['title']) ?>" в разработке</h3>
            <p style='margin: 20px 0;'>Скоро здесь будет полезная информация</p>
            <a href='?page=home' class='btn btn-primary'>На главную</a>
        </div>
    </div>
    <?php
}

// === ДАННЫЕ СТРАНИЦ ===
function getPageData($page) {
    $titles = [
        'home' => 'Главная',
        'catalog' => 'Каталог товаров',
        'categories' => 'Категории',
        'product' => 'Товар',
        'category' => 'Категория',
        'cart' => 'Корзина',
        'checkout' => 'Оформление заказа',
        'news' => 'Новости',
        'search' => 'Поиск',
        'about' => 'О нас',
        'contact' => 'Контакты'
    ];

    $descriptions = [
        'home' => getSiteSettings()['site_description'],
        'catalog' => 'Большой выбор товаров для аквариума: рыбки, растения, оборудование',
        'categories' => 'Все категории товаров для аквариума',
        'cart' => 'Корзина покупок',
        'news' => 'Новости и статьи об аквариумистике'
    ];

    return [
        'title' => $titles[$page] ?? ucfirst($page),
        'description' => $descriptions[$page] ?? getSiteSettings()['site_description'],
        'keywords' => getSiteSettings()['site_keywords'] ?? 'аквариум, рыбки, растения'
    ];
}

// === КОРЗИНА ===
function getCartTotal() {
    if (empty($_SESSION['cart'])) return '0';

    $total = 0;
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $product = getProductById($productId);
        if ($product) {
            $total += $product['price'] * $quantity;
        }
    }

    return number_format($total, 0, '', ' ');
}

?>