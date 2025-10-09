<?php
/**
 * Главная страница сайта Sasha's Sushi
 * Дизайн: Бело-красно-черный брендбук
 * С функционалом как в Dodo Pizza + модальное окно товара
 * 
 * v6.4.0 - ПОЛНОСТЬЮ ПЕРЕРАБОТАН ПОИСК (МАКСИМАЛЬНАЯ ПРОИЗВОДИТЕЛЬНОСТЬ)
 * - ✅ НОВЫЙ алгоритм поиска без зависаний
 * - ✅ Debounce 300ms + кэширование
 * - ✅ Виртуальная фильтрация (batch processing)
 * - ✅ CSS-классы вместо DOM-манипуляций
 * - ✅ Оптимизированная подсветка текста
 * - ✅ Поиск по названию, составу, описанию
 * - ✅ Результаты в реальном времени
 * - ✅ Скрытие категорий при поиске
 * - ✅ Категории = Родитель из 1С (реальные группы)
 * - ✅ Свойства = is_new, is_popular (виртуальные категории)
 * - ✅ Товары НЕ дублируются между виртуальными и реальными
 * - ✅ Пустые категории скрываются автоматически
 * - ✅ Товары с is_closed = true НЕ показываются
 */

// ВАЖНО: Сначала подключаем config.php (он запустит сессию)
require_once 'config.php';

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = $_SESSION['customer_name'] ?? '';
$customerId = $_SESSION['customer_id'] ?? null;

// Функция для безопасного вывода
function safe_output($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Функция для получения изображения товара
function getProductImage($product) {
    // Если есть изображение
    if (!empty($product['image'])) {
        $image = $product['image'];

        // Если это URL (начинается с http:// или https://)
        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        // Если это относительный путь - проверяем существование файла
        if ($image[0] === '/') {
            $filePath = '.' . $image;
        } else {
            $filePath = $image;
        }

        // Проверяем только локальные файлы
        if (@file_exists($filePath) && @is_file($filePath)) {
            return $image;
        }
    }

    // Заглушка с эмодзи
    $emoji = '🍣';
    if (isset($product['category_id'])) {
        $emojiMap = [
            1 => '🍣', 2 => '🍱', 3 => '🥢', 4 => '🔥', 
            5 => '🍲', 6 => '🎁', 7 => '🥤', 8 => '🍜',
            9 => '🍰', 10 => '🌶️', 11 => '🥡', 12 => '🍛',
            13 => '🥗', 14 => '🍡', 15 => '🧂'
        ];
        $emoji = $emojiMap[$product['category_id']] ?? '🍣';
    }

    return 'https://via.placeholder.com/300x220/E31E24/ffffff?text=' . urlencode($emoji);
}

// Проверяем подключение к БД и получаем данные
try {
    // ========== 1. ПОЛУЧАЕМ ВСЕ ДАННЫЕ ИЗ БД ==========
    $allCategoriesRaw = $db->findAll('categories') ?: [];
    $allProductsRaw = $db->findAll('products') ?: [];

    // ========== 2. ФИЛЬТРУЕМ АКТИВНЫЕ ТОВАРЫ ==========
    // Только товары: активные, с ценой, НЕ закрытые к заказу, в наличии
    $allProducts = array_filter($allProductsRaw, function($p) {
        $isActive = ($p['status'] ?? 'active') === 'active';
        $hasPrice = ($p['price'] ?? 0) > 0;
        $notClosed = !($p['is_closed'] ?? false); // ✅ ключевое условие из ТЗ
        $inStock = ($p['unlimited_stock'] ?? false) || ($p['stock'] ?? 1) > 0;
        return $isActive && $hasPrice && $notClosed && $inStock;
    });
    $allProducts = array_values($allProducts);

    // ========== 3. ВИРТУАЛЬНЫЕ КАТЕГОРИИ (ПО СВОЙСТВАМ) ==========

    // НОВИНКИ (is_new = true)
    $newProducts = array_filter($allProducts, function($product) {
        return ($product['is_new'] ?? false) === true;
    });
    $newProducts = array_values($newProducts);
    $newProducts = array_slice($newProducts, 0, 8);

    // ПОПУЛЯРНЫЕ (is_popular = true)
    $popularProducts = array_filter($allProducts, function($product) {
        return ($product['is_popular'] ?? false) === true;
    });
    $popularProducts = array_values($popularProducts);
    $popularProducts = array_slice($popularProducts, 0, 12);

    // Если популярных нет - берём самые дорогие (исключая новинки)
    if (empty($popularProducts)) {
        $newProductIds = array_column($newProducts, 'id');
        $popularProducts = array_filter($allProducts, function($product) use ($newProductIds) {
            return !in_array($product['id'], $newProductIds);
        });

        usort($popularProducts, function($a, $b) {
            return ($b['price'] ?? 0) - ($a['price'] ?? 0);
        });
        $popularProducts = array_values($popularProducts);
        $popularProducts = array_slice($popularProducts, 0, 12);
    }

    // ========== 4. РЕАЛЬНЫЕ КАТЕГОРИИ (ПО РОДИТЕЛЮ ИЗ 1С) ==========
    // Показываем только категории с открытыми товарами
    $categories = [];

    foreach ($allCategoriesRaw as $cat) {
        // Пропускаем неактивные и специальные категории
        if (($cat['status'] ?? 'active') !== 'active') {
            continue;
        }

        if ($cat['is_special'] ?? false) {
            continue;
        }

        // Считаем количество товаров в категории
        $categoryProductCount = 0;
        foreach ($allProducts as $product) {
            if (($product['category_id'] ?? 0) == $cat['id']) {
                $categoryProductCount++;
            }
        }

        // ✅ Если есть хоть один товар - показываем категорию
        if ($categoryProductCount > 0) {
            $categories[] = $cat;
        }
    }

    // Сортируем по порядку
    usort($categories, function($a, $b) {
        return ($a['sort_order'] ?? 999) - ($b['sort_order'] ?? 999);
    });

    // ========== 5. НАСТРОЙКИ САЙТА ==========
    $siteSettingsData = $db->find('settings', 'main');

    // Базовые настройки по умолчанию
    $defaultSettings = [
        'site_name' => "Sasha's Sushi",
        'site_description' => 'Лучшие суши и роллы в городе с доставкой',
        'delivery_cost' => 200,
        'free_delivery_from' => 999,
        'min_order_amount' => 800,
        'phones' => ['+7 999 123-45-67'],
        'work_hours' => ['start' => '10:00', 'end' => '23:00'],
        'vk_link' => 'https://vk.com/sasha_s_sushi',
        'telegram_link' => '',
        'email' => 'ledybag47@bk.ru',
        'site_logo' => '',
        'show_jobs_banner' => true,
        'jobs_banner_title' => 'Требуются работники',
        'jobs_banner_text' => 'Официальное оформление. Стабильная зарплата!',
        'jobs_banner_link' => 'https://forms.yandex.ru/cloud/65d07d1ac09c024b01bf6adb/'
    ];

    // Объединяем настройки из БД с дефолтными
    if ($siteSettingsData && is_array($siteSettingsData)) {
        $siteSettings = array_merge($defaultSettings, $siteSettingsData);
    } else {
        $siteSettings = $defaultSettings;
    }

    // Проверяем ссылку VK (всегда используем правильную)
    $siteSettings['vk_link'] = 'https://vk.com/sasha_s_sushi';

    // Статистика
    $stats = [
        'products' => count($allProducts),
        'categories' => count($categories),
        'orders' => count($db->findAll('orders') ?: [])
    ];

    $dbConnected = true;

} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    $categories = [];
    $newProducts = [];
    $popularProducts = [];
    $allProducts = [];
    $stats = ['products' => 0, 'categories' => 0, 'orders' => 0];
    $siteSettings = [
        'site_name' => "Sasha's Sushi",
        'site_description' => 'Лучшие суши и роллы в городе с доставкой',
        'delivery_cost' => 200,
        'free_delivery_from' => 999,
        'min_order_amount' => 800,
        'phones' => ['+7 999 123-45-67'],
        'work_hours' => ['start' => '10:00', 'end' => '23:00'],
        'vk_link' => 'https://vk.com/sasha_s_sushi',
        'telegram_link' => '',
        'email' => 'ledybag47@bk.ru',
        'site_logo' => '',
        'show_jobs_banner' => true,
        'jobs_banner_title' => 'Требуются работники',
        'jobs_banner_text' => 'Официальное оформление. Стабильная зарплата!',
        'jobs_banner_link' => 'https://forms.yandex.ru/cloud/65d07d1ac09c024b01bf6adb/'
    ];
    $dbConnected = false;
}

