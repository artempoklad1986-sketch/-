<?php
// functions.php - Полный файл функций для интернет-магазина (МЕГА ВЕРСИЯ)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Функция для надежного запуска сессии
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_lifetime', 86400);
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');

        if (!session_start()) {
            error_log('ОШИБКА: Не удалось запустить сессию');
            return false;
        }
        error_log('Сессия запущена: ' . session_id());
    }
    return true;
}

/**
 * Загрузка данных из JSON файла
 */
function loadJsonData($filename) {
    $filepath = __DIR__ . '/data/' . $filename;
    if (!file_exists($filepath)) {
        error_log("JSON файл не найден: $filepath");
        return [];
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ошибка парсинга JSON в $filename: " . json_last_error_msg());
        return [];
    }
    return $data ?: [];
}

/**
 * Сохранение данных в JSON файл
 */
function saveJsonData($filename, $data) {
    $filepath = __DIR__ . '/data/' . $filename;
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Не удалось создать директорию: $dir");
            return false;
        }
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Ошибка кодирования JSON для $filename: " . json_last_error_msg());
        return false;
    }
    $result = file_put_contents($filepath, $json, LOCK_EX);
    return $result !== false;
}

// ========== ТОВАРЫ ==========

function getProducts($filters = []) {
    $products = loadJsonData('products.json');

    if (!empty($filters['category_id'])) {
        $products = array_filter($products, function($product) use ($filters) {
            return isset($product['category_id']) && $product['category_id'] == $filters['category_id'];
        });
    }

    if (!empty($filters['search'])) {
        $search = mb_strtolower($filters['search']);
        $products = array_filter($products, function($product) use ($search) {
            $name = mb_strtolower($product['name'] ?? '');
            $desc = mb_strtolower($product['description'] ?? '');
            return mb_strpos($name, $search) !== false || mb_strpos($desc, $search) !== false;
        });
    }

    if (!empty($filters['price_min'])) {
        $products = array_filter($products, function($product) use ($filters) {
            return isset($product['price']) && $product['price'] >= $filters['price_min'];
        });
    }

    if (!empty($filters['price_max'])) {
        $products = array_filter($products, function($product) use ($filters) {
            return isset($product['price']) && $product['price'] <= $filters['price_max'];
        });
    }

    if (!isset($filters['include_inactive'])) {
        $products = array_filter($products, function($product) {
            return !isset($product['status']) || $product['status'] == 1;
        });
    }

    return array_values($products);
}

function getAllProducts() {
    return loadJsonData('products.json');
}

function getProductById($id) {
    if (empty($id)) return null;
    $products = loadJsonData('products.json');
    foreach ($products as $product) {
        if (isset($product['id']) && $product['id'] == $id) {
            return $product;
        }
    }
    return null;
}

function getFeaturedProducts($limit = 8) {
    $products = getProducts();
    if (empty($products)) return [];
    shuffle($products);
    return array_slice($products, 0, $limit);
}

