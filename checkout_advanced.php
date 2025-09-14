<?php
/**
 * АкваСбор - Расширенное оформление заказа v2.0
 * Используем ваши стили + мой функционал
 */

session_start();
require_once 'data.php';
require_once 'cart_system.php';

// Проверяем наличие товаров в корзине
if (empty($_SESSION['cart_advanced']['items'])) {
    header('Location: ?page=cart');
    exit;
}

// Обработка оформления заказа
if ($_POST['action'] === 'place_order') {
    $result = processOrderAdvanced($_POST);

    if ($result['success']) {
        // Сохраняем заказ в систему
        $saveResult = saveOrder($result['order']);

        // Очищаем корзину после успешного оформления
        clearCartAdvanced();

        // Перенаправляем на страницу успеха
        $_SESSION['last_order'] = $result['order']['order_number'];
        $_SESSION['message'] = [
            'text' => 'Заказ ' . $result['order']['order_number'] . ' успешно оформлен! Мы свяжемся с вами в ближайшее время.',
            'type' => 'success'
        ];
        header('Location: ?page=order_success&order=' . $result['order']['order_number']);
        exit;
    } else {
        $orderError = $result['message'];
    }
}

$cart = $_SESSION['cart_advanced'];
$paymentMethods = getPaymentMethodsAdvanced();
$deliveryMethods = getDeliveryMethodsAdvanced();
$settings = getSiteSettings();

function processOrderAdvanced($data) {
    global $cart;

    // Валидация данных
    $required = ['customer_name', 'customer_email', 'customer_phone', 'delivery_method', 'payment_method'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Поле '{$field}' обязательно для заполнения"];
        }
    }

    // Проверяем email
    if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Некорректный email адрес'];
    }

    // Проверяем телефон (базовая проверка)
    if (!preg_match('/[\d\+\-$$\s]{10,}/', $data['customer_phone'])) {
        return ['success' => false, 'message' => 'Некорректный номер телефона'];
    }

    // Генерируем номер заказа
    $orderNumber = 'AQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Рассчитываем финальную сумму
    $shippingCost = getShippingCostAdvanced($cart['total_weight'] ?? 0, $cart['final_amount']);
    $totalAmount = $cart['final_amount'] + $shippingCost;

    // Создаем заказ
    $order = [
        'order_number' => $orderNumber,
        'customer_name' => $data['customer_name'],
        'customer_email' => $data['customer_email'],
        'customer_phone' => $data['customer_phone'],
        'delivery_method' => $data['delivery_method'],
        'delivery_address' => $data['delivery_address'] ?? '',
        'payment_method' => $data['payment_method'],
        'items' => $cart['items'],
        'items_count' => count($cart['items']),
        'subtotal' => $cart['total_amount'],
        'discount' => $cart['discount'] ?? 0,
        'discount_code' => $cart['discount_code'] ?? '',
        'discount_amount' => $cart['discount_amount'] ?? 0,
        'shipping_cost' => $shippingCost,
        'total_amount' => $totalAmount,
        'total_weight' => $cart['total_weight'] ?? 0,
        'notes' => $data['notes'] ?? '',
        'status' => 'new',
        'status_label' => 'Новый',
        'created_at' => date('Y-m-d H:i:s')
    ];

    return ['success' => true, 'order' => $order];
}