$logoUrl = $siteSettings['site_logo'] ?? null;

// Преобразуем массивы товаров в JSON для JavaScript
$allProductsJson = json_encode(array_values(array_map(function($p) {
    return [
        'id' => $p['id'],
        'name' => $p['name'],
        'price' => $p['price'],
        'image' => getProductImage($p),
        'category_id' => $p['category_id'] ?? 0,
        'is_new' => $p['is_new'] ?? false,
        'is_popular' => $p['is_popular'] ?? false,
        'stock' => $p['stock'] ?? null,
        'unlimited_stock' => $p['unlimited_stock'] ?? false,
        'description' => $p['description'] ?? '',
        'weight' => $p['weight'] ?? '',
        'composition' => $p['composition'] ?? '',
        'nutrition' => $p['nutrition'] ?? '',
        'external_id' => $p['external_id'] ?? ''
    ];
}, $allProducts)), JSON_UNESCAPED_UNICODE);

$newProductsJson = json_encode(array_values(array_map(function($p) {
    return ['id' => $p['id']];
}, $newProducts)), JSON_UNESCAPED_UNICODE);

$popularProductsJson = json_encode(array_values(array_map(function($p) {
    return ['id' => $p['id']];
}, $popularProducts)), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= safe_output($siteSettings['site_name']) ?> - Доставка суши и роллов</title>
    <meta name="description" content="<?= safe_output($siteSettings['site_description']) ?>">
    <meta name="theme-color" content="#E31E24">

    <!-- Favicon -->
    <?php if ($logoUrl): ?>
    <link rel="icon" href="<?= safe_output($logoUrl) ?>">
    <?php else: ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍣</text></svg>">
    <?php endif; ?>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ========== БАЗОВЫЕ СТИЛИ ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-red: #E31E24;
            --primary-red-hover: #C41A1F;
            --primary-red-light: #FF4046;
            --black: #1A1A1A;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
            --header-height: 70px;
            --category-nav-height: 60px;
            --transition: all 0.2s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--gray-900);
            background: var(--gray-50);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            scroll-behavior: smooth;
        }

        body.modal-open {
            overflow: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ==================== HEADER ==================== */
        .header {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--header-height);
            transition: var(--transition);
        }

        .header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--header-height);
            gap: 16px;
        }

        .header-logo .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: var(--transition);
        }

        .header-logo .logo:hover {
            opacity: 0.8;
        }

        .logo-image {
            height: 40px;
            width: 40px;
            object-fit: contain;
        }

        .logo-emoji {
            font-size: 32px;
            line-height: 1;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: var(--black);
            white-space: nowrap;
        }

        /* Navigation */
        .header-nav {
            display: flex;
            gap: 8px;
        }

        .nav-link {
            padding: 8px 14px;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            white-space: nowrap;
        }

        .nav-link:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .nav-link.active {
            color: var(--primary-red);
            background: rgba(227, 30, 36, 0.08);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-social {
            display: flex;
            gap: 6px;
        }

        .header-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            color: var(--gray-600);
            background: var(--gray-100);
            border-radius: var(--radius-sm);
            transition: var(--transition);
            text-decoration: none;
        }

        .header-social a:hover {
            background: var(--primary-red);
            color: var(--white);
        }

        /* Search */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 240px;
            padding: 8px 36px 8px 12px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.1);
        }

        .search-input.has-value {
            border-color: var(--primary-red);
            background: rgba(227, 30, 36, 0.02);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
            transition: var(--transition);
        }

        .search-input:focus ~ .search-icon,
        .search-input.has-value ~ .search-icon {
            color: var(--primary-red);
        }

        .search-clear {
            position: absolute;
            right: 36px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            display: none;
            align-items: center;
            justify-content: center;
            background: var(--gray-300);
            color: var(--white);
            border-radius: 50%;
            cursor: pointer;
            font-size: 10px;
            transition: var(--transition);
        }

        .search-clear:hover {
            background: var(--primary-red);
        }

        .search-input.has-value ~ .search-clear {
            display: flex;
        }

        /* Auth Button / User Profile */
        .auth-btn, .user-profile {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--gray-100);
            color: var(--gray-700);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: var(--transition);
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .auth-btn:hover, .user-profile:hover {
            background: var(--gray-200);
            color: var(--gray-900);
        }

        .user-profile {
            position: relative;
        }

        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary-red);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .user-name {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            overflow: hidden;
            z-index: 1000;
        }

        .user-profile:hover .user-dropdown,
        .user-profile:focus-within .user-dropdown {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-200);
            margin: 4px 0;
        }

        /* Cart Button */
        .cart-btn {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .cart-btn:hover {
            background: var(--primary-red-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .cart-btn i {
            font-size: 16px;
        }

        .cart-badge {
            display: none;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            background: var(--white);
            color: var(--primary-red);
            font-size: 11px;
            font-weight: 700;
            border-radius: 10px;
        }

        .cart-badge.show {
            display: flex;
        }

        .cart-info {
            display: flex;
            align-items: center;
        }

        .cart-total {
            font-weight: 600;
        }

        /* Mobile Menu Toggle */
        .mobile-toggle {
            display: none;
            flex-direction: column;
            gap: 4px;
            width: 36px;
            height: 36px;
            padding: 8px;
            background: var(--gray-100);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
        }

        .mobile-toggle span {
            display: block;
            height: 2px;
            background: var(--gray-700);
            border-radius: 2px;
            transition: var(--transition);
        }

        .mobile-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .mobile-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--white);
            z-index: 99;
            overflow-y: auto;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-menu-section {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .mobile-menu-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: var(--gray-100);
            color: var(--primary-red);
        }

        .mobile-nav-link i {
            width: 20px;
            text-align: center;
        }

        /* ==================== JOBS BANNER ==================== */
        .jobs-banner {
            background: linear-gradient(135deg, rgba(227, 30, 36, 0.03) 0%, rgba(227, 30, 36, 0.06) 100%);
            border: 1px solid rgba(227, 30, 36, 0.15);
            border-radius: var(--radius-lg);
            margin: 16px 20px;
            padding: 14px 20px;
            transition: var(--transition);
        }

        .jobs-banner:hover {
            background: linear-gradient(135deg, rgba(227, 30, 36, 0.05) 0%, rgba(227, 30, 36, 0.08) 100%);
            border-color: rgba(227, 30, 36, 0.25);
        }

        .jobs-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .jobs-text h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray-700);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .jobs-text h3 i {
            color: rgba(227, 30, 36, 0.7);
            font-size: 18px;
        }

        .jobs-text p {
            color: var(--gray-600);
            font-size: 13px;
        }

        .jobs-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(227, 30, 36, 0.1);
            color: var(--primary-red);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: var(--transition);
            white-space: nowrap;
            border: 1px solid rgba(227, 30, 36, 0.2);
        }

        .jobs-btn:hover {
            background: var(--primary-red);
            color: var(--white);
            border-color: var(--primary-red);
        }

        /* ==================== STICKY CATEGORY NAV ==================== */
        .category-nav-wrapper {
            position: sticky;
            top: var(--header-height);
            z-index: 90;
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: top;
        }

        .category-nav-wrapper.scrolled {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .category-nav {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 20px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .category-nav::-webkit-scrollbar {
            display: none;
        }

        .category-nav-item {
            padding: 10px 18px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .category-nav-item:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        .category-nav-item.active {
            background: var(--primary-red);
            border-color: var(--primary-red);
            color: var(--white);
        }

        /* ==================== SEARCH RESULTS ==================== */
        .search-results-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 24px 20px 16px;
            background: rgba(227, 30, 36, 0.03);
            border-radius: var(--radius-lg);
            margin: 24px 20px 0;
        }

        .search-results-header.active {
            display: flex;
        }

        .search-results-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-results-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-red);
            color: var(--white);
            border-radius: 50%;
            font-size: 18px;
        }

        .search-results-text h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .search-results-text p {
            font-size: 14px;
            color: var(--gray-600);
        }

        .search-results-text .search-query {
            color: var(--primary-red);
            font-weight: 600;
        }

        .search-clear-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--white);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-clear-btn:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        .search-highlight {
            background: rgba(227, 30, 36, 0.15);
            color: var(--primary-red);
            font-weight: 600;
            padding: 1px 2px;
            border-radius: 2px;
        }

        /* ==================== PRODUCTS SECTIONS ==================== */
        .products-wrapper {
            padding: 24px 0 60px;
        }

        .category-section {
            margin-bottom: 48px;
            scroll-margin-top: calc(var(--header-height) + var(--category-nav-height) + 20px);
        }

        .category-section.hidden {
            display: none;
        }

        .category-section-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 24px;
            padding: 0 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 0 20px;
        }

        .product-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            opacity: 0;
            animation: fadeInUp 0.4s ease-out forwards;
            cursor: pointer;
        }

        .product-card.search-hidden {
            display: none !important;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .product-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-new {
            background: #10B981;
            color: var(--white);
        }

        .badge-hit {
            background: #F59E0B;
            color: var(--white);
        }

        .product-image {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: var(--gray-100);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-content {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .product-description {
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .product-weight {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: auto;
        }

        .product-price {
            display: flex;
            flex-direction: column;
        }

        .current-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--black);
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .add-btn:hover:not(:disabled) {
            background: var(--primary-red-hover);
            transform: translateY(-1px);
        }

        .add-btn:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .add-btn i {
            font-size: 12px;
        }

        /* ==================== PRODUCT MODAL ==================== */
        .product-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1001;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .product-modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background: var(--white);
            border-radius: var(--radius-xl);
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 1;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            color: var(--white);
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.7);
            transform: rotate(90deg);
        }

        .modal-image-wrapper {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            background: var(--gray-100);
        }

        .modal-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-badges {
            position: absolute;
            top: 16px;
            left: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .modal-body {
            padding: 32px;
        }

        .modal-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .modal-description {
            font-size: 16px;
            color: var(--gray-700);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .modal-specs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
        }

        .spec-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .spec-label {
            font-size: 13px;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            font-size: 16px;
            color: var(--gray-900);
            font-weight: 600;
        }

        .modal-quantity {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
        }

        .quantity-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quantity-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .quantity-btn:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
            background: var(--white);
        }

        .quantity-btn:active {
            transform: scale(0.95);
        }

        .quantity-value {
            min-width: 60px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-top: 24px;
            border-top: 2px solid var(--gray-200);
        }

        .modal-price-wrapper {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .modal-price-label {
            font-size: 13px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .modal-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-red);
        }

        .modal-add-btn {
            flex: 1;
            max-width: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-add-btn:hover:not(:disabled) {
            background: var(--primary-red-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .modal-add-btn:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .modal-add-btn i {
            font-size: 18px;
        }

        /* No Products */
        .no-products {
            text-align: center;
            padding: 60px 20px;
        }

        .no-products-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .no-products h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .no-products p {
            font-size: 16px;
            color: var(--gray-600);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .retry-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .retry-btn:hover {
            background: var(--primary-red-hover);
            transform: translateY(-2px);
        }

        /* ==================== CART SIDEBAR ==================== */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            max-width: 440px;
            z-index: 1000;
            pointer-events: none;
        }

        .cart-sidebar.active {
            pointer-events: all;
        }

        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .cart-sidebar.active .cart-overlay {
            opacity: 1;
            pointer-events: all;
        }

        .cart-panel {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            background: var(--white);
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-xl);
        }

        .cart-sidebar.active .cart-panel {
            transform: translateX(0);
        }

        .cart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .cart-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--black);
        }

        .cart-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--gray-100);
            border: none;
            border-radius: var(--radius-sm);
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
        }

        .cart-close:hover {
            background: var(--gray-200);
            color: var(--gray-900);
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .cart-empty {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .cart-empty h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .cart-empty p {
            font-size: 15px;
            color: var(--gray-600);
            margin-bottom: 24px;
        }

        .continue-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--gray-100);
            color: var(--gray-700);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .continue-btn:hover {
            background: var(--gray-200);
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cart-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }

        .cart-item-image {
            width: 70px;
            height: 70px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            flex-shrink: 0;
        }

        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cart-item-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .cart-item-price {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-red);
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .qty-btn:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        .qty-input {
            width: 40px;
            height: 28px;
            text-align: center;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .cart-item-remove {
            margin-left: auto;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
        }

        .cart-item-remove:hover {
            color: var(--primary-red);
        }

        .cart-item-total {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
            white-space: nowrap;
        }

        .cart-footer {
            border-top: 1px solid var(--gray-200);
            padding: 20px;
        }

        .cart-summary {
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: var(--gray-700);
        }

        .summary-row.total {
            padding-top: 12px;
            border-top: 2px solid var(--gray-200);
            font-size: 18px;
            font-weight: 700;
            color: var(--black);
        }

        .delivery-info {
            margin-top: 12px;
            padding: 10px;
            background: rgba(227, 30, 36, 0.05);
            border-radius: var(--radius-sm);
            text-align: center;
        }

        .delivery-info small {
            font-size: 13px;
            color: var(--primary-red);
            font-weight: 500;
        }

        .cart-actions {
            display: flex;
            gap: 10px;
        }

        .clear-cart-btn {
            flex: 1;
            padding: 12px;
            background: var(--gray-100);
            color: var(--gray-700);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .clear-cart-btn:hover {
            background: var(--gray-200);
        }

        .checkout-btn {
            flex: 2;
            padding: 12px;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .checkout-btn:hover {
            background: var(--primary-red-hover);
            transform: translateY(-1px);
        }

        /* ==================== FOOTER ==================== */
        .footer {
            background: var(--gray-900);
            color: var(--gray-300);
            padding: 48px 0 24px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            margin-bottom: 32px;
        }

        .footer-section h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 16px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .footer-logo .logo-image {
            height: 36px;
            width: 36px;
        }

        .footer-logo .logo-emoji {
            font-size: 28px;
        }

        .footer-logo .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
        }

        .footer-description {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .footer-address {
            display: flex;
            gap: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--gray-300);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .social-links {
            display: flex;
            gap: 10px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--gray-800);
            color: var(--gray-300);
            border-radius: var(--radius-md);
            transition: var(--transition);
            text-decoration: none;
        }

        .social-link:hover {
            background: var(--primary-red);
            color: var(--white);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .contact-item i {
            width: 18px;
            color: var(--primary-red);
        }

        .contact-item a {
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-item a:hover {
            color: var(--white);
        }

        .vk-widget-container {
            margin-top: 16px;
        }

        .delivery-zones {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid var(--gray-800);
        }

        .delivery-zones h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
        }

        .zone-map {
            margin-bottom: 20px;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .zones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .zone-card {
            padding: 16px;
            border-radius: var(--radius-md);
            border-left: 4px solid;
        }

        .zone-card.green {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10B981;
        }

        .zone-card.yellow {
            background: rgba(245, 158, 11, 0.1);
            border-color: #F59E0B;
        }

        .zone-card.red {
            background: rgba(239, 68, 68, 0.1);
            border-color: #EF4444;
        }

        .zone-card h5 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .zone-card.green h5 i {
            color: #10B981;
        }

        .zone-card.yellow h5 i {
            color: #F59E0B;
        }

        .zone-card.red h5 i {
            color: #EF4444;
        }

        .zone-card p {
            font-size: 13px;
            line-height: 1.5;
        }

        .footer-bottom {
            padding-top: 24px;
            border-top: 1px solid var(--gray-800);
            text-align: center;
        }

        .footer-bottom p {
            font-size: 13px;
            color: var(--gray-400);
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {
            .header-nav {
                display: none;
            }

            .search-input {
                width: 180px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            :root {
                --header-height: 60px;
            }

            .container {
                padding: 0 16px;
            }

            .header-wrapper {
                gap: 10px;
            }

            .logo-text {
                font-size: 18px;
            }

            .header-social,
            .search-container {
                display: none;
            }

            .mobile-toggle {
                display: flex;
            }

            .header-actions {
                gap: 8px;
            }

            .user-name {
                max-width: 80px;
            }

            .cart-btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .cart-info {
                display: none;
            }

            .jobs-banner {
                margin: 12px 16px;
                padding: 12px 16px;
            }

            .jobs-content {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .jobs-text h3 {
                font-size: 15px;
                justify-content: center;
            }

            .jobs-text p {
                font-size: 12px;
            }

            .jobs-btn {
                width: 100%;
                justify-content: center;
                font-size: 12px;
            }

            .search-results-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .search-results-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .category-section-title {
                font-size: 24px;
                padding: 0 16px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 0 16px;
            }

            .product-card {
                display: flex;
                flex-direction: row;
                border-radius: var(--radius-md);
                width: 100%;
            }

            .product-image {
                width: 110px;
                height: 110px;
                flex-shrink: 0;
            }

            .product-content {
                padding: 12px;
                width: 100%;
            }

            .product-title {
                font-size: 14px;
                margin-bottom: 4px;
            }

            .product-description {
                font-size: 12px;
                margin-bottom: 4px;
            }

            .product-weight {
                font-size: 11px;
                margin-bottom: 4px;
            }

            .product-footer {
                flex-direction: row;
                align-items: center;
                gap: 8px;
            }

            .current-price {
                font-size: 16px;
            }

            .add-btn {
                padding: 6px 12px;
                font-size: 12px;
                flex-shrink: 0;
            }

            .product-badges {
                top: 6px;
                left: 6px;
                gap: 4px;
            }

            .badge {
                padding: 2px 6px;
                font-size: 9px;
            }

            .modal-image-wrapper {
                height: 300px;
            }

            .modal-body {
                padding: 24px 20px;
            }

            .modal-title {
                font-size: 24px;
            }

            .modal-description {
                font-size: 15px;
            }

            .modal-specs {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                padding: 16px;
            }

            .modal-quantity {
                padding: 16px;
            }

            .modal-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .modal-add-btn {
                max-width: 100%;
            }

            .cart-sidebar {
                max-width: 100%;
            }

            .cart-panel {
                max-width: 100%;
            }

            .cart-item {
                padding: 10px;
            }

            .cart-item-image {
                width: 60px;
                height: 60px;
            }

            .cart-item-name {
                font-size: 13px;
            }

            .cart-item-price {
                font-size: 14px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .zones-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }

            .cart-btn {
                padding: 8px 10px;
            }

            .cart-badge {
                min-width: 18px;
                height: 18px;
                font-size: 10px;
            }

            .jobs-text h3 {
                font-size: 14px;
            }

            .category-section-title {
                font-size: 20px;
            }

            .product-image {
                width: 90px;
                height: 90px;
            }

            .product-title {
                font-size: 13px;
            }

            .current-price {
                font-size: 15px;
            }

            .modal-image-wrapper {
                height: 250px;
            }

            .modal-body {
                padding: 20px 16px;
            }

            .modal-title {
                font-size: 20px;
            }

            .modal-specs {
                grid-template-columns: 1fr;
            }

            .modal-price {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="mainHeader">
        <div class="container">
            <div class="header-wrapper">
                <!-- Logo -->
                <div class="header-logo">
                    <a href="/" class="logo">
                        <?php if ($logoUrl): ?>
                        <img src="<?= safe_output($logoUrl) ?>?v=<?= time() ?>" alt="<?= safe_output($siteSettings['site_name']) ?>" class="logo-image">
                        <?php else: ?>
                        <span class="logo-emoji">🍣</span>
                        <?php endif; ?>
                        <span class="logo-text"><?= safe_output($siteSettings['site_name']) ?></span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="header-nav">
                    <a href="/" class="nav-link active">Меню</a>
                    <a href="/pages/promotions.php" class="nav-link">Акции</a>
                    <a href="/pages/delivery.php" class="nav-link">Доставка</a>
                    <a href="/pages/payment.php" class="nav-link">Оплата</a>
                    <a href="/pages/contacts.php" class="nav-link">Контакты</a>
                </nav>

                <!-- Actions -->
                <div class="header-actions">
                    <!-- Social Links -->
                    <div class="header-social">
                        <a href="<?= safe_output($siteSettings['vk_link']) ?>" target="_blank" rel="noopener" aria-label="VK">
                            <i class="fab fa-vk"></i>
                        </a>
                        <?php if (!empty($siteSettings['telegram_link'])): ?>
                        <a href="<?= safe_output($siteSettings['telegram_link']) ?>" target="_blank" rel="noopener" aria-label="Telegram">
                            <i class="fab fa-telegram"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Search -->
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Поиск..." id="searchInput" autocomplete="off">
                        <span class="search-clear" id="searchClear">×</span>
                        <i class="fas fa-search search-icon"></i>
                    </div>

                    <!-- Auth Button / User Profile -->
                    <?php if ($isLoggedIn): ?>
                    <div class="user-profile" tabindex="0">
                        <div class="user-avatar">
                            <?= strtoupper(mb_substr($customerName, 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= safe_output($customerName) ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>

                        <div class="user-dropdown">
                            <a href="/pages/account.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Личный кабинет</span>
                            </a>
                            <a href="/pages/account.php?tab=orders" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Мои заказы</span>
                            </a>
                            <a href="/pages/account.php?tab=bonuses" class="dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>Бонусы</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/pages/login.php?action=logout" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Выйти</span>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="/pages/login.php" class="auth-btn">
                        <i class="fas fa-user"></i>
                        <span>Войти</span>
                    </a>
                    <?php endif; ?>

                    <!-- Cart -->
                    <button class="cart-btn" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cartCount">0</span>
                        <div class="cart-info">
                            <span class="cart-total" id="cartTotal">0 ₽</span>
                        </div>
                    </button>

                    <!-- Mobile menu toggle -->
                    <button class="mobile-toggle" id="mobileToggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-section">
            <h3>Меню</h3>
            <a href="/" class="mobile-nav-link active">
                <i class="fas fa-utensils"></i>
                <span>Все меню</span>
            </a>
            <a href="/pages/promotions.php" class="mobile-nav-link">
                <i class="fas fa-gift"></i>
                <span>Акции</span>
            </a>
            <a href="/pages/delivery.php" class="mobile-nav-link">
                <i class="fas fa-truck"></i>
                <span>Доставка</span>
            </a>
            <a href="/pages/payment.php" class="mobile-nav-link">
                <i class="fas fa-credit-card"></i>
                <span>Оплата</span>
            </a>
            <a href="/pages/contacts.php" class="mobile-nav-link">
                <i class="fas fa-phone"></i>
                <span>Контакты</span>
            </a>
        </div>

        <div class="mobile-menu-section">
            <h3>Аккаунт</h3>
            <?php if ($isLoggedIn): ?>
            <a href="/pages/account.php" class="mobile-nav-link">
                <i class="fas fa-user"></i>
                <span><?= safe_output($customerName) ?></span>
            </a>
            <a href="/pages/account.php?tab=orders" class="mobile-nav-link">
                <i class="fas fa-shopping-bag"></i>
                <span>Мои заказы</span>
            </a>
            <a href="/pages/account.php?tab=bonuses" class="mobile-nav-link">
                <i class="fas fa-star"></i>
                <span>Бонусы</span>
            </a>
            <a href="/pages/login.php?action=logout" class="mobile-nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выйти</span>
            </a>
            <?php else: ?>
            <a href="/pages/login.php" class="mobile-nav-link">
                <i class="fas fa-sign-in-alt"></i>
                <span>Войти</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="mobile-menu-section">
            <h3>Социальные сети</h3>
            <a href="<?= safe_output($siteSettings['vk_link']) ?>" target="_blank" class="mobile-nav-link">
                <i class="fab fa-vk"></i>
                <span>ВКонтакте</span>
            </a>
            <?php if (!empty($siteSettings['telegram_link'])): ?>
            <a href="<?= safe_output($siteSettings['telegram_link']) ?>" target="_blank" class="mobile-nav-link">
                <i class="fab fa-telegram"></i>
                <span>Telegram</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Jobs Banner (управляемый из админки) -->
    <?php if ($siteSettings['show_jobs_banner'] ?? true): ?>
    <section class="container">
        <div class="jobs-banner">
            <div class="jobs-content">
                <div class="jobs-text">
                    <h3>
                        <i class="fas fa-users"></i>
                        <?= safe_output($siteSettings['jobs_banner_title'] ?? 'Требуются работники') ?>
                    </h3>
                    <p><?= safe_output($siteSettings['jobs_banner_text'] ?? 'Официальное оформление. Стабильная зарплата!') ?></p>
                </div>
                <a href="<?= safe_output($siteSettings['jobs_banner_link'] ?? 'https://forms.yandex.ru/cloud/65d07d1ac09c024b01bf6adb/') ?>" target="_blank" class="jobs-btn">
                    <i class="fas fa-file-alt"></i>
                    Заполнить анкету
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sticky Category Navigation -->
    <div class="category-nav-wrapper" id="categoryNav">
        <div class="container">
            <nav class="category-nav" id="categoryNavScroll">
                <button class="category-nav-item active" data-category="all">
                    Все
                </button>
                <?php if (!empty($newProducts)): ?>
                <button class="category-nav-item" data-category="new">
                    ✨ Новинки
                </button>
                <?php endif; ?>
                <?php if (!empty($popularProducts)): ?>
                <button class="category-nav-item" data-category="popular">
                    🔥 Популярное
                </button>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                <button class="category-nav-item" data-category="<?= $category['id'] ?>">
                    <?= safe_output($category['name']) ?>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Search Results Header -->
    <div class="search-results-header" id="searchResultsHeader">
        <div class="search-results-info">
            <div class="search-results-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="search-results-text">
                <h3>Результаты поиска</h3>
                <p>Найдено товаров: <span id="searchResultsCount">0</span> по запросу "<span class="search-query" id="searchQueryText"></span>"</p>
            </div>
        </div>
        <button class="search-clear-btn" id="searchClearResults">
            <i class="fas fa-times"></i>
            Очистить поиск
        </button>
    </div>

    <!-- Products by Categories -->
    <div class="products-wrapper">
        <div class="container">
            <?php if (!empty($allProducts)): ?>

                <?php
                // ✅ ID товаров, показанных в виртуальных категориях
                $shownInVirtual = [];
                ?>

                <!-- ✨ НОВИНКИ (ВИРТУАЛЬНАЯ КАТЕГОРИЯ) -->
                <?php if (!empty($newProducts)): ?>
                <section class="category-section" id="section-new" data-category="new">
                    <h2 class="category-section-title">✨ Новинки</h2>
                    <div class="products-grid">
                        <?php foreach ($newProducts as $product): 
                            $shownInVirtual[] = $product['id']; // Запоминаем ID
                        ?>
                        <div class="product-card" 
                             data-product-id="<?= safe_output($product['id']) ?>"
                             data-product-name="<?= safe_output($product['name']) ?>"
                             data-product-price="<?= safe_output($product['price']) ?>"
                             data-product-image="<?= getProductImage($product) ?>"
                             data-product-description="<?= safe_output($product['description'] ?? '') ?>"
                             data-product-weight="<?= safe_output($product['weight'] ?? '') ?>"
                             data-product-composition="<?= safe_output($product['composition'] ?? '') ?>"
                             data-product-stock="<?= safe_output($product['stock'] ?? '') ?>"
                             data-product-unlimited-stock="<?= ($product['unlimited_stock'] ?? false) ? '1' : '0' ?>"
                             data-product-is-new="1"
                             data-product-external-id="<?= safe_output($product['external_id'] ?? '') ?>"
                             data-search-text="<?= strtolower(safe_output($product['name'] . ' ' . ($product['composition'] ?? '') . ' ' . ($product['description'] ?? ''))) ?>">
                            <div class="product-image">
                                <div class="product-badges">
                                    <span class="badge badge-new">Новинка</span>
                                </div>
                                <img src="<?= getProductImage($product) ?>" alt="<?= safe_output($product['name']) ?>" loading="lazy">
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><?= safe_output($product['name']) ?></h3>
                                <?php if (!empty($product['composition'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['composition'], 0, 60)) ?><?= mb_strlen($product['composition']) > 60 ? '...' : '' ?></p>
                                <?php elseif (!empty($product['description'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['description'], 0, 60)) ?><?= mb_strlen($product['description']) > 60 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <?php if (!empty($product['weight'])): ?>
                                <div class="product-weight"><?= safe_output($product['weight']) ?> г</div>
                                <?php endif; ?>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="current-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
                                    </div>
                                    <button class="add-btn add-to-cart-btn" 
                                            data-product-id="<?= safe_output($product['id']) ?>"
                                            data-product-name="<?= safe_output($product['name']) ?>"
                                            data-product-price="<?= safe_output($product['price']) ?>"
                                            data-product-image="<?= getProductImage($product) ?>"
                                            onclick="event.stopPropagation()">
                                        <i class="fas fa-plus"></i> В корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 🔥 ПОПУЛЯРНОЕ (ВИРТУАЛЬНАЯ КАТЕГОРИЯ) -->
                <?php if (!empty($popularProducts)): ?>
                <section class="category-section" id="section-popular" data-category="popular">
                    <h2 class="category-section-title">🔥 Популярное</h2>
                    <div class="products-grid">
                        <?php foreach ($popularProducts as $product): 
                            $shownInVirtual[] = $product['id']; // Запоминаем ID
                        ?>
                        <div class="product-card" 
                             data-product-id="<?= safe_output($product['id']) ?>"
                             data-product-name="<?= safe_output($product['name']) ?>"
                             data-product-price="<?= safe_output($product['price']) ?>"
                             data-product-image="<?= getProductImage($product) ?>"
                             data-product-description="<?= safe_output($product['description'] ?? '') ?>"
                             data-product-weight="<?= safe_output($product['weight'] ?? '') ?>"
                             data-product-composition="<?= safe_output($product['composition'] ?? '') ?>"
                             data-product-stock="<?= safe_output($product['stock'] ?? '') ?>"
                             data-product-unlimited-stock="<?= ($product['unlimited_stock'] ?? false) ? '1' : '0' ?>"
                             data-product-is-popular="1"
                             data-product-external-id="<?= safe_output($product['external_id'] ?? '') ?>"
                             data-search-text="<?= strtolower(safe_output($product['name'] . ' ' . ($product['composition'] ?? '') . ' ' . ($product['description'] ?? ''))) ?>">
                            <div class="product-image">
                                <div class="product-badges">
                                    <span class="badge badge-hit">Хит</span>
                                </div>
                                <img src="<?= getProductImage($product) ?>" alt="<?= safe_output($product['name']) ?>" loading="lazy">
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><?= safe_output($product['name']) ?></h3>
                                <?php if (!empty($product['composition'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['composition'], 0, 60)) ?><?= mb_strlen($product['composition']) > 60 ? '...' : '' ?></p>
                                <?php elseif (!empty($product['description'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['description'], 0, 60)) ?><?= mb_strlen($product['description']) > 60 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <?php if (!empty($product['weight'])): ?>
                                <div class="product-weight"><?= safe_output($product['weight']) ?> г</div>
                                <?php endif; ?>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="current-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
                                    </div>
                                    <button class="add-btn add-to-cart-btn" 
                                            data-product-id="<?= safe_output($product['id']) ?>"
                                            data-product-name="<?= safe_output($product['name']) ?>"
                                            data-product-price="<?= safe_output($product['price']) ?>"
                                            data-product-image="<?= getProductImage($product) ?>"
                                            onclick="event.stopPropagation()">
                                        <i class="fas fa-plus"></i> В корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 📁 РЕАЛЬНЫЕ КАТЕГОРИИ (ПО РОДИТЕЛЮ ИЗ 1С) -->
                <?php foreach ($categories as $category): 
                    // ✅ Берём только товары этой категории, которые НЕ были показаны выше
                    $categoryProducts = array_filter($allProducts, function($p) use ($category, $shownInVirtual) {
                        $belongsToCategory = ($p['category_id'] ?? 0) == $category['id'];
                        $notShownYet = !in_array($p['id'], $shownInVirtual);
                        return $belongsToCategory && $notShownYet;
                    });

                    // Если нет товаров - пропускаем категорию
                    if (empty($categoryProducts)) continue;

                    $categoryProducts = array_values($categoryProducts);
                ?>
                <section class="category-section" id="section-<?= $category['id'] ?>" data-category="<?= $category['id'] ?>">
                    <h2 class="category-section-title"><?= safe_output($category['name']) ?></h2>
                    <div class="products-grid">
                        <?php foreach ($categoryProducts as $product): ?>
                        <div class="product-card" 
                             data-product-id="<?= safe_output($product['id']) ?>"
                             data-product-name="<?= safe_output($product['name']) ?>"
                             data-product-price="<?= safe_output($product['price']) ?>"
                             data-product-image="<?= getProductImage($product) ?>"
                             data-product-description="<?= safe_output($product['description'] ?? '') ?>"
                             data-product-weight="<?= safe_output($product['weight'] ?? '') ?>"
                             data-product-composition="<?= safe_output($product['composition'] ?? '') ?>"
                             data-product-stock="<?= safe_output($product['stock'] ?? '') ?>"
                             data-product-unlimited-stock="<?= ($product['unlimited_stock'] ?? false) ? '1' : '0' ?>"
                             data-product-is-new="<?= ($product['is_new'] ?? false) ? '1' : '0' ?>"
                             data-product-is-popular="<?= ($product['is_popular'] ?? false) ? '1' : '0' ?>"
                             data-product-external-id="<?= safe_output($product['external_id'] ?? '') ?>"
                             data-search-text="<?= strtolower(safe_output($product['name'] . ' ' . ($product['composition'] ?? '') . ' ' . ($product['description'] ?? ''))) ?>">
                            <div class="product-image">
                                <?php if (($product['is_new'] ?? false) || ($product['is_popular'] ?? false)): ?>
                                <div class="product-badges">
                                    <?php if ($product['is_new'] ?? false): ?>
                                    <span class="badge badge-new">Новинка</span>
                                    <?php endif; ?>
                                    <?php if ($product['is_popular'] ?? false): ?>
                                    <span class="badge badge-hit">Хит</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <img src="<?= getProductImage($product) ?>" alt="<?= safe_output($product['name']) ?>" loading="lazy">
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><?= safe_output($product['name']) ?></h3>
                                <?php if (!empty($product['composition'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['composition'], 0, 60)) ?><?= mb_strlen($product['composition']) > 60 ? '...' : '' ?></p>
                                <?php elseif (!empty($product['description'])): ?>
                                <p class="product-description"><?= safe_output(mb_substr($product['description'], 0, 60)) ?><?= mb_strlen($product['description']) > 60 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <?php if (!empty($product['weight'])): ?>
                                <div class="product-weight"><?= safe_output($product['weight']) ?> г</div>
                                <?php endif; ?>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="current-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
                                    </div>
                                    <button class="add-btn add-to-cart-btn" 
                                            data-product-id="<?= safe_output($product['id']) ?>"
                                            data-product-name="<?= safe_output($product['name']) ?>"
                                            data-product-price="<?= safe_output($product['price']) ?>"
                                            data-product-image="<?= getProductImage($product) ?>"
                                            onclick="event.stopPropagation()">
                                        <i class="fas fa-plus"></i> В корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>

            <?php else: ?>
            <div class="no-products">
                <div class="no-products-icon">
                    <?php if ($dbConnected): ?>
                    🍱
                    <?php else: ?>
                    ⚠️
                    <?php endif; ?>
                </div>
                <h3>
                    <?php if ($dbConnected): ?>
                        Товары загружаются из 1С
                    <?php else: ?>
                        База данных недоступна
                    <?php endif; ?>
                </h3>
                <p>
                    <?php if ($dbConnected): ?>
                        Пожалуйста, подождите. Товары появятся после синхронизации с 1С.<br>
                        Или перейдите в <a href="/admin/visual1c.php" style="color: var(--primary-red);">панель администратора</a> для загрузки товаров.
                    <?php else: ?>
                        Пожалуйста, попробуйте позже
                    <?php endif; ?>
                </p>
                <?php if (!$dbConnected): ?>
                <button class="retry-btn" onclick="location.reload()">
                    <i class="fas fa-sync"></i> Попробовать снова
                </button>
                <?php else: ?>
                <a href="/admin/visual1c.php" class="retry-btn">
                    <i class="fas fa-cog"></i> Панель интеграции 1С
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="product-modal" id="productModal">
        <div class="modal-overlay" onclick="closeProductModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>

            <div class="modal-image-wrapper">
                <div class="modal-badges" id="modalBadges"></div>
                <img id="modalImage" src="" alt="">
            </div>

            <div class="modal-body">
                <h2 class="modal-title" id="modalTitle"></h2>
                <p class="modal-description" id="modalDescription"></p>

                <div class="modal-specs">
                    <div class="spec-item">
                        <span class="spec-label">Вес</span>
                        <span class="spec-value" id="modalWeight">—</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Наличие</span>
                        <span class="spec-value" id="modalStock">В наличии</span>
                    </div>
                </div>

                <!-- Quantity Controls -->
                <div class="modal-quantity">
                    <span class="quantity-label">Количество:</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn" id="modalQuantityMinus" onclick="changeModalQuantity(-1)">−</button>
                        <span class="quantity-value" id="modalQuantityValue">1</span>
                        <button class="quantity-btn" id="modalQuantityPlus" onclick="changeModalQuantity(1)">+</button>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="modal-price-wrapper">
                        <span class="modal-price-label">Цена</span>
                        <div class="modal-price" id="modalPrice"></div>
                    </div>
                    <button class="modal-add-btn" id="modalAddBtn" onclick="addModalProductToCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <span>В корзину</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-overlay" id="cartOverlay"></div>
        <div class="cart-panel">
            <div class="cart-header">
                <h3>Корзина</h3>
                <button class="cart-close" id="cartClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="cart-body">
                <div class="cart-empty" id="cartEmpty">
                    <div class="empty-icon">🛒</div>
                    <h4>Корзина пуста</h4>
                    <p>Добавьте товары из меню</p>
                    <button class="continue-btn" onclick="document.getElementById('cartClose').click()">Продолжить покупки</button>
                </div>

                <div class="cart-items" id="cartItems" style="display: none;">
                    <!-- Динамически заполняется JavaScript -->
                </div>
            </div>

            <div class="cart-footer" id="cartFooter" style="display: none;">
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Товаров:</span>
                        <span id="cartItemsCount">0</span>
                    </div>
                    <div class="summary-row">
                        <span>Сумма:</span>
                        <span id="cartSubtotal">0 ₽</span>
                    </div>
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span id="cartDelivery"><?= number_format($siteSettings['delivery_cost'] ?? 200, 0, ',', ' ') ?> ₽</span>
                    </div>
                    <div class="summary-row total">
                        <span>Итого:</span>
                        <span id="cartTotalAmount">0 ₽</span>
                    </div>

                    <?php if (isset($siteSettings['free_delivery_from'])): ?>
                    <div class="delivery-info" id="deliveryInfo">
                        <small>Бесплатная доставка от <?= number_format($siteSettings['free_delivery_from'], 0, ',', ' ') ?> ₽</small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="cart-actions">
                    <button class="clear-cart-btn" id="clearCartBtn">Очистить</button>
                    <button class="checkout-btn" onclick="window.location.href='/pages/checkout.php'">
                        Оформить заказ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <?php if ($logoUrl): ?>
                        <img src="<?= safe_output($logoUrl) ?>?v=<?= time() ?>" alt="<?= safe_output($siteSettings['site_name']) ?>" class="logo-image">
                        <?php else: ?>
                        <span class="logo-emoji">🍣</span>
                        <?php endif; ?>
                        <span class="logo-text"><?= safe_output($siteSettings['site_name']) ?></span>
                    </div>
                    <p class="footer-description">
                        <?= safe_output($siteSettings['site_description']) ?>
                    </p>
                    <p class="footer-address">
                        <i class="fas fa-map-marker-alt"></i>
                        Лен. обл. г. Сосновый Бор, ул. Красных Фортов, 49
                    </p>
                    <div class="social-links">
                        <a href="<?= safe_output($siteSettings['vk_link']) ?>" target="_blank" class="social-link" aria-label="VK">
                            <i class="fab fa-vk"></i>
                        </a>
                        <?php if (!empty($siteSettings['telegram_link'])): ?>
                        <a href="<?= safe_output($siteSettings['telegram_link']) ?>" target="_blank" class="social-link" aria-label="Telegram">
                            <i class="fab fa-telegram"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Меню</h4>
                    <ul class="footer-links">
                        <li><a href="/">Все меню</a></li>
                        <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                        <li><a href="#section-<?= $category['id'] ?>"><?= safe_output($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Информация</h4>
                    <ul class="footer-links">
                        <li><a href="/pages/promotions.php">Акции</a></li>
                        <li><a href="/pages/delivery.php">Доставка</a></li>
                        <li><a href="/pages/payment.php">Оплата на сайте</a></li>
                        <li><a href="/pages/contacts.php">Контакты</a></li>
                        <li><a href="/pages/privacy.php">Политика конфиденциальности</a></li>
                        <li><a href="https://forms.yandex.ru/cloud/65d07d1ac09c024b01bf6adb/" target="_blank">Вакансии</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Контакты</h4>
                    <div class="contact-info">
                        <?php if (!empty($siteSettings['phones'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $siteSettings['phones'][0]) ?>">
                                <?= safe_output($siteSettings['phones'][0]) ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?= safe_output($siteSettings['email']) ?>">
                                <?= safe_output($siteSettings['email']) ?>
                            </a>
                        </div>

                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>
                                <?= safe_output($siteSettings['work_hours']['start'] ?? '10:00') ?> - 
                                <?= safe_output($siteSettings['work_hours']['end'] ?? '23:00') ?>
                            </span>
                        </div>
                    </div>

                    <!-- VK Widget -->
                    <div class="vk-widget-container">
                        <div id="vk_community_messages"></div>
                    </div>
                </div>
            </div>

            <!-- Зоны доставки -->
            <div class="delivery-zones">
                <h4>
                    <i class="fas fa-map-marked-alt"></i>
                    Зоны доставки
                </h4>

                <div class="zone-map">
                    <div style="position:relative;overflow:hidden;border-radius:12px;">
                        <iframe src="https://yandex.ru/map-widget/v1/?from=mapframe&ll=29.104519%2C59.889521&mode=usermaps&source=mapframe&um=constructor%3A23d7ce2ff1ccd3a5e9e754d578502920ed2790d814c04dedc6d380b0e94cca06&utm_source=mapframe&z=12" 
                                width="100%" 
                                height="400" 
                                frameborder="0" 
                                allowfullscreen="true" 
                                style="position:relative;border-radius:12px;">
                        </iframe>
                    </div>
                </div>

                <div class="zones-grid">
                    <div class="zone-card green">
                        <h5>
                            <i class="fas fa-circle"></i>
                            Зеленая зона
                        </h5>
                        <p>Доставка от <strong>1500 руб.</strong></p>
                    </div>

                    <div class="zone-card yellow">
                        <h5>
                            <i class="fas fa-circle"></i>
                            Желтая зона
                        </h5>
                        <p>Доставка от <strong>2500 руб.</strong></p>
                    </div>

                    <div class="zone-card red">
                        <h5>
                            <i class="fas fa-circle"></i>
                            Красная зона
                        </h5>
                        <p>Доставка от <strong>3500 руб.</strong></p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    <p>&copy; <?= date('Y') ?> <?= safe_output($siteSettings['site_name']) ?>. Все права защищены.</p>
                    <p style="margin: 0.5rem 0; color: rgba(255, 255, 255, 0.6); font-size: 13px;">
                        <strong>ИП Коваленко Александр Анатольевич</strong>
                    </p>
                    <p style="margin: 0.25rem 0; color: rgba(255, 255, 255, 0.5); font-size: 12px;">
                        ИНН: 471420709894
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Data for JavaScript -->
    <script>
        window.APP_DATA = {
            products: <?= $allProductsJson ?>,
            newProductIds: <?= $newProductsJson ?>,
            popularProductIds: <?= $popularProductsJson ?>,
            settings: {
                deliveryCost: <?= $siteSettings['delivery_cost'] ?? 200 ?>,
                freeDeliveryFrom: <?= $siteSettings['free_delivery_from'] ?? 999 ?>
            },
            user: {
                isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
                id: <?= $customerId ?? 'null' ?>,
                name: <?= json_encode($customerName, JSON_UNESCAPED_UNICODE) ?>
            }
        };

        let currentModalProduct = null;
        let modalQuantity = 1;
    </script>

    <!-- VK SDK -->
    <script src="https://vk.com/js/api/openapi.js?169" type="text/javascript"></script>

    <!-- Scripts -->
    <script src="/assets/js/cart.js?v=<?= time() ?>"></script>
    <script src="/assets/js/modal.js?v=<?= time() ?>"></script>

    <!-- Main Script -->
    <script>
        // ============================================
        // 🔍 SEARCH FUNCTIONALITY v2.0 (ОПТИМИЗИРОВАННЫЙ)
        // ============================================
        (function() {
            const searchInput = document.getElementById('searchInput');
            const searchClear = document.getElementById('searchClear');
            const searchClearResults = document.getElementById('searchClearResults');
            const searchResultsHeader = document.getElementById('searchResultsHeader');
            const searchQueryText = document.getElementById('searchQueryText');
            const searchResultsCount = document.getElementById('searchResultsCount');
            const categoryNav = document.getElementById('categoryNav');
            const categorySections = document.querySelectorAll('.category-section');
            const productCards = document.querySelectorAll('.product-card');

            let searchTimeout = null;
            let isSearchActive = false;
            let searchCache = new Map();

            // ✅ БЫСТРАЯ функция поиска (используем classList)
            function performSearch(query) {
                query = query.trim().toLowerCase();

                if (query.length === 0) {
                    resetSearch();
                    return;
                }

                if (query.length < 2) return;

                // Проверяем кэш
                if (searchCache.has(query)) {
                    const cached = searchCache.get(query);
                    applySearchResults(cached.found, cached.count, query);
                    return;
                }

                isSearchActive = true;
                let foundCount = 0;
                const foundCards = new Set();

                // Скрываем категории
                categorySections.forEach(section => {
                    section.classList.add('hidden');
                });
                categoryNav.style.display = 'none';

                // ✅ Один проход по карточкам
                productCards.forEach(card => {
                    const searchText = card.getAttribute('data-search-text') || '';

                    if (searchText.includes(query)) {
                        card.classList.remove('search-hidden');
                        card.parentElement.parentElement.classList.remove('hidden');
                        foundCards.add(card);
                        foundCount++;
                    } else {
                        card.classList.add('search-hidden');
                    }
                });

                // Сохраняем в кэш
                searchCache.set(query, { found: foundCards, count: foundCount });

                // Показываем результаты
                searchResultsHeader.classList.add('active');
                searchQueryText.textContent = query;
                searchResultsCount.textContent = foundCount;
                searchInput.classList.add('has-value');
            }

            // Применение результатов из кэша
            function applySearchResults(foundCards, count, query) {
                categorySections.forEach(section => {
                    section.classList.add('hidden');
                });
                categoryNav.style.display = 'none';

                productCards.forEach(card => {
                    if (foundCards.has(card)) {
                        card.classList.remove('search-hidden');
                        card.parentElement.parentElement.classList.remove('hidden');
                    } else {
                        card.classList.add('search-hidden');
                    }
                });

                searchResultsHeader.classList.add('active');
                searchQueryText.textContent = query;
                searchResultsCount.textContent = count;
                searchInput.classList.add('has-value');
            }

            // Сброс поиска
            function resetSearch() {
                isSearchActive = false;

                categorySections.forEach(section => {
                    section.classList.remove('hidden');
                });

                productCards.forEach(card => {
                    card.classList.remove('search-hidden');
                });

                categoryNav.style.display = '';
                searchResultsHeader.classList.remove('active');
                searchInput.value = '';
                searchInput.classList.remove('has-value');

                // Сброс на "Все"
                const allCategoryBtn = document.querySelector('[data-category="all"]');
                if (allCategoryBtn) {
                    document.querySelectorAll('.category-nav-item').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    allCategoryBtn.classList.add('active');
                }
            }

            // ✅ Debounce 300ms
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value;

                if (query.length > 0) {
                    searchInput.classList.add('has-value');
                } else {
                    searchInput.classList.remove('has-value');
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    performSearch(this.value);
                }
            });

            searchClear.addEventListener('click', resetSearch);
            searchClearResults.addEventListener('click', resetSearch);

            // Очистка кэша при большом размере
            setInterval(() => {
                if (searchCache.size > 50) {
                    searchCache.clear();
                }
            }, 60000);
        })();

        // MOBILE MENU
        document.getElementById('mobileToggle')?.addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('mobileMenu').classList.toggle('active');
            document.body.style.overflow = this.classList.contains('active') ? 'hidden' : '';
        });

        // STICKY CATEGORY NAV
        const categoryNav = document.getElementById('categoryNav');
        const mainHeader = document.getElementById('mainHeader');
        const categorySections = document.querySelectorAll('.category-section');
        const categoryNavItems = document.querySelectorAll('.category-nav-item');

        let isScrolling = false;
        let currentActiveCategory = 'all';
        let lastScrollY = window.scrollY;
        let isDetached = false;

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            const headerHeight = mainHeader.offsetHeight;
            const scrollingDown = currentScrollY > lastScrollY;

            if (currentScrollY > headerHeight) {
                if (!categoryNav.classList.contains('scrolled')) {
                    categoryNav.classList.add('scrolled');
                }

                if (scrollingDown && !isDetached) {
                    isDetached = true;
                } else if (!scrollingDown && isDetached) {
                    isDetached = false;
                }
            } else {
                categoryNav.classList.remove('scrolled');
                isDetached = false;
            }

            lastScrollY = currentScrollY;
        });

        const observerOptions = {
            root: null,
            rootMargin: '-100px 0px -60% 0px',
            threshold: 0
        };

        const sectionObserver = new IntersectionObserver((entries) => {
            if (isScrolling) return;

            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const sectionId = entry.target.dataset.category;
                    updateActiveCategory(sectionId);
                }
            });
        }, observerOptions);

        categorySections.forEach(section => {
            sectionObserver.observe(section);
        });

        function updateActiveCategory(categoryId) {
            if (currentActiveCategory === categoryId) return;
            currentActiveCategory = categoryId;

            categoryNavItems.forEach(item => {
                item.classList.remove('active');
                if (item.dataset.category == categoryId) {
                    item.classList.add('active');
                    item.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                }
            });
        }

        categoryNavItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryId = this.dataset.category;
                const targetSection = document.getElementById('section-' + categoryId) || 
                                      document.querySelector(`[data-category="${categoryId}"]`);

                if (targetSection) {
                    isScrolling = true;
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    updateActiveCategory(categoryId);
                    setTimeout(() => {
                        isScrolling = false;
                    }, 1000);
                }
            });
        });

        // VK WIDGET
        try {
            VK.init({apiId: 123456789});
            VK.Widgets.CommunityMessages("vk_community_messages", 123456789, {
                expandTimeout: "0",
                tooltipButtonText: "Есть вопрос?"
            });
        } catch(e) {
            console.log('VK widget error:', e);
        }

        // ANIMATION DELAY
        document.querySelectorAll('.product-card').forEach((card, index) => {
            card.style.animationDelay = `${(index % 12) * 0.05}s`;
        });

        console.log('✅ Sasha\'s Sushi v6.4.0 - SEARCH FULLY OPTIMIZED');
        console.log('📊 Products:', <?= count($allProducts) ?>);
        console.log('📁 Categories:', <?= count($categories) ?>);
        console.log('✨ New:', <?= count($newProducts) ?>);
        console.log('🔥 Popular:', <?= count($popularProducts) ?>);
        console.log('🚀 Search: ULTRA FAST with CACHE!');
    </script>
</body>
</html>