function saveProduct($productData) {
    try {
        $products = loadJsonData('products.json');

        if (empty($productData['id'])) {
            $productData['id'] = 'prod_' . time() . '_' . uniqid();
            $productData['created_at'] = date('Y-m-d H:i:s');
        }

        $productData['updated_at'] = date('Y-m-d H:i:s');

        $productExists = false;
        foreach ($products as $key => $product) {
            if ($product['id'] == $productData['id']) {
                $products[$key] = $productData;
                $productExists = true;
                break;
            }
        }

        if (!$productExists) {
            $products[] = $productData;
        }

        if (saveJsonData('products.json', $products)) {
            return ['success' => true, 'product_id' => $productData['id']];
        } else {
            return ['success' => false, 'error' => 'Не удалось сохранить в файл'];
        }

    } catch (Exception $e) {
        error_log('Ошибка сохранения товара: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function deleteProduct($id) {
    try {
        $products = loadJsonData('products.json');
        $products = array_filter($products, function($product) use ($id) {
            return $product['id'] != $id;
        });
        return saveJsonData('products.json', array_values($products));
    } catch (Exception $e) {
        return false;
    }
}

// ========== КАТЕГОРИИ ==========

function getCategories() {
    return loadJsonData('categories.json');
}

function getCategoriesWithCount() {
    $categories = loadJsonData('categories.json');
    $products = getProducts();

    foreach ($categories as &$category) {
        $count = 0;
        foreach ($products as $product) {
            if (isset($product['category_id']) && $product['category_id'] == $category['id']) {
                $count++;
            }
        }
        $category['products_count'] = $count;
    }

    return $categories;
}

function getCategoryById($id) {
    if (!is_numeric($id) || $id <= 0) return null;
    $categories = loadJsonData('categories.json');
    foreach ($categories as $category) {
        if (isset($category['id']) && $category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

// ========== МЕГА КОРЗИНА (ПОЛНОСТЬЮ ПЕРЕПИСАННАЯ) ==========

/**
 * МЕГА функция инициализации корзины
 */
function initCart() {
    if (!initSession()) {
        return false;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        error_log('КОРЗИНА: Инициализирована новая корзина');
    }

    return true;
}

/**
 * МЕГА функция добавления товара в корзину
 */
function addToCart($productId, $quantity = 1) {
    error_log("КОРЗИНА: Попытка добавить товар ID=$productId, количество=$quantity");

    // Инициализация
    if (!initCart()) {
        error_log('КОРЗИНА: Ошибка инициализации');
        return false;
    }

    // Валидация параметров
    if (empty($productId) || !is_numeric($quantity) || $quantity <= 0) {
        error_log("КОРЗИНА: Некорректные параметры - productId: $productId, quantity: $quantity");
        return false;
    }

    $productId = strval($productId); // Используем строковый ID для консистентности
    $quantity = (int)$quantity;

    // Проверяем существование товара
    $product = getProductById($productId);
    if (!$product) {
        error_log("КОРЗИНА: Товар с ID $productId не найден");
        return false;
    }

    // Добавляем товар
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
        error_log("КОРЗИНА: Увеличено количество товара $productId до " . $_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
        error_log("КОРЗИНА: Добавлен новый товар $productId в количестве $quantity");
    }

    error_log('КОРЗИНА: Текущее состояние - ' . json_encode($_SESSION['cart']));
    return true;
}

/**
 * МЕГА функция получения содержимого корзины
 */
function getCartItems() {
    if (!initCart()) {
        return ['items' => [], 'total' => 0, 'count' => 0];
    }

    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    $total = 0;

    foreach ($cart as $productId => $quantity) {
        $product = getProductById($productId);
        if ($product && is_numeric($quantity) && $quantity > 0) {
            $item = $product;
            $item['quantity'] = (int)$quantity;
            $item['subtotal'] = (float)$product['price'] * (int)$quantity;
            $total += $item['subtotal'];
            $items[] = $item;
        }
    }

    return [
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ];
}

/**
 * МЕГА функция получения общей стоимости корзины
 */
function getCartTotal() {
    $cartData = getCartItems();
    return $cartData['total'];
}

/**
 * МЕГА функция получения количества товаров в корзине
 */
function getCartCount() {
    if (!initCart()) {
        return 0;
    }

    $cart = $_SESSION['cart'] ?? [];
    $count = array_sum($cart);
    error_log("КОРЗИНА: Общее количество товаров - $count");
    return $count;
}

/**
 * МЕГА функция обновления количества товара
 */
function updateCartItem($productId, $quantity) {
    if (!initCart()) {
        return false;
    }

    $productId = strval($productId);
    $quantity = (int)$quantity;

    if ($quantity > 0) {
        $_SESSION['cart'][$productId] = $quantity;
        error_log("КОРЗИНА: Обновлено количество товара $productId до $quantity");
    } else {
        unset($_SESSION['cart'][$productId]);
        error_log("КОРЗИНА: Удален товар $productId");
    }

    return true;
}

/**
 * МЕГА функция удаления товара из корзины
 */
function removeFromCart($productId) {
    if (!initCart()) {
        return false;
    }

    $productId = strval($productId);

    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        error_log("КОРЗИНА: Удален товар $productId");
        return true;
    }

    return false;
}

/**
 * МЕГА функция очистки корзины
 */
function clearCart() {
    if (!initCart()) {
        return false;
    }
    $_SESSION['cart'] = [];
    error_log('КОРЗИНА: Корзина очищена');
    return true;
}

// ========== ИЗБРАННОЕ (ПОЛНОСТЬЮ ПЕРЕПИСАННОЕ) ==========

/**
 * Инициализация избранного
 */
function initFavorites() {
    if (!initSession()) {
        return false;
    }

    if (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
        error_log('ИЗБРАННОЕ: Инициализировано новое избранное');
    }

    return true;
}

/**
 * Получение списка ID избранных товаров
 */
function getFavorites() {
    if (!initFavorites()) return [];
    return $_SESSION['favorites'] ?? [];
}

/**
 * Получение полных данных избранных товаров
 */
function getFavoritesItems() {
    if (!initFavorites()) {
        return [];
    }

    $favorites = $_SESSION['favorites'] ?? [];
    $items = [];

    foreach ($favorites as $productId) {
        $product = getProductById($productId);
        if ($product) {
            $items[] = $product;
        }
    }

    return $items;
}

/**
 * Добавление товара в избранное
 */
function addToFavorites($productId) {
    if (!initFavorites()) return false;

    $productId = strval($productId);

    // Проверяем существование товара
    $product = getProductById($productId);
    if (!$product) {
        error_log("ИЗБРАННОЕ: Товар с ID $productId не найден");
        return false;
    }

    if (!in_array($productId, $_SESSION['favorites'])) {
        $_SESSION['favorites'][] = $productId;
        error_log("ИЗБРАННОЕ: Добавлен товар $productId");
        return true;
    }

    return false; // Уже в избранном
}

/**
 * Удаление товара из избранного
 */
function removeFromFavorites($productId) {
    if (!initFavorites()) return false;

    $productId = strval($productId);
    $key = array_search($productId, $_SESSION['favorites']);

    if ($key !== false) {
        unset($_SESSION['favorites'][$key]);
        $_SESSION['favorites'] = array_values($_SESSION['favorites']); // Переиндексация
        error_log("ИЗБРАННОЕ: Удален товар $productId");
        return true;
    }

    return false;
}

/**
 * Получение количества товаров в избранном
 */
function getFavoritesCount() {
    if (!initFavorites()) return 0;
    $count = count($_SESSION['favorites'] ?? []);
    error_log("ИЗБРАННОЕ: Количество товаров - $count");
    return $count;
}

/**
 * Проверка, находится ли товар в избранном
 */
function isInFavorites($productId) {
    if (!initFavorites()) return false;
    return in_array(strval($productId), $_SESSION['favorites'] ?? []);
}

/**
 * Очистка избранного
 */
function clearFavorites() {
    if (!initFavorites()) return false;
    $_SESSION['favorites'] = [];
    error_log('ИЗБРАННОЕ: Избранное очищено');
    return true;
}

// ========== ОТЗЫВЫ (ИСПРАВЛЕННЫЕ) ==========

function getAllReviews() {
    return loadJsonData('reviews.json');
}

function getReviewById($id) {
    $reviews = getAllReviews();
    foreach ($reviews as $review) {
        if ($review['id'] === $id) {
            return $review;
        }
    }
    return null;
}

function getProductReviews($productId) {
    $reviews = loadJsonData('reviews.json');
    $productReviews = array_filter($reviews, function($review) use ($productId) {
        return isset($review['product_id']) && $review['product_id'] == $productId && 
               isset($review['status']) && $review['status'] === 'approved';
    });
    return array_values($productReviews);
}

function getProductRating($productId) {
    $reviews = getProductReviews($productId);
    if (empty($reviews)) {
        return ['average' => 0, 'count' => 0];
    }

    $ratings = array_column($reviews, 'rating');
    $validRatings = array_filter($ratings, function($rating) {
        return is_numeric($rating) && $rating > 0 && $rating <= 5;
    });

    if (empty($validRatings)) {
        return ['average' => 0, 'count' => 0];
    }

    $average = array_sum($validRatings) / count($validRatings);

    return [
        'average' => round($average, 1),
        'count' => count($validRatings)
    ];
}

function saveReview($reviewData) {
    try {
        // Валидация данных
        if (empty($reviewData['product_id']) || empty($reviewData['customer_name']) || 
            empty($reviewData['comment']) || !isset($reviewData['rating'])) {
            return ['success' => false, 'message' => 'Заполните все обязательные поля'];
        }

        if (!is_numeric($reviewData['rating']) || $reviewData['rating'] < 1 || $reviewData['rating'] > 5) {
            return ['success' => false, 'message' => 'Некорректный рейтинг'];
        }

        // Проверяем существование товара
        $product = getProductById($reviewData['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $reviews = getAllReviews();

        // Генерируем ID если его нет
        if (empty($reviewData['id'])) {
            $reviewData['id'] = generateUniqueId();
            $reviewData['created_at'] = date('Y-m-d H:i:s');
        }

        $reviewData['updated_at'] = date('Y-m-d H:i:s');

        // Устанавливаем статус по умолчанию
        if (!isset($reviewData['status'])) {
            $reviewData['status'] = 'pending';
        }

        $exists = false;
        foreach ($reviews as $key => $review) {
            if ($review['id'] === $reviewData['id']) {
                $reviews[$key] = $reviewData;
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $reviews[] = $reviewData;
        }

        if (saveJsonData('reviews.json', $reviews)) {
            return ['success' => true, 'message' => 'Отзыв сохранен успешно'];
        } else {
            return ['success' => false, 'message' => 'Ошибка сохранения отзыва'];
        }
    } catch (Exception $e) {
        error_log('Ошибка сохранения отзыва: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Внутренняя ошибка сервера'];
    }
}

function updateReviewStatus($id, $status) {
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        return false;
    }

    $reviews = getAllReviews();
    foreach ($reviews as &$review) {
        if ($review['id'] === $id) {
            $review['status'] = $status;
            $review['updated_at'] = date('Y-m-d H:i:s');
            return saveJsonData('reviews.json', $reviews);
        }
    }
    return false;
}

function deleteReview($id) {
    $reviews = getAllReviews();
    $reviews = array_filter($reviews, function($review) use ($id) {
        return $review['id'] !== $id;
    });
    return saveJsonData('reviews.json', array_values($reviews));
}

function generateUniqueId() {
    return 'review_' . time() . '_' . uniqid();
}

function getProductLabels($productId) {
    $rating = getProductRating($productId);
    $labels = [];

    if ($rating['count'] > 0) {
        if ($rating['average'] >= 4.5 && $rating['count'] >= 5) {
            $labels[] = ['text' => 'ПРЕМИУМ', 'class' => 'bg-gradient-danger'];
        } elseif ($rating['average'] >= 4.0 && $rating['count'] >= 3) {
            $labels[] = ['text' => 'ХОРОШИЙ ТОВАР', 'class' => 'bg-gradient-success'];
        } elseif ($rating['count'] >= 10) {
            $labels[] = ['text' => 'ПОПУЛЯРНЫЙ', 'class' => 'bg-gradient-info'];
        }
    }

    $product = getProductById($productId);
    if ($product && isset($product['is_new']) && $product['is_new']) {
        $labels[] = ['text' => 'НОВИНКА', 'class' => 'bg-gradient-warning'];
    }

    if ($product && isset($product['is_sale']) && $product['is_sale']) {
        $labels[] = ['text' => 'АКЦИЯ', 'class' => 'bg-gradient-danger'];
    }

    return $labels;
}

// ========== ЗАКАЗЫ ==========

function saveOrder($orderData) {
    $orders = loadJsonData('orders.json');

    $order = [
        'id' => time() . '_' . uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'status' => 'new',
        'customer' => $orderData['customer'] ?? [],
        'items' => $orderData['items'] ?? [],
        'total' => $orderData['total'] ?? 0
    ];

    $orders[] = $order;
    if (saveJsonData('orders.json', $orders)) {
        return $order['id'];
    }
    return false;
}

function getOrders() {
    return loadJsonData('orders.json');
}

function updateOrderStatus($id, $status) {
    $orders = loadJsonData('orders.json');
    foreach ($orders as &$order) {
        if ($order['id'] == $id) {
            $order['status'] = $status;
            $order['updated_at'] = date('Y-m-d H:i:s');
            return saveJsonData('orders.json', $orders);
        }
    }
    return false;
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

function formatPrice($price) {
    if (!is_numeric($price)) return '0 ₽';
    return number_format((float)$price, 0, ',', ' ') . ' ₽';
}

function getDashboardStats() {
    $products = getAllProducts();
    $orders = getOrders();
    $categories = getCategories();

    $activeProducts = array_filter($products, function($p) {
        return !isset($p['status']) || $p['status'] == 1;
    });

    return [
        'total_products' => count($activeProducts),
        'total_categories' => count($categories),
        'total_orders' => count($orders),
        'today_orders' => 0,
        'month_orders' => 0,
        'total_revenue' => 0,
        'month_revenue' => 0
    ];
}

function getSiteSettings() {
    return loadJsonData('settings.json');
}

function saveSiteSettings($settingsData) {
    return saveJsonData('settings.json', $settingsData);
}

function initializeDefaultData() {
    $categories = getCategories();
    if (empty($categories)) {
        $defaultCategories = [
            ['id' => 1, 'name' => 'Растения', 'slug' => 'plants', 'description' => 'Аквариумные растения', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'name' => 'Рыбки', 'slug' => 'fish', 'description' => 'Аквариумные рыбки', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3, 'name' => 'Оборудование', 'slug' => 'equipment', 'description' => 'Аквариумное оборудование', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 4, 'name' => 'Декор', 'slug' => 'decoration', 'description' => 'Декорации для аквариума', 'created_at' => date('Y-m-d H:i:s')]
        ];
        saveJsonData('categories.json', $defaultCategories);
    }

    $settings = getSiteSettings();
    if (empty($settings)) {
        $defaultSettings = [
            'site_name' => 'АкваСбор',
            'site_description' => 'Интернет-магазин аквариумных товаров',
            'contact_email' => 'info@akvasbor.ru',
            'contact_phone' => '+7 (000) 000-00-00',
            'currency' => 'RUB',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        saveSiteSettings($defaultSettings);
    }
}

// Автоматическая инициализация
if (!defined('SKIP_AUTO_INIT')) {
    initializeDefaultData();
}

?>