// Подключаем header и navigation из index.php
$page = 'checkout';
$pageData = [
    'title' => 'Оформление заказа',
    'description' => 'Оформление заказа в интернет-магазине АкваСбор'
];
?>
<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?= htmlspecialchars($pageData['title']) ?> - <?= getSiteSettings()['site_name'] ?></title>
    <meta name='description' content='<?= htmlspecialchars($pageData['description']) ?>'>

    <!-- Используем ваши стили из index.php -->
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <link rel='icon' href='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><text y=\'.9em\' font-size=\'90\'>🐠</text></svg>'>

    <style>
        /* Подключаем ваши CSS переменные и базовые стили */
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

        /* Кнопки из вашего дизайна */
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

        .btn-lg {
            padding: 16px 32px;
            font-size: 16px;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* Ваши карточки */
        .feature-card {
            background: var(--bg-primary);
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Заголовки страницы */
        .page-header {
            background: var(--bg-secondary);
            padding: 40px 0;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5em;
            margin-bottom: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Дополнительные стили для checkout */
        .checkout-steps {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--bg-secondary);
            border-radius: 20px;
            font-size: 14px;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 40px 0;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

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
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        .radio-group {
            display: grid;
            gap: 15px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            cursor: pointer;
            transition: var(--transition);
            background: var(--bg-primary);
        }

        .radio-option:hover {
            border-color: var(--primary-color);
            background: rgba(46, 204, 113, 0.05);
        }

        .radio-option.selected {
            border-color: var(--primary-color);
            background: rgba(46, 204, 113, 0.1);
        }

        .radio-option input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
        }

        .radio-info {
            flex: 1;
        }

        .radio-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .radio-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .radio-cost {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-summary {
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
            color: var(--text-primary);
        }

        .item-quantity {
            font-size: 12px;
            color: var(--text-muted);
        }

        .item-total {
            font-weight: 600;
            color: var(--primary-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .summary-row.total {
            border-top: 2px solid var(--border-color);
            padding-top: 15px;
            margin-top: 15px;
            font-weight: 700;
            font-size: 18px;
            color: var(--primary-color);
        }

        .error-message {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            padding: 15px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--danger-color);
            margin-bottom: 20px;
        }

        .security-info {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .security-info h4 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .checkout-steps {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<!-- Шапка из вашего дизайна -->
<header class='page-header'>
    <div class='container'>
        <h1>
            <i class='fas fa-credit-card'></i>
            Оформление заказа
        </h1>
        <p>Заполните информацию для доставки и оплаты</p>

        <div class='checkout-steps'>
            <div class='step active'>
                <i class='fas fa-shopping-cart'></i>
                Корзина
            </div>
            <div class='step active'>
                <i class='fas fa-credit-card'></i>
                Оформление
            </div>
            <div class='step'>
                <i class='fas fa-check-circle'></i>
                Подтверждение
            </div>
        </div>
    </div>
</header>

<div class='container'>
    <?php if (isset($orderError)): ?>
        <div class='error-message'>
            <i class='fas fa-exclamation-triangle'></i>
            <?= htmlspecialchars($orderError) ?>
        </div>
    <?php endif; ?>

    <div class='checkout-content'>
        <!-- Форма оформления заказа -->
        <div>
            <form method='POST' id='checkoutForm'>
                <input type='hidden' name='action' value='place_order'>

                <!-- Контактная информация -->
                <div class='feature-card form-section'>
                    <h3>
                        <i class='fas fa-user'></i>
                        Контактная информация
                    </h3>

                    <div class='form-group'>
                        <label class='form-label'>Имя и фамилия *</label>
                        <input type='text' name='customer_name' class='form-input' 
                               placeholder='Введите ваше имя' required>
                    </div>

                    <div class='form-grid'>
                        <div class='form-group'>
                            <label class='form-label'>Email *</label>
                            <input type='email' name='customer_email' class='form-input' 
                                   placeholder='email@example.com' required>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Телефон *</label>
                            <input type='tel' name='customer_phone' class='form-input' 
                                   placeholder='+7 (999) 123-45-67' required>
                        </div>
                    </div>
                </div>

                <!-- Способ доставки -->
                <div class='feature-card form-section'>
                    <h3>
                        <i class='fas fa-shipping-fast'></i>
                        Способ доставки
                    </h3>

                    <div class='radio-group'>
                        <?php foreach ($deliveryMethods as $method): ?>
                            <?php if (!$method['active']) continue; ?>
                            <label class='radio-option' onclick='selectDelivery("<?= $method['id'] ?>")'>
                                <input type='radio' name='delivery_method' value='<?= $method['id'] ?>' required>
                                <i class='<?= $method['icon'] ?>' style='font-size: 24px; color: var(--primary-color); margin-right: 15px;'></i>
                                <div class='radio-info'>
                                    <div class='radio-title'><?= $method['name'] ?></div>
                                    <div class='radio-description'><?= $method['description'] ?></div>
                                    <div class='radio-cost'>
                                        <?= $method['cost'] == 0 ? 'Бесплатно' : number_format($method['cost'], 0, '', ' ') . ' ₽' ?>
                                        • <?= $method['time'] ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class='form-group' id='addressField' style='display: none; margin-top: 20px;'>
                        <label class='form-label'>Адрес доставки *</label>
                        <textarea name='delivery_address' class='form-input' rows='3' 
                                  placeholder='Укажите точный адрес доставки'></textarea>
                    </div>
                </div>

                <!-- Способ оплаты -->
                <div class='feature-card form-section'>
                    <h3>
                        <i class='fas fa-credit-card'></i>
                        Способ оплаты
                    </h3>

                    <div class='radio-group'>
                        <?php foreach ($paymentMethods as $method): ?>
                            <?php if (!$method['active']) continue; ?>
                            <label class='radio-option'>
                                <input type='radio' name='payment_method' value='<?= $method['id'] ?>' required>
                                <i class='<?= $method['icon'] ?>' style='font-size: 24px; color: var(--primary-color); margin-right: 15px;'></i>
                                <div class='radio-info'>
                                    <div class='radio-title'><?= $method['name'] ?></div>
                                    <div class='radio-description'><?= $method['description'] ?></div>
                                    <?php if ($method['fee'] > 0): ?>
                                        <div class='radio-cost'>Комиссия: <?= $method['fee'] ?>%</div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Комментарий к заказу -->
                <div class='feature-card form-section'>
                    <h3>
                        <i class='fas fa-comment'></i>
                        Комментарий к заказу
                    </h3>

                    <div class='form-group'>
                        <label class='form-label'>Дополнительная информация</label>
                        <textarea name='notes' class='form-input' rows='3' 
                                  placeholder='Укажите особые пожелания к заказу (необязательно)'></textarea>
                    </div>
                </div>

                <!-- Кнопка оформления -->
                <button type='submit' class='btn btn-primary btn-lg btn-block'>
                    <i class='fas fa-check'></i>
                    Оформить заказ
                </button>

                <a href='?page=cart' class='btn btn-outline btn-lg btn-block' style='margin-top: 15px;'>
                    <i class='fas fa-arrow-left'></i>
                    Вернуться в корзину
                </a>
            </form>
        </div>

        <!-- Итоги заказа -->
        <div class='feature-card order-summary'>
            <h3 class='summary-title'>
                <i class='fas fa-receipt'></i>
                Ваш заказ
            </h3>

            <!-- Товары -->
            <div class='order-items'>
                <?php foreach ($cart['items'] as $item): ?>
                    <div class='order-item'>
                        <div class='item-details'>
                            <div class='item-name'><?= htmlspecialchars($item['name']) ?></div>
                            <div class='item-quantity'><?= $item['quantity'] ?> × <?= number_format($item['price'], 0, '', ' ') ?> ₽</div>
                        </div>
                        <div class='item-total'>
                            <?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Итоги -->
            <div class='summary-row'>
                <span>Товары (<?= $cart['total_items'] ?> шт.):</span>
                <span><?= number_format($cart['total_amount'], 0, '', ' ') ?> ₽</span>
            </div>

            <?php if ($cart['discount'] > 0): ?>
                <div class='summary-row' style='color: var(--primary-color);'>
                    <span>Скидка (<?= $cart['discount'] ?>%):</span>
                    <span>-<?= number_format($cart['discount_amount'], 0, '', ' ') ?> ₽</span>
                </div>
            <?php endif; ?>

            <div class='summary-row'>
                <span>Доставка:</span>
                <span id='deliveryCost'>Рассчитается</span>
            </div>

            <div class='summary-row total'>
                <span>К оплате:</span>
                <span id='totalAmount'><?= number_format($cart['final_amount'], 0, '', ' ') ?> ₽</span>
            </div>

            <!-- Информация о безопасности -->
            <div class='security-info'>
                <h4><i class='fas fa-shield-alt'></i> Безопасность покупок</h4>
                <ul style='list-style: none; padding: 0;'>
                    <li style='margin-bottom: 5px;'>
                        <i class='fas fa-check' style='color: var(--primary-color); margin-right: 8px;'></i>
                        SSL шифрование данных
                    </li>
                    <li style='margin-bottom: 5px;'>
                        <i class='fas fa-check' style='color: var(--primary-color); margin-right: 8px;'></i>
                        Защита персональной информации
                    </li>
                    <li>
                        <i class='fas fa-check' style='color: var(--primary-color); margin-right: 8px;'></i>
                        Гарантия возврата товара
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    function selectDelivery(methodId) {
        // Убираем выделение у всех вариантов доставки
        document.querySelectorAll('.radio-option').forEach(option => {
            if (option.querySelector('input[name="delivery_method"]')) {
                option.classList.remove('selected');
            }
        });

        // Выделяем выбранный вариант
        event.currentTarget.classList.add('selected');

        // Показываем поле адреса для курьерской доставки
        const addressField = document.getElementById('addressField');
        if (methodId === 'courier' || methodId === 'cdek') {
            addressField.style.display = 'block';
            addressField.querySelector('textarea').required = true;
        } else {
            addressField.style.display = 'none';
            addressField.querySelector('textarea').required = false;
        }

        updateDeliveryCost(methodId);
    }

    function updateDeliveryCost(methodId) {
        const deliveryMethods = <?= json_encode($deliveryMethods) ?>;
        const method = deliveryMethods[methodId];
        const cartAmount = <?= $cart['final_amount'] ?>;
        const freeShippingFrom = <?= $settings['free_shipping_from'] ?>;

        let cost = method.cost;

        // Проверяем бесплатную доставку
        if (cartAmount >= freeShippingFrom && (methodId === 'courier' || methodId === 'cdek')) {
            cost = 0;
        }

        const deliveryCostElement = document.getElementById('deliveryCost');
        const totalElement = document.getElementById('totalAmount');

        if (cost === 0) {
            deliveryCostElement.textContent = 'Бесплатно';
            deliveryCostElement.style.color = 'var(--primary-color)';
        } else {
            deliveryCostElement.textContent = cost.toLocaleString() + ' ₽';
            deliveryCostElement.style.color = '';
        }

        // Обновляем общую сумму
        const newTotal = cartAmount + cost;
        totalElement.textContent = newTotal.toLocaleString() + ' ₽';
    }

    // Выделение выбранного способа оплаты
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.radio-option').forEach(option => {
                    if (option.querySelector('input[name="payment_method"]')) {
                        option.classList.remove('selected');
                    }
                });
                this.closest('.radio-option').classList.add('selected');
            });
        });
    });

    // Обработка отправки формы
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Оформляем заказ...';
    });
</script>

</body>
</html>