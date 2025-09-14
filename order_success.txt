<?php
/**
 * АкваСбор - Успешное оформление заказа v1.0
 */

session_start();
require_once 'data.php';

$orderId = $_GET['order_id'] ?? '';

if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Находим заказ в сессии (в реальном проекте - из базы данных)
$order = null;
if (isset($_SESSION['orders'])) {
    foreach ($_SESSION['orders'] as $sessionOrder) {
        if ($sessionOrder['order_number'] === $orderId) {
            $order = $sessionOrder;
            break;
        }
    }
}

if (!$order) {
    header('Location: index.php');
    exit;
}

$settings = getSiteSettings();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ успешно оформлен - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --success-color: #2ecc71;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --text-muted: #95a5a6;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --border-color: #dee2e6;
            --border-radius: 8px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
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
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .success-card {
            background: var(--bg-primary);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            font-size: 80px;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .order-number {
            font-size: 20px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .success-message {
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .order-details {
            background: var(--bg-primary);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }

        .details-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
        }

        .detail-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 600;
            font-size: 14px;
        }

        .order-items {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .items-header {
            background: var(--bg-secondary);
            padding: 12px 16px;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .item-details {
            font-size: 12px;
            color: var(--text-muted);
        }

        .item-total {
            font-weight: 600;
            color: var(--success-color);
        }

        .order-summary {
            background: var(--bg-secondary);
            padding: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            color: var(--success-color);
            border-top: 1px solid var(--border-color);
            padding-top: 12px;
            margin-top: 12px;
        }

        .actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
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

        .next-steps {
            background: var(--bg-primary);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .steps-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .step-description {
            font-size: 14px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Карточка успеха -->
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Заказ успешно оформлен!</h1>
            <div class="order-number">
                Номер заказа: <?= htmlspecialchars($order['order_number']) ?>
            </div>
            <div class="success-message">
                Спасибо за покупку! Мы получили ваш заказ и уже начали его обработку.<br>
                В ближайшее время с вами свяжется наш менеджер для подтверждения деталей.
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    На главную
                </a>
                <a href="index.php?page=catalog" class="btn btn-outline">
                    <i class="fas fa-fish"></i>
                    Продолжить покупки
                </a>
            </div>
        </div>

        <!-- Детали заказа -->
        <div class="order-details">
            <h2 class="details-title">
                <i class="fas fa-receipt"></i>
                Детали заказа
            </h2>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Дата заказа</div>
                    <div class="detail-value"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Статус</div>
                    <div class="detail-value" style="color: var(--info-color);">Новый</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Получатель</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Телефон</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_phone']) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_email']) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Способ доставки</div>
                    <div class="detail-value">
                        <?php
                        $deliveryMethods = getDeliveryMethods();
                        echo $deliveryMethods[$order['delivery_method']]['name'] ?? $order['delivery_method'];
                        ?>
                    </div>
                </div>
            </div>

            <!-- Товары в заказе -->
            <div class="order-items">
                <div class="items-header">
                    Товары в заказе (<?= count($order['items']) ?>)
                </div>

                <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-details">
                                Артикул: <?= $item['sku'] ?> | <?= $item['quantity'] ?> × <?= number_format($item['price'], 0, '', ' ') ?> ₽
                            </div>
                        </div>
                        <div class="item-total">
                            <?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Итоги -->
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Товары:</span>
                        <span><?= number_format($order['subtotal'], 0, '', ' ') ?> ₽</span>
                    </div>

                    <?php if ($order['discount'] > 0): ?>
                        <div class="summary-row" style="color: var(--success-color);">
                            <span>Скидка (<?= $order['discount'] ?>%):</span>
                            <span>-<?= number_format($order['discount_amount'], 0, '', ' ') ?> ₽</span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span>
                            <?= $order['shipping_cost'] == 0 ? 'Бесплатно' : number_format($order['shipping_cost'], 0, '', ' ') . ' ₽' ?>
                        </span>
                    </div>

                    <div class="summary-row total">
                        <span>Итого к оплате:</span>
                        <span><?= number_format($order['total_amount'], 0, '', ' ') ?> ₽</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Что дальше -->
        <div class="next-steps">
            <h3 class="steps-title">
                <i class="fas fa-list-ol"></i>
                Что дальше?
            </h3>

            <div class="step-item">
                <div class="step-icon">1</div>
                <div class="step-content">
                    <div class="step-title">Подтверждение заказа</div>
                    <div class="step-description">
                        В течение 30 минут наш менеджер свяжется с вами для подтверждения заказа
                    </div>
                </div>
            </div>

            <div class="step-item">
                <div class="step-icon">2</div>
                <div class="step-content">
                    <div class="step-title">Сборка и отправка</div>
                    <div class="step-description">
                        После подтверждения мы соберем ваш заказ и передадим его в доставку
                    </div>
                </div>
            </div>

            <div class="step-item">
                <div class="step-icon">3</div>
                <div class="step-content">
                    <div class="step-title">Получение заказа</div>
                    <div class="step-description">
                        Вы получите уведомление о готовности к получению или доставке
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>