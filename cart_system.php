<?php
/**
 * АкваСбор - Система корзины v2.0 - РАСШИРЕННАЯ
 * Интеграция с data.php и вашими стилями
 */

session_start();
require_once 'data.php';

// === ИНИЦИАЛИЗАЦИЯ КОРЗИНЫ ===
if (!isset($_SESSION['cart_advanced'])) {
    $_SESSION['cart_advanced'] = [
        'items' => [],
        'total_amount' => 0,
        'total_items' => 0,
        'total_weight' => 0,
        'discount' => 0,
        'discount_code' => '',
        'discount_amount' => 0,
        'subtotal' => 0,
        'final_amount' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// === ОБРАБОТКА ДЕЙСТВИЙ КОРЗИНЫ ===
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action) {
    header('Content-Type: application/json');

    switch ($action) {
        case 'add':
            $result = addToCartAdvanced($_POST['product_id'], $_POST['quantity'] ?? 1);
            echo json_encode($result);
            break;

        case 'update':
            $result = updateCartItemAdvanced($_POST['product_id'], $_POST['quantity']);
            echo json_encode($result);
            break;

        case 'remove':
            $result = removeFromCartAdvanced($_POST['product_id']);
            echo json_encode($result);
            break;

        case 'clear':
            $result = clearCartAdvanced();
            echo json_encode($result);
            break;

        case 'get_cart':
            $result = getCartDataAdvanced();
            echo json_encode($result);
            break;

        case 'apply_discount':
            $result = applyDiscountAdvanced($_POST['code']);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
    }
    exit;
}

// === ФУНКЦИИ КОРЗИНЫ ===

function addToCartAdvanced($productId, $quantity = 1) {
    $product = getProductById($productId);

    if (!$product) {
        return ['success' => false, 'message' => 'Товар не найден'];
    }

    if (!$product['is_active']) {
        return ['success' => false, 'message' => 'Товар недоступен'];
    }

    if ($product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Недостаточно товара на складе'];
    }

    $cartItemId = 'item_' . $productId;

    // Если товар уже в корзине - увеличиваем количество
    if (isset($_SESSION['cart_advanced']['items'][$cartItemId])) {
        $newQuantity = $_SESSION['cart_advanced']['items'][$cartItemId]['quantity'] + $quantity;

        if ($product['stock'] < $newQuantity) {
            return ['success' => false, 'message' => 'Недостаточно товара на складе'];
        }

        $_SESSION['cart_advanced']['items'][$cartItemId]['quantity'] = $newQuantity;
    } else {
        // Добавляем новый товар
        $_SESSION['cart_advanced']['items'][$cartItemId] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $product['price'],
            'old_price' => $product['old_price'],
            'sku' => $product['sku'],
            'category' => $product['category'],
            'quantity' => $quantity,
            'weight' => $product['weight'] ?? 0,
            'image' => !empty($product['images']) ? $product['images'][0] : '',
            'added_at' => date('Y-m-d H:i:s')
        ];
    }

    updateCartTotalsAdvanced();

    return [
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'cart' => getCartSummaryAdvanced()
    ];
}

function updateCartItemAdvanced($productId, $quantity) {
    $cartItemId = 'item_' . $productId;

    if (!isset($_SESSION['cart_advanced']['items'][$cartItemId])) {
        return ['success' => false, 'message' => 'Товар не найден в корзине'];
    }

    if ($quantity <= 0) {
        return removeFromCartAdvanced($productId);
    }

    $product = getProductById($productId);

    if ($product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Недостаточно товара на складе'];
    }

    $_SESSION['cart_advanced']['items'][$cartItemId]['quantity'] = $quantity;
    updateCartTotalsAdvanced();

    return [
        'success' => true,
        'message' => 'Количество обновлено',
        'cart' => getCartSummaryAdvanced()
    ];
}

function removeFromCartAdvanced($productId) {
    $cartItemId = 'item_' . $productId;

    if (isset($_SESSION['cart_advanced']['items'][$cartItemId])) {
        unset($_SESSION['cart_advanced']['items'][$cartItemId]);
        updateCartTotalsAdvanced();

        return [
            'success' => true,
            'message' => 'Товар удален из корзины',
            'cart' => getCartSummaryAdvanced()
        ];
    }

    return ['success' => false, 'message' => 'Товар не найден в корзине'];
}

function clearCartAdvanced() {
    $_SESSION['cart_advanced']['items'] = [];
    $_SESSION['cart_advanced']['discount'] = 0;
    $_SESSION['cart_advanced']['discount_code'] = '';
    updateCartTotalsAdvanced();

    return [
        'success' => true,
        'message' => 'Корзина очищена',
        'cart' => getCartSummaryAdvanced()
    ];
}

function updateCartTotalsAdvanced() {
    $totalAmount = 0;
    $totalItems = 0;
    $totalWeight = 0;

    foreach ($_SESSION['cart_advanced']['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $totalAmount += $itemTotal;
        $totalItems += $item['quantity'];
        $totalWeight += ($item['weight'] ?? 0) * $item['quantity'];
    }

    $_SESSION['cart_advanced']['total_amount'] = $totalAmount;
    $_SESSION['cart_advanced']['total_items'] = $totalItems;
    $_SESSION['cart_advanced']['total_weight'] = $totalWeight;
    $_SESSION['cart_advanced']['subtotal'] = $totalAmount;
    $_SESSION['cart_advanced']['discount_amount'] = $totalAmount * ($_SESSION['cart_advanced']['discount'] / 100);
    $_SESSION['cart_advanced']['final_amount'] = $totalAmount - $_SESSION['cart_advanced']['discount_amount'];
}

function getCartDataAdvanced() {
    updateCartTotalsAdvanced();
    return [
        'success' => true,
        'cart' => $_SESSION['cart_advanced'],
        'summary' => getCartSummaryAdvanced()
    ];
}

function getCartSummaryAdvanced() {
    return [
        'total_items' => $_SESSION['cart_advanced']['total_items'] ?? 0,
        'total_amount' => $_SESSION['cart_advanced']['total_amount'] ?? 0,
        'final_amount' => $_SESSION['cart_advanced']['final_amount'] ?? 0,
        'discount' => $_SESSION['cart_advanced']['discount'] ?? 0,
        'items_count' => count($_SESSION['cart_advanced']['items'] ?? [])
    ];
}

function applyDiscountAdvanced($code) {
    $discountCodes = [
        'FIRST10' => ['percent' => 10, 'min_amount' => 1000, 'description' => 'Скидка 10% на первый заказ'],
        'SUMMER20' => ['percent' => 20, 'min_amount' => 2000, 'description' => 'Летняя скидка 20%'],
        'AQUA15' => ['percent' => 15, 'min_amount' => 1500, 'description' => 'Скидка для аквариумистов 15%'],
        'WELCOME5' => ['percent' => 5, 'min_amount' => 500, 'description' => 'Скидка новичкам 5%']
    ];

    $code = strtoupper(trim($code));

    if (!isset($discountCodes[$code])) {
        return ['success' => false, 'message' => 'Промокод не действителен'];
    }

    $discount = $discountCodes[$code];

    if ($_SESSION['cart_advanced']['total_amount'] < $discount['min_amount']) {
        return [
            'success' => false,
            'message' => "Минимальная сумма для промокода: {$discount['min_amount']} ₽"
        ];
    }

    $_SESSION['cart_advanced']['discount'] = $discount['percent'];
    $_SESSION['cart_advanced']['discount_code'] = $code;
    updateCartTotalsAdvanced();

    return [
        'success' => true,
        'message' => "Промокод применен! Скидка: {$discount['percent']}%",
        'cart' => getCartSummaryAdvanced()
    ];
}

// === ФУНКЦИИ ДОСТАВКИ ===

function getShippingCostAdvanced($weight = 0, $amount = 0) {
    $settings = getSiteSettings();

    if ($amount >= $settings['free_shipping_from']) {
        return 0;
    }

    // Базовая стоимость доставки
    $baseCost = 300;

    // Дополнительная плата за вес свыше 2 кг
    if ($weight > 2000) {
        $extraWeight = ceil(($weight - 2000) / 1000); // за каждый кг свыше 2кг
        $baseCost += $extraWeight * 50;
    }

    return $baseCost;
}

function getPaymentMethodsAdvanced() {
    return [
        'card' => [
            'id' => 'card',
            'name' => 'Банковская карта',
            'description' => 'Visa, MasterCard, МИР',
            'icon' => 'fas fa-credit-card',
            'fee' => 0,
            'active' => true
        ],
        'sbp' => [
            'id' => 'sbp',
            'name' => 'Система быстрых платежей',
            'description' => 'Оплата через СБП',
            'icon' => 'fas fa-mobile-alt',
            'fee' => 0,
            'active' => true
        ],
        'yoomoney' => [
            'id' => 'yoomoney',
            'name' => 'ЮMoney',
            'description' => 'Электронный кошелек',
            'icon' => 'fas fa-wallet',
            'fee' => 2.5,
            'active' => true
        ],
        'cash' => [
            'id' => 'cash',
            'name' => 'Наличными при получении',
            'description' => 'Оплата курьеру или в пункте выдачи',
            'icon' => 'fas fa-money-bill-wave',
            'fee' => 0,
            'active' => true
        ]
    ];
}

function getDeliveryMethodsAdvanced() {
    return [
        'pickup' => [
            'id' => 'pickup',
            'name' => 'Самовывоз',
            'description' => 'Забрать в нашем магазине',
            'cost' => 0,
            'time' => '1-2 часа',
            'icon' => 'fas fa-store',
            'active' => true
        ],
        'courier' => [
            'id' => 'courier',
            'name' => 'Курьерская доставка',
            'description' => 'Доставка по городу',
            'cost' => 300,
            'time' => '1-3 дня',
            'icon' => 'fas fa-shipping-fast',
            'active' => true
        ],
        'cdek' => [
            'id' => 'cdek',
            'name' => 'СДЭК',
            'description' => 'До пункта выдачи или курьером',
            'cost' => 250,
            'time' => '2-5 дней',
            'icon' => 'fas fa-truck',
            'active' => true
        ],
        'post' => [
            'id' => 'post',
            'name' => 'Почта России',
            'description' => 'До почтового отделения',
            'cost' => 200,
            'time' => '5-14 дней',
            'icon' => 'fas fa-envelope',
            'active' => true
        ]
    ];
}

// === СОВМЕСТИМОСТЬ СО СТАРОЙ КОРЗИНОЙ ===

function getCartTotalAdvanced() {
    if (empty($_SESSION['cart_advanced']['items'])) return '0';

    updateCartTotalsAdvanced();
    return number_format($_SESSION['cart_advanced']['final_amount'], 0, '', ' ');
}

function getCartCountAdvanced() {
    return $_SESSION['cart_advanced']['total_items'] ?? 0;
}

// Миграция со старой корзины
function migrateOldCart() {
    if (!empty($_SESSION['cart']) && empty($_SESSION['cart_advanced']['items'])) {
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            addToCartAdvanced($productId, $quantity);
        }
        // Очищаем старую корзину после миграции
        $_SESSION['cart'] = [];
    }
}

// Автоматически мигрируем при подключении файла
migrateOldCart();

?>