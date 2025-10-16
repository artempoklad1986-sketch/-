<?php
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Moscow');
session_start();

// Простая авторизация
$adminKey = 'admin123';
if (!isset($_GET['key']) || $_GET['key'] !== $adminKey) {
    http_response_code(403);
    die('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Доступ запрещен</title></head>
    <body style="font-family:Arial;text-align:center;padding:50px;background:#f0f0f0;">
        <h1>🔒 Доступ запрещен</h1>
        <p>Неверный ключ доступа к админ панели</p>
        <p>Используйте: <code>fotoadmin.php?key=admin123</code></p>
    </body></html>
    ');
}

$BASE = __DIR__;
$ordersLog = $BASE.'/orders.txt';
$uploadsDir = $BASE.'/uploads';
$photoConfigFile = $BASE.'/photo_config.json';
$printOrdersLog = $BASE.'/print_orders.txt';

// Создаём папку uploads если нет
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Helper функции
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Функция генерации номера заказа
function generateOrderNumber($ordersLog, $printOrdersLog) {
    $maxNum = 0;

    foreach ([$ordersLog, $printOrdersLog] as $file) {
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/print_order_(\d+)/', $line, $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) $maxNum = $num;
                }
            }
        }
    }

    return 'print_order_' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);
}

// API: Обновление статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json; charset=utf-8');

    $orderId = trim($_POST['order_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    if (empty($orderId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны параметры'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Читаем все заказы
    $allLines = [];
    $updated = false;

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, $orderId) !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $orderData = json_decode(substr($line, $jsonStart), true);

                if ($orderData && isset($orderData['id']) && $orderData['id'] === $orderId) {
                    $orderData['status'] = $newStatus;
                    $orderData['status_updated_at'] = date('Y-m-d H:i:s');

                    // Добавляем в историю
                    if (!isset($orderData['history'])) {
                        $orderData['history'] = [];
                    }
                    $orderData['history'][] = [
                        'action' => 'status_change',
                        'from' => $orderData['status'] ?? 'new',
                        'to' => $newStatus,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];

                    // Пересохраняем в txt
                    $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
                    $orderDate = date('Y-m-d', strtotime($orderData['dates']['order_date']));
                    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
                    file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

                    $line = date('Y-m-d H:i:s') . " | " . $orderId . " | " . json_encode($orderData, JSON_UNESCAPED_UNICODE);
                    $updated = true;
                }
            }
            $allLines[] = $line;
        }
    }

    if ($updated) {
        file_put_contents($printOrdersLog, implode("\n", $allLines) . "\n", LOCK_EX);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// API: Добавление комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    header('Content-Type: application/json; charset=utf-8');

    $orderId = trim($_POST['order_id'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if (empty($orderId) || empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны параметры'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allLines = [];
    $updated = false;

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, $orderId) !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $orderData = json_decode(substr($line, $jsonStart), true);

                if ($orderData && isset($orderData['id']) && $orderData['id'] === $orderId) {
                    if (!isset($orderData['comments'])) {
                        $orderData['comments'] = [];
                    }
                    $orderData['comments'][] = [
                        'text' => $comment,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];

                    $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
                    $orderDate = date('Y-m-d', strtotime($orderData['dates']['order_date']));
                    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
                    file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

                    $line = date('Y-m-d H:i:s') . " | " . $orderId . " | " . json_encode($orderData, JSON_UNESCAPED_UNICODE);
                    $updated = true;
                }
            }
            $allLines[] = $line;
        }
    }

    if ($updated) {
        file_put_contents($printOrdersLog, implode("\n", $allLines) . "\n", LOCK_EX);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// API: Дублирование заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'duplicate_order') {
    header('Content-Type: application/json; charset=utf-8');

    $orderId = trim($_POST['order_id'] ?? '');

    if (empty($orderId)) {
        echo json_encode(['success' => false, 'error' => 'ID не указан'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, $orderId) !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $orderData = json_decode(substr($line, $jsonStart), true);

                if ($orderData && isset($orderData['id']) && $orderData['id'] === $orderId) {
                    // Создаём копию
                    $newOrderData = $orderData;
                    $newOrderData['id'] = generateOrderNumber($ordersLog, $printOrdersLog);
                    $newOrderData['timestamp'] = date('Y-m-d H:i:s');
                    $newOrderData['dates']['order_date'] = date('Y-m-d');
                    $newOrderData['status'] = 'new';
                    unset($newOrderData['history']);
                    unset($newOrderData['comments']);

                    // Сохраняем
                    $logLine = date('Y-m-d H:i:s') . " | " . $newOrderData['id'] . " | " . json_encode($newOrderData, JSON_UNESCAPED_UNICODE) . "\n";
                    file_put_contents($printOrdersLog, $logLine, FILE_APPEND | LOCK_EX);

                    $phone = preg_replace('/[^0-9]/', '', $newOrderData['customer']['phone']);
                    $orderDate = date('Y-m-d');
                    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
                    file_put_contents($txtFilename, json_encode($newOrderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

                    echo json_encode([
                        'success' => true,
                        'new_order_id' => $newOrderData['id'],
                        'new_order_number' => str_replace('print_order_', '#', $newOrderData['id'])
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }
    }

    echo json_encode(['success' => false, 'error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

// API: Экспорт в Excel
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $exportOrders = [];
    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'print_order_') !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $jsonStr = substr($line, $jsonStart);
                $orderData = json_decode($jsonStr, true);
                if ($orderData && isset($orderData['id'])) {
                    $exportOrders[] = $orderData;
                }
            }
        }
    }

    usort($exportOrders, function($a, $b) {
        return strtotime($b['timestamp'] ?? '1970-01-01') - strtotime($a['timestamp'] ?? '1970-01-01');
    });

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Номер заказа',
        'Дата создания',
        'Дата готовности',
        'Клиент',
        'Телефон',
        'Email',
        'Описание',
        'Технические данные',
        'Сумма',
        'Предоплата',
        'Осталось',
        'Статус'
    ], ';');

    foreach ($exportOrders as $order) {
        $statusLabels = [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'ready' => 'Готов',
            'completed' => 'Выдан',
            'cancelled' => 'Отменён'
        ];

        fputcsv($output, [
            str_replace('print_order_', '#', $order['id']),
            date('d.m.Y H:i', strtotime($order['timestamp'])),
            date('d.m.Y', strtotime($order['dates']['ready_date'])),
            $order['customer']['name'],
            $order['customer']['phone'],
            $order['customer']['email'] ?? '',
            $order['details']['description'],
            $order['details']['technical'] ?? '',
            $order['pricing']['total'],
            $order['pricing']['prepayment'],
            $order['pricing']['total'] - $order['pricing']['prepayment'],
            $statusLabels[$order['status']] ?? $order['status']
        ], ';');
    }

    fclose($output);
    exit;
}

// API: Получение данных заказа по ID
if (isset($_GET['api']) && $_GET['api'] === 'get_order' && !empty($_GET['order_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $orderId = $_GET['order_id'];
    $foundOrder = null;

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, $orderId) !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $jsonStr = substr($line, $jsonStart);
                $orderData = json_decode($jsonStr, true);
                if ($orderData && isset($orderData['id']) && $orderData['id'] === $orderId) {
                    $foundOrder = $orderData;
                    break;
                }
            }
        }
    }

    if ($foundOrder) {
        $response = [
            'success' => true,
            'order_id' => $foundOrder['id'],
            'order_number' => str_replace('print_order_', '#', $foundOrder['id']),
            'customer_name' => $foundOrder['customer']['name'],
            'customer_phone' => $foundOrder['customer']['phone'],
            'customer_email' => $foundOrder['customer']['email'] ?? '',
            'order_date' => date('d.m.Y', strtotime($foundOrder['dates']['order_date'])),
            'ready_date' => date('d.m.Y', strtotime($foundOrder['dates']['ready_date'])),
            'description' => $foundOrder['details']['description'],
            'technical' => $foundOrder['details']['technical'] ?? '',
            'materials_provided' => $foundOrder['details']['materials_provided'],
            'materials_date' => !empty($foundOrder['details']['materials_date']) ? date('d.m.Y', strtotime($foundOrder['details']['materials_date'])) : '',
            'prepayment' => $foundOrder['pricing']['prepayment'],
            'prepayment_paid' => $foundOrder['pricing']['prepayment_paid'],
            'total' => $foundOrder['pricing']['total'],
            'remaining' => $foundOrder['pricing']['total'] - $foundOrder['pricing']['prepayment'],
            'timestamp' => date('d.m.Y H:i', strtotime($foundOrder['timestamp'])),
            'status' => $foundOrder['status'] ?? 'new',
            'comments' => $foundOrder['comments'] ?? []
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// API: Получение данных для календаря
if (isset($_GET['api']) && $_GET['api'] === 'calendar_events') {
    header('Content-Type: application/json; charset=utf-8');

    $events = [];

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'print_order_') !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $jsonStr = substr($line, $jsonStart);
                $orderData = json_decode($jsonStr, true);

                if ($orderData && isset($orderData['id'])) {
                    $statusColors = [
                        'new' => '#fbbf24',
                        'in_progress' => '#3b82f6',
                        'ready' => '#10b981',
                        'completed' => '#6b7280',
                        'cancelled' => '#ef4444'
                    ];

                    $events[] = [
                        'id' => $orderData['id'],
                        'title' => str_replace('print_order_', '#', $orderData['id']) . ' - ' . $orderData['customer']['name'],
                        'start' => $orderData['dates']['ready_date'],
                        'color' => $statusColors[$orderData['status'] ?? 'new'] ?? '#3b82f6',
                        'extendedProps' => [
                            'customer' => $orderData['customer']['name'],
                            'phone' => $orderData['customer']['phone'],
                            'description' => mb_substr($orderData['details']['description'], 0, 50),
                            'total' => $orderData['pricing']['total'],
                            'status' => $orderData['status'] ?? 'new'
                        ]
                    ];
                }
            }
        }
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE);
    exit;
}

// API: Обновление заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $orderId = trim($_POST['order_id'] ?? '');

    if (empty($orderId)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'ID заказа не указан'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allLines = [];
    $updated = false;

    if (file_exists($printOrdersLog)) {
        $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, $orderId) !== false && strpos($line, '{') !== false) {
                $jsonStart = strpos($line, '{');
                $orderData = json_decode(substr($line, $jsonStart), true);

                if ($orderData && isset($orderData['id']) && $orderData['id'] === $orderId) {
                    $orderData['customer']['name'] = trim($_POST['customer_name'] ?? '');
                    $orderData['customer']['phone'] = trim($_POST['customer_phone'] ?? '');
                    $orderData['customer']['email'] = trim($_POST['customer_email'] ?? '');
                    $orderData['details']['description'] = trim($_POST['order_description'] ?? '');
                    $orderData['details']['technical'] = trim($_POST['technical_details'] ?? '');
                    $orderData['details']['materials_provided'] = isset($_POST['materials_provided']);
                    $orderData['details']['materials_date'] = trim($_POST['materials_date'] ?? '');
                    $orderData['pricing']['prepayment'] = (float)($_POST['prepayment'] ?? 0);
                    $orderData['pricing']['prepayment_paid'] = isset($_POST['prepayment_paid']);
                    $orderData['pricing']['total'] = (float)($_POST['total_price'] ?? 0);
                    $orderData['dates']['ready_date'] = trim($_POST['ready_date'] ?? '');
                    $orderData['updated_at'] = date('Y-m-d H:i:s');

                    $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
                    $orderDate = date('Y-m-d', strtotime($orderData['dates']['order_date']));
                    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
                    file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

                    $line = date('Y-m-d H:i:s') . " | " . $orderId . " | " . json_encode($orderData, JSON_UNESCAPED_UNICODE);
                    $updated = true;
                }
            }
            $allLines[] = $line;
        }
    }

    if ($updated) {
        file_put_contents($printOrdersLog, implode("\n", $allLines) . "\n", LOCK_EX);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Обработка создания заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    $orderData = [
        'id' => generateOrderNumber($ordersLog, $printOrdersLog),
        'timestamp' => date('Y-m-d H:i:s'),
        'customer' => [
            'name' => trim($_POST['customer_name'] ?? ''),
            'phone' => trim($_POST['customer_phone'] ?? ''),
            'email' => trim($_POST['customer_email'] ?? '')
        ],
        'details' => [
            'description' => trim($_POST['order_description'] ?? ''),
            'technical' => trim($_POST['technical_details'] ?? ''),
            'materials_provided' => isset($_POST['materials_provided']),
            'materials_date' => trim($_POST['materials_date'] ?? '')
        ],
        'pricing' => [
            'prepayment' => (float)($_POST['prepayment'] ?? 0),
            'prepayment_paid' => isset($_POST['prepayment_paid']),
            'total' => (float)($_POST['total_price'] ?? 0)
        ],
        'dates' => [
            'order_date' => date('Y-m-d'),
            'ready_date' => trim($_POST['ready_date'] ?? '')
        ],
        'status' => 'new',
        'type' => 'print_order'
    ];

    $logLine = date('Y-m-d H:i:s') . " | " . $orderData['id'] . " | " . json_encode($orderData, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($printOrdersLog, $logLine, FILE_APPEND | LOCK_EX);

    $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
    $orderDate = date('Y-m-d');
    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
    file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    $response = [
        'success' => true,
        'order_id' => $orderData['id'],
        'order_number' => str_replace('print_order_', '#', $orderData['id']),
        'customer_name' => $orderData['customer']['name'],
        'customer_phone' => $orderData['customer']['phone'],
        'customer_email' => $orderData['customer']['email'],
        'order_date' => date('d.m.Y'),
        'ready_date' => date('d.m.Y', strtotime($orderData['dates']['ready_date'])),
        'description' => $orderData['details']['description'],
        'technical' => $orderData['details']['technical'],
        'materials_provided' => $orderData['details']['materials_provided'],
        'materials_date' => !empty($orderData['details']['materials_date']) ? date('d.m.Y', strtotime($orderData['details']['materials_date'])) : '',
        'prepayment' => $orderData['pricing']['prepayment'],
        'prepayment_paid' => $orderData['pricing']['prepayment_paid'],
        'total' => $orderData['pricing']['total'],
        'remaining' => $orderData['pricing']['total'] - $orderData['pricing']['prepayment'],
        'timestamp' => date('d.m.Y H:i'),
        'txt_file' => basename($txtFilename)
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// API конфигурации
if (isset($_GET['api']) && $_GET['api'] === 'config') {
    header('Content-Type: application/json; charset=utf-8');
    $photoConfig = json_decode(@file_get_contents($photoConfigFile), true);
    if (!$photoConfig) {
        $photoConfig = [
            'enabled' => true,
            'max_photos' => 100,
            'max_file_size' => 10,
            'supported_formats' => ['jpg', 'jpeg', 'png', 'heic', 'heif']
        ];
    }
    echo json_encode($photoConfig, JSON_UNESCAPED_UNICODE);
    exit;
}

// Скачивание архива
if (isset($_GET['download_archive']) && !empty($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    $pattern = $uploadsDir . "/photos_order_{$orderId}_*.zip";
    $archiveFiles = glob($pattern);
    if (!empty($archiveFiles) && file_exists($archiveFiles[0])) {
        $filename = basename($archiveFiles[0]);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($archiveFiles[0]));
        readfile($archiveFiles[0]);
        exit;
    } else {
        http_response_code(404);
        die('Архив заказа не найден');
    }
}

// Определяем активную страницу
$page = $_GET['page'] ?? 'dashboard';

// Загрузка заказов фотопечати
$orders = [];
if (file_exists($ordersLog)) {
    $lines = file($ordersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'photo_') !== false && strpos($line, '{') !== false) {
            $jsonStart = strpos($line, '{');
            $jsonStr = substr($line, $jsonStart);
            $orderData = json_decode($jsonStr, true);
            if ($orderData && isset($orderData['id'])) {
                $orders[] = $orderData;
            }
        }
    }
}

// Сортируем фотозаказы
usort($orders, function($a, $b) {
    return strtotime($b['timestamp'] ?? '1970-01-01') - strtotime($a['timestamp'] ?? '1970-01-01');
});

// Загрузка заказов печати
$printOrders = [];
if (file_exists($printOrdersLog)) {
    $lines = file($printOrdersLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'print_order_') !== false && strpos($line, '{') !== false) {
            $jsonStart = strpos($line, '{');
            $jsonStr = substr($line, $jsonStart);
            $orderData = json_decode($jsonStr, true);
            if ($orderData && isset($orderData['id'])) {
                $printOrders[] = $orderData;
            }
        }
    }
}

// Объединяем все заказы
$allOrders = array_merge($orders, $printOrders);

// Сортировка по дате (новые сначала)
usort($allOrders, function($a, $b) {
    return strtotime($b['timestamp'] ?? '1970-01-01') - strtotime($a['timestamp'] ?? '1970-01-01');
});

// Статистика
$totalOrders = count($allOrders);
$totalPhotos = array_sum(array_map(function($o) { return $o['details']['photo_count'] ?? 0; }, $orders));

// Считаем общую сумму
$totalAmount = 0;
$totalReceived = 0;
$totalRemaining = 0;

foreach ($allOrders as $order) {
    if (isset($order['pricing']['total_price'])) {
        $totalAmount += $order['pricing']['total_price'];
    } elseif (isset($order['pricing']['total'])) {
        $totalAmount += $order['pricing']['total'];
        $totalReceived += $order['pricing']['prepayment'] ?? 0;
        $totalRemaining += ($order['pricing']['total'] - ($order['pricing']['prepayment'] ?? 0));
    }
}

// Статистика за периоды
$today = date('Y-m-d');
$todayOrders = array_filter($allOrders, function($o) use ($today) { return strpos($o['timestamp'], $today) === 0; });
$todayAmount = 0;
foreach ($todayOrders as $order) {
    if (isset($order['pricing']['total_price'])) {
        $todayAmount += $order['pricing']['total_price'];
    } elseif (isset($order['pricing']['total'])) {
        $todayAmount += $order['pricing']['total'];
    }
}

$thisMonth = date('Y-m');
$monthOrders = array_filter($allOrders, function($o) use ($thisMonth) { return strpos($o['timestamp'], $thisMonth) === 0; });
$monthAmount = 0;
foreach ($monthOrders as $order) {
    if (isset($order['pricing']['total_price'])) {
        $monthAmount += $order['pricing']['total_price'];
    } elseif (isset($order['pricing']['total'])) {
        $monthAmount += $order['pricing']['total'];
    }
}

// Статистика по статусам
$statusCounts = [
    'new' => 0,
    'in_progress' => 0,
    'ready' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($printOrders as $order) {
    $status = $order['status'] ?? 'new';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯 ПРИНТСС Админ PRO - Управление заказами</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
        }

        .logo h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .logo p {
            opacity: 0.9;
            font-size: 14px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #555;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .menu a:hover, .menu a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .menu i {
            margin-right: 12px;
            width: 20px;
        }

        .main-content {
            padding: 30px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .header h2 {
            color: #333;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #0284c7);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--color);
        }

        .stat-card.total { --color: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.today { --color: linear-gradient(135deg, #10b981, #047857); }
        .stat-card.month { --color: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.received { --color: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-card.remaining { --color: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-right: 15px;
        }

        .stat-card.total .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.today .stat-icon { background: linear-gradient(135deg, #10b981, #047857); }
        .stat-card.month .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.received .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-card.remaining .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .controls {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .filter-select {
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .orders-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .orders-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .orders-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .orders-table {
            max-height: 70vh;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 14px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            background: #e0e7ff;
            color: #3730a3;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .client-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .client-contact {
            font-size: 11px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 2px;
        }

        .order-details {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .detail-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .detail-photos { background: #dbeafe; color: #1e40af; }
        .detail-size { background: #f3e8ff; color: #7c3aed; }
        .detail-paper { background: #ecfdf5; color: #065f46; }

        .edit-indicators {
            margin-top: 8px;
        }

        .edit-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .edit-processed { background: #fef3c7; color: #92400e; }
        .edit-polaroid { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }

        .price {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
            margin-bottom: 6px;
        }

        .discount {
            font-size: 12px;
            color: #059669;
            background: #d1fae5;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-new { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-ready { background: #d1fae5; color: #065f46; }
        .status-completed { background: #e5e7eb; color: #374151; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .action-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .action-btn-success {
            background: linear-gradient(135deg, #10b981, #047857);
            color: white;
        }

        .action-btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .action-btn-info {
            background: linear-gradient(135deg, #06b6d4, #0284c7);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .download-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .archive-info {
            font-size: 10px;
            color: #6b7280;
            margin-top: 8px;
        }

        .no-orders {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .no-orders i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .order-form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-grid.full {
            grid-template-columns: 1fr;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .form-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .form-checkbox-label {
            font-size: 14px;
            color: #374151;
            cursor: pointer;
        }

        .important-note {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .important-note h4 {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
        }

        .important-note ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .important-note li {
            font-size: 13px;
            color: #78350f;
            padding: 4px 0;
            padding-left: 20px;
            position: relative;
        }

        .important-note li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #f59e0b;
            font-weight: bold;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 16px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f3f4f6;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            color: #6b7280;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: #e5e7eb;
            color: #374151;
            transform: rotate(90deg);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #047857);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            margin: 0 auto 20px;
        }

        .modal-icon.edit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .notification-box {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.8;
            color: #374151;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-copy {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-whatsapp {
            flex: 1;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
        }

        .btn-receipt {
            flex: 1;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .receipt-print, .receipt-print * {
                visibility: visible;
            }
            .receipt-print {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
            }
        }

        .receipt-print {
            display: none;
        }

        .receipt-document {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            font-family: Arial, sans-serif;
            color: #000;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .receipt-header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .receipt-info {
            font-size: 12px;
            color: #666;
            line-height: 1.6;
        }

        .receipt-body {
            margin: 30px 0;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .receipt-row.header {
            font-weight: bold;
            border-bottom: 2px solid #333;
        }

        .receipt-row.total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            margin-top: 10px;
        }

        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }

        .receipt-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }

        .success-message.show {
            display: flex;
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

        .calendar-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        #calendar {
            max-width: 100%;
        }

        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #e5e7eb;
        }

        .fc-button-primary {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            border: none !important;
        }

        .fc-button-primary:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46a0) !important;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .status-dropdown {
            position: relative;
            display: inline-block;
        }

        .status-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 100;
            border-radius: 10px;
            overflow: hidden;
            top: 100%;
            left: 0;
            margin-top: 5px;
        }

        .status-dropdown:hover .status-dropdown-content {
            display: block;
        }

        .status-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
            font-weight: 600;
        }

        .status-option:hover {
            background: #f3f4f6;
        }

        .comment-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .comment-item {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .comment-time {
            color: #6b7280;
            font-size: 11px;
            margin-top: 4px;
        }

        .comment-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .comment-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 13px;
        }

        @media (max-width: 1024px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .controls-grid {
                grid-template-columns: 1fr;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .header-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card, .orders-container, .controls, .order-form-container, .calendar-container, .chart-container {
            animation: fadeIn 0.6s ease-out;
        }

        .orders-table::-webkit-scrollbar, .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .orders-table::-webkit-scrollbar-track, .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .orders-table::-webkit-scrollbar-thumb, .modal-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Боковая панель -->
        <aside class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-print"></i> ПРИНТСС PRO</h1>
                <p>Панель управления</p>
            </div>

            <nav>
                <ul class="menu">
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar"></i> Дашборд
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="<?= $page === 'create_order' ? 'active' : '' ?>">
                            <i class="fas fa-plus-circle"></i> Создать заказ
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=photo_orders" class="<?= $page === 'photo_orders' ? 'active' : '' ?>">
                            <i class="fas fa-camera"></i> Фотозаказы
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=all_orders" class="<?= $page === 'all_orders' ? 'active' : '' ?>">
                            <i class="fas fa-list"></i> Все заказы
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=calendar" class="<?= $page === 'calendar' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt"></i> Календарь
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&page=analytics" class="<?= $page === 'analytics' ? 'active' : '' ?>">
                            <i class="fas fa-chart-line"></i> Аналитика
                        </a>
                    </li>
                    <li>
                        <a href="?key=<?= $adminKey ?>&action=export_excel">
                            <i class="fas fa-file-excel"></i> Экспорт Excel
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Главная область -->
        <main class="main-content">
            <?php if ($page === 'photo_orders'): ?>
                <!-- СТРАНИЦА ФОТОЗАКАЗОВ -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-camera"></i> Заказы фотопечати</h2>
                        <p style="color: #666; margin-top: 5px;">Управление заказами из фотоконструктора</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-success" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                    </div>
                </header>

                <!-- Панель управления -->
                <div class="controls">
                    <div class="controls-grid">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" id="searchInputPhoto" placeholder="Поиск по ID, имени, телефону..." onkeyup="filterPhotoOrders()">
                        </div>

                        <select class="filter-select" id="statusFilterPhoto" onchange="filterPhotoOrders()">
                            <option value="">Все статусы</option>
                            <option value="new">Новые</option>
                            <option value="pending_payment">Ожидает оплаты</option>
                            <option value="completed">Завершенные</option>
                        </select>

                        <select class="filter-select" id="dateFilterPhoto" onchange="filterPhotoOrders()">
                            <option value="">Все даты</option>
                            <option value="today">Сегодня</option>
                            <option value="week">Эта неделя</option>
                            <option value="month">Этот месяц</option>
                        </select>
                    </div>
                </div>

                <!-- Заказы -->
                <?php if (empty($orders)): ?>
                <div class="orders-container">
                    <div class="no-orders">
                        <i class="fas fa-inbox"></i>
                        <h2>Фотозаказов пока нет</h2>
                        <p>Заказы из фотоконструктора появятся здесь</p>
                    </div>
                </div>
                <?php else: ?>

                <div class="orders-container">
                    <div class="orders-header">
                        <h3><i class="fas fa-images"></i> Список фотозаказов</h3>
                        <div class="orders-count">
                            <span id="ordersCountPhoto"><?= count($orders) ?></span> заказов
                        </div>
                    </div>

                    <div class="orders-table">
                        <table id="ordersTablePhoto">
                            <thead>
                                <tr>
                                    <th width="15%">ID заказа</th>
                                    <th width="20%">Клиент</th>
                                    <th width="30%">Детали заказа</th>
                                    <th width="15%">Сумма</th>
                                    <th width="10%">Статус</th>
                                    <th width="10%">Архив</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $hasEditedPhotos = 0;
                                    $polaroidCount = 0;

                                    if (!empty($order['details']['photos'])) {
                                        foreach ($order['details']['photos'] as $photo) {
                                            if (!empty($photo['edit_params'])) {
                                                if ($photo['edit_params']['brightness'] != 0 || $photo['edit_params']['contrast'] != 0 || $photo['edit_params']['crop']) {
                                                    $hasEditedPhotos++;
                                                }
                                                if ($photo['edit_params']['polaroid']) {
                                                    $polaroidCount++;
                                                }
                                            }
                                        }
                                    }
                                ?>
                                <tr data-order-id="<?= esc($order['id']) ?>" 
                                    data-customer="<?= esc($order['name'] ?? '') ?>" 
                                    data-phone="<?= esc($order['phone'] ?? '') ?>"
                                    data-status="<?= esc($order['status'] ?? 'new') ?>"
                                    data-date="<?= esc($order['timestamp'] ?? '') ?>">

                                    <td>
                                        <div class="order-id"><?= esc($order['id']) ?></div>
                                        <div style="font-size: 11px; color: #6b7280; margin-top: 8px;">
                                            <i class="fas fa-clock"></i> <?= date('d.m.Y H:i', strtotime($order['timestamp'])) ?>
                                        </div>
                                        <div style="font-size: 10px; color: #9ca3af; margin-top: 4px;">
                                            <i class="fas fa-map-marker-alt"></i> <?= esc(substr($order['ip'] ?? 'неизвестен', 0, 12)) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="client-info">
                                            <div class="client-name"><?= esc($order['name']) ?></div>
                                            <div class="client-contact">
                                                <i class="fas fa-phone"></i> <?= esc($order['phone']) ?>
                                            </div>
                                            <?php if (!empty($order['email'])): ?>
                                            <div class="client-contact">
                                                <i class="fas fa-envelope"></i> <?= esc($order['email']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="order-details">
                                            <span class="detail-badge detail-photos">
                                                <i class="fas fa-images"></i> <?= $order['details']['photo_count'] ?> фото
                                            </span>
                                            <span class="detail-badge detail-size">
                                                <i class="fas fa-ruler-combined"></i> <?= esc($order['details']['size']) ?>
                                            </span>
                                            <span class="detail-badge detail-paper">
                                                <i class="fas fa-file-alt"></i> <?= esc($order['details']['paper']) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($order['details']['qty_per_photo']) && $order['details']['qty_per_photo'] > 1): ?>
                                        <div style="font-size: 12px; color: #6b7280; margin-bottom: 6px;">
                                            <i class="fas fa-copy"></i> Тираж: <?= $order['details']['qty_per_photo'] ?>x каждое фото
                                        </div>
                                        <?php endif; ?>

                                        <div class="edit-indicators">
                                            <?php if ($hasEditedPhotos > 0): ?>
                                            <span class="edit-badge edit-processed">
                                                <i class="fas fa-palette"></i> Обработано: <?= $hasEditedPhotos ?>
                                            </span>
                                            <?php endif; ?>

                                            <?php if ($polaroidCount > 0): ?>
                                            <span class="edit-badge edit-polaroid">
                                                <i class="fas fa-camera-retro"></i> Polaroid: <?= $polaroidCount ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($order['details']['processing_options'])): ?>
                                        <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">
                                            <i class="fas fa-tools"></i> Услуги: 
                                            <?= implode(', ', array_map(function($p) { return $p['name']; }, $order['details']['processing_options'])) ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['details']['comment'])): ?>
                                        <div style="font-size: 11px; color: #6b7280; margin-top: 6px; font-style: italic;">
                                            <i class="fas fa-comment"></i> <?= esc(mb_substr($order['details']['comment'], 0, 50)) ?><?= mb_strlen($order['details']['comment']) > 50 ? '...' : '' ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="price"><?= number_format($order['pricing']['total_price'], 0, '.', ' ') ?> ₽</div>
                                        <?php if (!empty($order['pricing']['discount_percent'])): ?>
                                        <div class="discount">
                                            -<?= $order['pricing']['discount_percent'] ?>% (<?= number_format($order['pricing']['discount_amount'], 0) ?> ₽)
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($order['payment_method'] === 'online'): ?>
                                        <div style="font-size: 11px; color: #3b82f6; margin-top: 6px;">
                                            <i class="fas fa-credit-card"></i> Онлайн оплата
                                        </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        $status = $order['status'] ?? 'new';
                                        $statusLabels = [
                                            'new' => 'Новый',
                                            'pending_payment' => 'Ожидает оплаты',
                                            'completed' => 'Завершен'
                                        ];
                                        ?>
                                        <span class="status-badge status-<?= $status ?>">
                                            <?= $statusLabels[$status] ?? 'Неизвестно' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="download-section">
                                            <?php if (!empty($order['archive']['filename'])): ?>
                                            <a href="?key=<?= $adminKey ?>&download_archive=1&order_id=<?= urlencode($order['id']) ?>" 
                                               class="download-btn" title="Скачать ZIP архив с фотографиями">
                                                <i class="fas fa-download"></i> Скачать
                                            </a>
                                            <div class="archive-info">
                                                <i class="fas fa-file-archive"></i> <?= round($order['archive']['size']/1024/1024, 1) ?> МБ
                                                <br>
                                                <i class="fas fa-images"></i> <?= $order['archive']['files_count'] ?> файлов
                                            </div>
                                            <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 12px;">
                                                <i class="fas fa-times-circle"></i><br>
                                                Архив не создан
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif ($page === 'create_order'): ?>
                <!-- СТРАНИЦА СОЗДАНИЯ ЗАКАЗА -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-plus-circle"></i> Создание нового заказа</h2>
                        <p style="color: #666; margin-top: 5px;">Заполните форму для создания заказа</p>
                    </div>
                    <a href="?key=<?= $adminKey ?>&page=dashboard" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Вернуться
                    </a>
                </header>

                <div id="successMessage" class="success-message">
                    <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                    <span>Заказ успешно создан! Форма очищена.</span>
                </div>

                <div class="order-form-container">
                    <form id="orderForm">
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-user"></i> ЗАКАЗЧИК
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">ФИО: *</label>
                                    <input type="text" name="customer_name" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Телефон: *</label>
                                    <input type="tel" name="customer_phone" class="form-input" required>
                                </div>
                            </div>
                            <div class="form-grid full" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Email:</label>
                                    <input type="email" name="customer_email" class="form-input">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-file-alt"></i> ЧТО ДЕЛАЕМ (описание заказа)
                            </div>
                            <div class="form-grid full">
                                <div class="form-group">
                                    <textarea name="order_description" class="form-textarea" placeholder="Например: Вывеска 2x1м, печать на баннере, люверсы по периметру" required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-tools"></i> ТЕХНИЧЕСКИЕ ДАННЫЕ
                            </div>
                            <div class="form-grid full">
                                <div class="form-group">
                                    <label class="form-label">Размеры, материалы, тексты, логотипы и т.д.:</label>
                                    <textarea name="technical_details" class="form-textarea" placeholder="Укажите размеры, материалы, цвета, шрифты и другие технические детали"></textarea>
                                </div>
                            </div>

                            <div class="form-checkbox-group">
                                <input type="checkbox" id="materials_provided" name="materials_provided" class="form-checkbox">
                                <label for="materials_provided" class="form-checkbox-label">
                                    Все материалы предоставлены (логотипы, тексты, фото и т.д.)
                                </label>
                            </div>

                            <div class="form-grid" style="margin-top: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Материалы будут предоставлены до:</label>
                                    <input type="date" name="materials_date" class="form-input">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-ruble-sign"></i> СТОИМОСТЬ
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Предоплата (руб.):</label>
                                    <input type="number" name="prepayment" class="form-input" min="0" step="1" value="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Итого к оплате (руб.): *</label>
                                    <input type="number" name="total_price" class="form-input" min="0" step="1" required>
                                </div>
                            </div>
                            <div class="form-checkbox-group">
                                <input type="checkbox" id="prepayment_paid" name="prepayment_paid" class="form-checkbox">
                                <label for="prepayment_paid" class="form-checkbox-label">
                                    Предоплата внесена
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calendar"></i> СРОКИ
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Дата приёма заказа:</label>
                                    <input type="date" value="<?= date('Y-m-d') ?>" class="form-input" disabled>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Дата готовности: *</label>
                                    <input type="date" name="ready_date" class="form-input" required>
                                </div>
                            </div>
                        </div>

                        <div class="important-note">
                            <h4>ВАЖНО! Заказчик подтверждает своей подписью:</h4>
                            <ul>
                                <li>Описание заказа согласовано и понятно</li>
                                <li>Срок выполнения устраивает</li>
                                <li>Стоимость работ согласована</li>
                                <li>Дополнительные правки после утверждения оплачиваются отдельно</li>
                                <li>Если материалы не предоставлены вовремя — срок выполнения сдвигается</li>
                                <li>Претензии принимаются только при наличии корешка заказа</li>
                            </ul>
                        </div>

                        <div class="form-actions">
                            <a href="?key=<?= $adminKey ?>&page=dashboard" class="btn" style="background: #e5e7eb; color: #374151;">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Создать заказ
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ($page === 'calendar'): ?>
                <!-- СТРАНИЦА КАЛЕНДАРЯ -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-calendar-alt"></i> Календарь заказов</h2>
                        <p style="color: #666; margin-top: 5px;">Визуальное планирование по датам готовности</p>
                    </div>
                    <div class="header-actions">
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Создать заказ
                        </a>
                        <button class="btn btn-success" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                    </div>
                </header>

                <div class="calendar-container">
                    <div id="calendar"></div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var calendarEl = document.getElementById('calendar');
                    if (calendarEl) {
                        var calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            locale: 'ru',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,listMonth'
                            },
                            buttonText: {
                                today: 'Сегодня',
                                month: 'Месяц',
                                week: 'Неделя',
                                list: 'Список'
                            },
                            events: function(info, successCallback, failureCallback) {
                                fetch('?key=<?= $adminKey ?>&api=calendar_events')
                                    .then(response => response.json())
                                    .then(data => successCallback(data))
                                    .catch(error => failureCallback(error));
                            },
                            eventClick: function(info) {
                                showOrderDetails(info.event.id);
                            },
                            eventContent: function(arg) {
                                return {
                                    html: '<div style="padding: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + arg.event.title + '</div>'
                                };
                            }
                        });
                        calendar.render();
                    }
                });
                </script>

            <?php elseif ($page === 'analytics'): ?>
                <!-- СТРАНИЦА АНАЛИТИКИ -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-chart-line"></i> Финансовая аналитика</h2>
                        <p style="color: #666; margin-top: 5px;">Графики доходов и статистика</p>
                    </div>
                    <div class="header-actions">
                        <a href="?key=<?= $adminKey ?>&action=export_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Экспорт Excel
                        </a>
                        <button class="btn btn-info" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                    </div>
                </header>

                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div>
                                <div class="stat-number"><?= $totalOrders ?></div>
                                <div class="stat-label">Всего заказов</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card received">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                            <div>
                                <div class="stat-number"><?= number_format($totalReceived, 0, '.', ' ') ?> ₽</div>
                                <div class="stat-label">Получено (предоплаты)</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card remaining">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                            <div>
                                <div class="stat-number"><?= number_format($totalRemaining, 0, '.', ' ') ?> ₽</div>
                                <div class="stat-label">К получению</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card today">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                            <div>
                                <div class="stat-number"><?= count($todayOrders) ?></div>
                                <div class="stat-label">Заказов сегодня</div>
                                <?php if ($todayAmount > 0): ?>
                                <div style="font-size: 12px; color: #059669; margin-top: 2px;">
                                    <?= number_format($todayAmount, 0, '.', ' ') ?> ₽
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card month">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <div class="stat-number"><?= count($monthOrders) ?></div>
                                <div class="stat-label">За этот месяц</div>
                                <?php if ($monthAmount > 0): ?>
                                <div style="font-size: 12px; color: #d97706; margin-top: 2px;">
                                    <?= number_format($monthAmount, 0, '.', ' ') ?> ₽
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Графики -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <div class="chart-title">Распределение по статусам</div>
                        <canvas id="statusChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">Доходы за последние 7 дней</div>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <script>
                // График статусов
                const statusCtx = document.getElementById('statusChart');
                if (statusCtx) {
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Новый', 'В работе', 'Готов', 'Выдан', 'Отменён'],
                            datasets: [{
                                data: [
                                    <?= $statusCounts['new'] ?>,
                                    <?= $statusCounts['in_progress'] ?>,
                                    <?= $statusCounts['ready'] ?>,
                                    <?= $statusCounts['completed'] ?>,
                                    <?= $statusCounts['cancelled'] ?>
                                ],
                                backgroundColor: [
                                    '#fbbf24',
                                    '#3b82f6',
                                    '#10b981',
                                    '#6b7280',
                                    '#ef4444'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }

                // График доходов за 7 дней
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    const last7Days = [];
                    const revenueData = [];

                    for (let i = 6; i >= 0; i--) {
                        const date = new Date();
                        date.setDate(date.getDate() - i);
                        const dateStr = date.toISOString().split('T')[0];
                        last7Days.push(date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' }));

                        let dayRevenue = 0;
                        <?php foreach ($printOrders as $order): ?>
                        if ('<?= date('Y-m-d', strtotime($order['dates']['order_date'])) ?>' === dateStr) {
                            dayRevenue += <?= $order['pricing']['total'] ?>;
                        }
                        <?php endforeach; ?>

                        revenueData.push(dayRevenue);
                    }

                    new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: last7Days,
                            datasets: [{
                                label: 'Доход (₽)',
                                data: revenueData,
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value.toLocaleString('ru-RU') + ' ₽';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                </script>

            <?php elseif ($page === 'all_orders'): ?>
                <!-- СТРАНИЦА ВСЕХ ЗАКАЗОВ -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-list"></i> Все заказы печати</h2>
                        <p style="color: #666; margin-top: 5px;">Полный список заказов печати с управлением</p>
                    </div>
                    <div class="header-actions">
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Создать
                        </a>
                        <a href="?key=<?= $adminKey ?>&action=export_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <button class="btn btn-info" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                    </div>
                </header>

                <!-- Панель управления -->
                <div class="controls">
                    <div class="controls-grid">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" id="searchInput" placeholder="Поиск по ID, имени, телефону..." onkeyup="filterOrders()">
                        </div>

                        <select class="filter-select" id="statusFilter" onchange="filterOrders()">
                            <option value="">Все статусы</option>
                            <option value="new">Новый</option>
                            <option value="in_progress">В работе</option>
                            <option value="ready">Готов</option>
                            <option value="completed">Выдан</option>
                            <option value="cancelled">Отменён</option>
                        </select>

                        <select class="filter-select" id="dateFilter" onchange="filterOrders()">
                            <option value="">Все даты</option>
                            <option value="today">Сегодня</option>
                            <option value="week">Эта неделя</option>
                            <option value="month">Этот месяц</option>
                        </select>
                    </div>
                </div>

                <!-- Заказы -->
                <?php if (empty($printOrders)): ?>
                <div class="orders-container">
                    <div class="no-orders">
                        <i class="fas fa-inbox"></i>
                        <h2>Заказов пока нет</h2>
                        <p>Создайте первый заказ для начала работы</p>
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Создать первый заказ
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <div class="orders-container">
                    <div class="orders-header">
                        <h3><i class="fas fa-list-ul"></i> Список заказов</h3>
                        <div class="orders-count">
                            <span id="ordersCount"><?= count($printOrders) ?></span> заказов
                        </div>
                    </div>

                    <div class="orders-table">
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th width="10%">ID</th>
                                    <th width="15%">Клиент</th>
                                    <th width="25%">Описание</th>
                                    <th width="10%">Готовность</th>
                                    <th width="10%">Сумма</th>
                                    <th width="10%">Статус</th>
                                    <th width="20%">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($printOrders as $order): ?>
                                <tr data-order-id="<?= esc($order['id']) ?>" 
                                    data-customer="<?= esc($order['customer']['name'] ?? '') ?>" 
                                    data-phone="<?= esc($order['customer']['phone'] ?? '') ?>"
                                    data-status="<?= esc($order['status'] ?? 'new') ?>"
                                    data-date="<?= esc($order['timestamp'] ?? '') ?>">

                                    <td>
                                        <div class="order-id"><?= str_replace('print_order_', '#', esc($order['id'])) ?></div>
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                                            <?= date('d.m H:i', strtotime($order['timestamp'])) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="client-name"><?= esc($order['customer']['name']) ?></div>
                                        <div class="client-contact">
                                            <i class="fas fa-phone"></i> <?= esc($order['customer']['phone']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div style="font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                                            <?= esc(mb_substr($order['details']['description'], 0, 60)) ?><?= mb_strlen($order['details']['description']) > 60 ? '...' : '' ?>
                                        </div>
                                        <?php if (!empty($order['details']['technical'])): ?>
                                        <div style="font-size: 11px; color: #6b7280;">
                                            <i class="fas fa-tools"></i> <?= esc(mb_substr($order['details']['technical'], 0, 40)) ?>...
                                        </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div style="font-size: 13px; font-weight: 600; color: #059669;">
                                            <?= date('d.m.Y', strtotime($order['dates']['ready_date'])) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="price"><?= number_format($order['pricing']['total'], 0, '.', ' ') ?> ₽</div>
                                        <?php if ($order['pricing']['prepayment'] > 0): ?>
                                        <div style="font-size: 11px; color: #f59e0b;">
                                            Остаток: <?= number_format($order['pricing']['total'] - $order['pricing']['prepayment'], 0) ?> ₽
                                        </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="status-dropdown">
                                            <?php
                                            $status = $order['status'] ?? 'new';
                                            $statusLabels = [
                                                'new' => 'Новый',
                                                'in_progress' => 'В работе',
                                                'ready' => 'Готов',
                                                'completed' => 'Выдан',
                                                'cancelled' => 'Отменён'
                                            ];
                                            ?>
                                            <div class="status-badge status-<?= $status ?>">
                                                <?= $statusLabels[$status] ?? 'Неизвестно' ?> <i class="fas fa-caret-down"></i>
                                            </div>
                                            <div class="status-dropdown-content">
                                                <div class="status-option" onclick="changeStatus('<?= esc($order['id']) ?>', 'new')">
                                                    <i class="fas fa-circle" style="color: #fbbf24;"></i> Новый
                                                </div>
                                                <div class="status-option" onclick="changeStatus('<?= esc($order['id']) ?>', 'in_progress')">
                                                    <i class="fas fa-circle" style="color: #3b82f6;"></i> В работе
                                                </div>
                                                <div class="status-option" onclick="changeStatus('<?= esc($order['id']) ?>', 'ready')">
                                                    <i class="fas fa-circle" style="color: #10b981;"></i> Готов
                                                </div>
                                                <div class="status-option" onclick="changeStatus('<?= esc($order['id']) ?>', 'completed')">
                                                    <i class="fas fa-circle" style="color: #6b7280;"></i> Выдан
                                                </div>
                                                <div class="status-option" onclick="changeStatus('<?= esc($order['id']) ?>', 'cancelled')">
                                                    <i class="fas fa-circle" style="color: #ef4444;"></i> Отменён
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-primary" onclick="showOrderDetails('<?= esc($order['id']) ?>')" title="Детали">
                                                <i class="fas fa-eye"></i> Детали
                                            </button>
                                            <button class="action-btn action-btn-success" onclick="sendWhatsApp('<?= esc($order['customer']['phone']) ?>', '<?= esc($order['id']) ?>')" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </button>
                                            <button class="action-btn action-btn-warning" onclick="editOrder('<?= esc($order['id']) ?>')" title="Редактировать">
                                                <i class="fas fa-edit"></i> Изменить
                                            </button>
                                            <button class="action-btn action-btn-info" onclick="duplicateOrder('<?= esc($order['id']) ?>')" title="Дублировать">
                                                <i class="fas fa-copy"></i> Копия
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ДАШБОРД (ГЛАВНАЯ СТРАНИЦА) -->
                <header class="header">
                    <div>
                        <h2><i class="fas fa-tachometer-alt"></i> Панель управления</h2>
                        <p style="color: #666; margin-top: 5px;">Добро пожаловать в ПРИНТСС Админ PRO</p>
                    </div>
                    <div class="header-actions">
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Создать заказ
                        </a>
                        <a href="?key=<?= $adminKey ?>&page=photo_orders" class="btn btn-info">
                            <i class="fas fa-camera"></i> Фотозаказы
                        </a>
                        <a href="?key=<?= $adminKey ?>&page=all_orders" class="btn btn-warning">
                            <i class="fas fa-list"></i> Все заказы
                        </a>
                        <button class="btn btn-success" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Обновить
                        </button>
                    </div>
                </header>

                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div>
                                <div class="stat-number"><?= $totalOrders ?></div>
                                <div class="stat-label">Всего заказов</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card today">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                            <div>
                                <div class="stat-number"><?= count($todayOrders) ?></div>
                                <div class="stat-label">Заказов сегодня</div>
                                <?php if ($todayAmount > 0): ?>
                                <div style="font-size: 12px; color: #059669; margin-top: 2px;">
                                    <?= number_format($todayAmount, 0, '.', ' ') ?> ₽
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card month">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <div class="stat-number"><?= count($monthOrders) ?></div>
                                <div class="stat-label">За этот месяц</div>
                                <?php if ($monthAmount > 0): ?>
                                <div style="font-size: 12px; color: #d97706; margin-top: 2px;">
                                    <?= number_format($monthAmount, 0, '.', ' ') ?> ₽
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card received">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                            <div>
                                <div class="stat-number"><?= number_format($totalReceived, 0, '.', ' ') ?> ₽</div>
                                <div class="stat-label">Получено</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card remaining">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                            <div>
                                <div class="stat-number"><?= number_format($totalRemaining, 0, '.', ' ') ?> ₽</div>
                                <div class="stat-label">К получению</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Последние заказы -->
                <?php if (empty($allOrders)): ?>
                <div class="orders-container">
                    <div class="no-orders">
                        <i class="fas fa-inbox"></i>
                        <h2>Заказов пока нет</h2>
                        <p>Создайте первый заказ для начала работы</p>
                        <a href="?key=<?= $adminKey ?>&page=create_order" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Создать первый заказ
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="orders-container">
                    <div class="orders-header">
                        <h3><i class="fas fa-clock"></i> Последние заказы</h3>
                        <div class="orders-count">
                            Последние 10 из <?= $totalOrders ?>
                        </div>
                    </div>

                    <div class="orders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th width="12%">ID</th>
                                    <th width="18%">Клиент</th>
                                    <th width="30%">Описание</th>
                                    <th width="12%">Дата</th>
                                    <th width="12%">Сумма</th>
                                    <th width="10%">Тип</th>
                                    <th width="6%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recentOrders = array_slice($allOrders, 0, 10);
                                foreach ($recentOrders as $order): 
                                    $isPhoto = isset($order['details']['photo_count']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="order-id"><?= $isPhoto ? esc($order['id']) : str_replace('print_order_', '#', esc($order['id'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="client-name"><?= esc($isPhoto ? $order['name'] : $order['customer']['name']) ?></div>
                                        <div class="client-contact">
                                            <i class="fas fa-phone"></i> <?= esc($isPhoto ? $order['phone'] : $order['customer']['phone']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isPhoto): ?>
                                            <div style="font-size: 13px;">
                                                <i class="fas fa-images"></i> <?= $order['details']['photo_count'] ?> фото | 
                                                <?= esc($order['details']['size']) ?> | 
                                                <?= esc($order['details']['paper']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 13px;">
                                                <?= esc(mb_substr($order['details']['description'], 0, 50)) ?><?= mb_strlen($order['details']['description']) > 50 ? '...' : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 600;">
                                            <?= date('d.m.Y H:i', strtotime($order['timestamp'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price"><?= number_format($isPhoto ? $order['pricing']['total_price'] : $order['pricing']['total'], 0, '.', ' ') ?> ₽</div>
                                    </td>
                                    <td>
                                        <?php if ($isPhoto): ?>
                                            <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">
                                                <i class="fas fa-camera"></i> ФОТО
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #f3e8ff; color: #7c3aed; padding: 4px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">
                                                <i class="fas fa-print"></i> ПЕЧАТЬ
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isPhoto): ?>
                                            <?php if (!empty($order['archive']['filename'])): ?>
                                            <a href="?key=<?= $adminKey ?>&download_archive=1&order_id=<?= urlencode($order['id']) ?>" 
                                               class="action-btn action-btn-primary action-btn-sm" title="Скачать">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="action-btn action-btn-primary action-btn-sm" onclick="showOrderDetails('<?= esc($order['id']) ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <a href="?key=<?= $adminKey ?>&page=photo_orders" class="btn btn-info btn-lg" style="justify-content: center;">
                        <i class="fas fa-camera"></i> Все фотозаказы (<?= count($orders) ?>)
                    </a>
                    <a href="?key=<?= $adminKey ?>&page=all_orders" class="btn btn-primary btn-lg" style="justify-content: center;">
                        <i class="fas fa-list"></i> Все заказы печати (<?= count($printOrders) ?>)
                    </a>
                </div>

                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Модальное окно с уведомлением -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>

            <div class="modal-header">
                <div class="modal-icon" id="modalIcon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="modal-title">Заказ <span id="modal-order-number"></span></h2>
                <p class="modal-subtitle">Скопируйте и отправьте клиенту</p>
            </div>

            <div class="notification-box" id="notificationText"></div>

            <div class="modal-actions">
                <button class="btn btn-copy" onclick="copyNotification()">
                    <i class="fas fa-copy"></i> Копировать
                </button>
                <button class="btn btn-whatsapp" onclick="sendWhatsAppFromModal()">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="btn btn-receipt" onclick="printReceipt()">
                    <i class="fas fa-receipt"></i> Чек
                </button>
                <button class="btn btn-primary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Закрыть
                </button>
            </div>

            <div class="comment-section" id="commentSection" style="display: none;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">
                    <i class="fas fa-comments"></i> Комментарии
                </h4>
                <div id="commentsContainer"></div>
                <div class="comment-form">
                    <input type="text" id="commentInput" class="comment-input" placeholder="Добавить комментарий...">
                    <button class="btn btn-sm btn-primary" onclick="addComment()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>

            <div class="modal-header">
                <div class="modal-icon edit">
                    <i class="fas fa-edit"></i>
                </div>
                <h2 class="modal-title">Редактирование заказа <span id="edit-order-number"></span></h2>
            </div>

            <form id="editOrderForm">
                <input type="hidden" name="order_id" id="edit-order-id">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user"></i> ЗАКАЗЧИК
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">ФИО: *</label>
                            <input type="text" name="customer_name" id="edit-customer-name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Телефон: *</label>
                            <input type="tel" name="customer_phone" id="edit-customer-phone" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-grid full" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="form-label">Email:</label>
                            <input type="email" name="customer_email" id="edit-customer-email" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-file-alt"></i> ОПИСАНИЕ
                    </div>
                    <div class="form-grid full">
                        <textarea name="order_description" id="edit-order-description" class="form-textarea" required></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-tools"></i> ТЕХНИЧЕСКИЕ ДАННЫЕ
                    </div>
                    <div class="form-grid full">
                        <textarea name="technical_details" id="edit-technical-details" class="form-textarea"></textarea>
                    </div>
                    <div class="form-checkbox-group">
                        <input type="checkbox" id="edit-materials-provided" name="materials_provided" class="form-checkbox">
                        <label for="edit-materials-provided" class="form-checkbox-label">
                            Все материалы предоставлены
                        </label>
                    </div>
                    <div class="form-grid" style="margin-top: 15px;">
                        <div class="form-group">
                            <label class="form-label">Материалы до:</label>
                            <input type="date" name="materials_date" id="edit-materials-date" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-ruble-sign"></i> СТОИМОСТЬ
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Предоплата (руб.):</label>
                            <input type="number" name="prepayment" id="edit-prepayment" class="form-input" min="0" step="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Итого (руб.): *</label>
                            <input type="number" name="total_price" id="edit-total-price" class="form-input" min="0" step="1" required>
                        </div>
                    </div>
                    <div class="form-checkbox-group">
                        <input type="checkbox" id="edit-prepayment-paid" name="prepayment_paid" class="form-checkbox">
                        <label for="edit-prepayment-paid" class="form-checkbox-label">
                            Предоплата внесена
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-calendar"></i> ДАТА ГОТОВНОСТИ
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Готовность: *</label>
                            <input type="date" name="ready_date" id="edit-ready-date" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" style="background: #e5e7eb; color: #374151;" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Товарный чек для печати -->
    <div class="receipt-print" id="receiptPrint">
        <div class="receipt-document">
            <div class="receipt-header">
                <h1>Копировальный центр ПРИНТСС</h1>
                <h2>ИП Гурбанова Г.А.</h2>
                <div class="receipt-info">
                    г. Сосновый Бор, ул. Красных Фортов, д. 49а<br>
                    Тел: 8-952-200-39-90 | Email: artcopy78@bk.ru
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <h2 style="font-size: 20px; font-weight: bold;">ТОВАРНЫЙ ЧЕК</h2>
                <div style="margin-top: 10px; font-size: 14px;">
                    № <span id="receipt-number"></span> от <span id="receipt-date"></span>
                </div>
            </div>

            <div class="receipt-body">
                <div class="receipt-row header">
                    <div style="width: 60%;">Наименование</div>
                    <div style="width: 15%; text-align: center;">Кол-во</div>
                    <div style="width: 25%; text-align: right;">Сумма</div>
                </div>

                <div class="receipt-row">
                    <div style="width: 60%;" id="receipt-description"></div>
                    <div style="width: 15%; text-align: center;">1</div>
                    <div style="width: 25%; text-align: right;" id="receipt-total"></div>
                </div>

                <div class="receipt-row total">
                    <div>ИТОГО:</div>
                    <div id="receipt-total-final"></div>
                </div>

                <div style="margin-top: 20px; font-size: 14px;">
                    <div style="margin-bottom: 8px;">
                        <strong>Заказчик:</strong> <span id="receipt-customer"></span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Телефон:</strong> <span id="receipt-phone"></span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Дата готовности:</strong> <span id="receipt-ready-date"></span>
                    </div>
                    <div id="receipt-prepayment-block" style="margin-top: 15px; display: none;">
                        <div style="margin-bottom: 8px;">
                            <strong>Предоплата:</strong> <span id="receipt-prepayment"></span>
                        </div>
                        <div style="margin-bottom: 8px; color: #f59e0b;">
                            <strong>Осталось оплатить:</strong> <span id="receipt-remaining"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="receipt-footer">
                <div style="margin-bottom: 20px; font-size: 12px; color: #666;">
                    <strong>Технические требования:</strong><br>
                    <span id="receipt-technical"></span>
                </div>

                <div class="receipt-signatures">
                    <div>
                        <div style="margin-bottom: 10px; font-weight: bold;">Продавец:</div>
                        <div class="signature-line"></div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">подпись</div>
                    </div>
                    <div>
                        <div style="margin-bottom: 10px; font-weight: bold;">Покупатель:</div>
                        <div class="signature-line"></div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">подпись</div>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #999;">
                    Товарный чек является подтверждением получения товара/услуги<br>
                    Претензии принимаются только при наличии данного чека
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentOrderData = null;
        let isEditMode = false;

        // Обработка формы создания заказа
        document.getElementById('orderForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'create_order');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    currentOrderData = result;
                    isEditMode = false;

                    const successMsg = document.getElementById('successMessage');
                    successMsg.classList.add('show');

                    this.reset();

                    setTimeout(() => {
                        successMsg.classList.remove('show');
                    }, 3000);

                    showSuccessModal(result);
                } else {
                    alert('Ошибка при создании заказа');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при создании заказа');
            }
        });

        // Обработка формы редактирования
        document.getElementById('editOrderForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'update_order');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Заказ успешно обновлён!');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Ошибка: ' + (result.error || 'Не удалось обновить'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при обновлении заказа');
            }
        });

        // Показать детали заказа
        async function showOrderDetails(orderId) {
            try {
                const response = await fetch(`?key=<?= $adminKey ?>&api=get_order&order_id=${encodeURIComponent(orderId)}`);
                const result = await response.json();

                if (result.success) {
                    currentOrderData = result;
                    isEditMode = false;
                    showSuccessModal(result);

                    if (result.comments && result.comments.length > 0) {
                        document.getElementById('commentSection').style.display = 'block';
                        const container = document.getElementById('commentsContainer');
                        container.innerHTML = '';
                        result.comments.forEach(comment => {
                            const div = document.createElement('div');
                            div.className = 'comment-item';
                            div.innerHTML = `
                                <div>${comment.text}</div>
                                <div class="comment-time">${new Date(comment.timestamp).toLocaleString('ru-RU')}</div>
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        document.getElementById('commentSection').style.display = 'block';
                        document.getElementById('commentsContainer').innerHTML = '<p style="color: #6b7280; font-size: 12px;">Комментариев пока нет</p>';
                    }
                } else {
                    alert('Ошибка: ' + (result.error || 'Заказ не найден'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при загрузке заказа');
            }
        }

        // Редактировать заказ
        async function editOrder(orderId) {
            try {
                const response = await fetch(`?key=<?= $adminKey ?>&api=get_order&order_id=${encodeURIComponent(orderId)}`);
                const result = await response.json();

                if (result.success) {
                    document.getElementById('edit-order-id').value = result.order_id;
                    document.getElementById('edit-order-number').textContent = result.order_number;
                    document.getElementById('edit-customer-name').value = result.customer_name;
                    document.getElementById('edit-customer-phone').value = result.customer_phone;
                    document.getElementById('edit-customer-email').value = result.customer_email || '';
                    document.getElementById('edit-order-description').value = result.description;
                    document.getElementById('edit-technical-details').value = result.technical || '';
                    document.getElementById('edit-materials-provided').checked = result.materials_provided;
                    document.getElementById('edit-materials-date').value = result.materials_date ? result.materials_date.split('.').reverse().join('-') : '';
                    document.getElementById('edit-prepayment').value = result.prepayment;
                    document.getElementById('edit-total-price').value = result.total;
                    document.getElementById('edit-prepayment-paid').checked = result.prepayment_paid;
                    document.getElementById('edit-ready-date').value = result.ready_date.split('.').reverse().join('-');

                    document.getElementById('editModal').classList.add('active');
                } else {
                    alert('Ошибка: ' + (result.error || 'Заказ не найден'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при загрузке заказа');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Смена статуса
        async function changeStatus(orderId, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('order_id', orderId);
                formData.append('status', newStatus);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Ошибка при смене статуса');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при смене статуса');
            }
        }

        // Дублирование заказа
        async function duplicateOrder(orderId) {
            if (!confirm('Создать копию этого заказа?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'duplicate_order');
                formData.append('order_id', orderId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`Заказ ${result.new_order_number} успешно создан!`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + (result.error || 'Не удалось дублировать'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при дублировании заказа');
            }
        }

        // Добавить комментарий
        async function addComment() {
            const input = document.getElementById('commentInput');
            const comment = input.value.trim();

            if (!comment || !currentOrderData) return;

            try {
                const formData = new FormData();
                formData.append('action', 'add_comment');
                formData.append('order_id', currentOrderData.order_id);
                formData.append('comment', comment);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    input.value = '';
                    showOrderDetails(currentOrderData.order_id);
                } else {
                    alert('Ошибка при добавлении комментария');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при добавлении комментария');
            }
        }

        // WhatsApp
        function sendWhatsApp(phone, orderId) {
            if (!orderId) {
                const cleanPhone = phone.replace(/[^0-9]/g, '');
                window.open(`https://wa.me/${cleanPhone}`, '_blank');
                return;
            }

            showOrderDetails(orderId);
        }

        function sendWhatsAppFromModal() {
            if (!currentOrderData) return;

            const phone = currentOrderData.customer_phone.replace(/[^0-9]/g, '');
            const text = document.getElementById('notificationText').textContent;
            const encoded = encodeURIComponent(text);

            window.open(`https://wa.me/${phone}?text=${encoded}`, '_blank');
        }

        function showSuccessModal(data) {
            const modal = document.getElementById('successModal');
            const notificationText = document.getElementById('notificationText');
            const modalOrderNumber = document.getElementById('modal-order-number');

            modalOrderNumber.textContent = data.order_number;

            let notification = `✅ ЗАКАЗ ПРИНЯТ!\n\n`;
            notification += `Номер заказа: ${data.order_number}\n`;
            notification += `Дата готовности: ${data.ready_date}\n\n`;
            notification += `Что делаем:\n`;
            notification += `${data.description}\n\n`;
            if (data.technical) {
                notification += `Технические данные:\n`;
                notification += `${data.technical}\n\n`;
            }
            notification += `Сумма:\n`;
            if (data.prepayment > 0) {
                notification += `• Предоплата: ${formatPrice(data.prepayment)} ₽`;
                if (data.prepayment_paid) {
                    notification += ` ✓\n`;
                } else {
                    notification += `\n`;
                }
                notification += `• Осталось: ${formatPrice(data.remaining)} ₽\n\n`;
            } else {
                notification += `• Итого: ${formatPrice(data.total)} ₽\n\n`;
            }
            notification += `Забрать заказ:\n`;
            notification += `📍 г. Сосновый Бор, ул. Красных Фортов, д. 49а\n`;
            notification += `📞 8-952-200-39-90\n\n`;
            notification += `Копировальный центр ПРИНТСС\n`;
            notification += `принтсс.рф`;

            notificationText.textContent = notification;
            modal.classList.add('active');

            fillReceiptData(data);
        }

        function fillReceiptData(data) {
            document.getElementById('receipt-number').textContent = data.order_number;
            document.getElementById('receipt-date').textContent = data.order_date || new Date().toLocaleDateString('ru-RU');
            document.getElementById('receipt-description').textContent = data.description;
            document.getElementById('receipt-total').textContent = formatPrice(data.total) + ' ₽';
            document.getElementById('receipt-total-final').textContent = formatPrice(data.total) + ' ₽';
            document.getElementById('receipt-customer').textContent = data.customer_name;
            document.getElementById('receipt-phone').textContent = data.customer_phone;
            document.getElementById('receipt-ready-date').textContent = data.ready_date;
            document.getElementById('receipt-technical').textContent = data.technical || 'Согласно заказу';

            if (data.prepayment > 0) {
                document.getElementById('receipt-prepayment-block').style.display = 'block';
                document.getElementById('receipt-prepayment').textContent = formatPrice(data.prepayment) + ' ₽' + (data.prepayment_paid ? ' ✓' : '');
                document.getElementById('receipt-remaining').textContent = formatPrice(data.remaining) + ' ₽';
            } else {
                document.getElementById('receipt-prepayment-block').style.display = 'none';
            }
        }

        function closeModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('active');
            currentOrderData = null;
            document.getElementById('commentSection').style.display = 'none';
        }

        function copyNotification() {
            const notificationText = document.getElementById('notificationText').textContent;

            navigator.clipboard.writeText(notificationText).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Скопировано!';
                btn.style.background = 'linear-gradient(135deg, #10b981, #047857)';

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            }).catch(err => {
                const textArea = document.createElement('textarea');
                textArea.value = notificationText;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Скопировано!';
                    btn.style.background = 'linear-gradient(135deg, #10b981, #047857)';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.style.background = '';
                    }, 2000);
                } catch (err) {
                    alert('Не удалось скопировать');
                }
                document.body.removeChild(textArea);
            });
        }

        function printReceipt() {
            if (!currentOrderData) {
                alert('Данные заказа не загружены');
                return;
            }

            const receiptPrint = document.getElementById('receiptPrint');
            receiptPrint.style.display = 'block';

            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    receiptPrint.style.display = 'none';
                }, 100);
            }, 100);
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price);
        }

        // Фильтрация заказов печати
        function filterOrders() {
            const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const dateFilter = document.getElementById('dateFilter')?.value || '';
            const rows = document.querySelectorAll('#ordersTable tbody tr');

            const today = new Date();
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

            let visibleCount = 0;

            rows.forEach(row => {
                const orderId = (row.dataset.orderId || '').toLowerCase();
                const customer = (row.dataset.customer || '').toLowerCase();
                const phone = (row.dataset.phone || '').toLowerCase();
                const status = row.dataset.status || '';
                const orderDate = new Date(row.dataset.date || '1970-01-01');

                const matchesSearch = orderId.includes(searchTerm) || 
                                    customer.includes(searchTerm) || 
                                    phone.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;

                let matchesDate = true;
                if (dateFilter === 'today') {
                    matchesDate = orderDate.toDateString() === today.toDateString();
                } else if (dateFilter === 'week') {
                    matchesDate = orderDate >= weekAgo;
                } else if (dateFilter === 'month') {
                    matchesDate = orderDate >= monthAgo;
                }

                const isVisible = matchesSearch && matchesStatus && matchesDate;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            const countEl = document.getElementById('ordersCount');
            if (countEl) countEl.textContent = visibleCount;
        }

        // Фильтрация фотозаказов
        function filterPhotoOrders() {
            const searchTerm = document.getElementById('searchInputPhoto')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('statusFilterPhoto')?.value || '';
            const dateFilter = document.getElementById('dateFilterPhoto')?.value || '';
            const rows = document.querySelectorAll('#ordersTablePhoto tbody tr');

            const today = new Date();
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

            let visibleCount = 0;

            rows.forEach(row => {
                const orderId = (row.dataset.orderId || '').toLowerCase();
                const customer = (row.dataset.customer || '').toLowerCase();
                const phone = (row.dataset.phone || '').toLowerCase();
                const status = row.dataset.status || '';
                const orderDate = new Date(row.dataset.date || '1970-01-01');

                const matchesSearch = orderId.includes(searchTerm) || 
                                    customer.includes(searchTerm) || 
                                    phone.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;

                let matchesDate = true;
                if (dateFilter === 'today') {
                    matchesDate = orderDate.toDateString() === today.toDateString();
                } else if (dateFilter === 'week') {
                    matchesDate = orderDate >= weekAgo;
                } else if (dateFilter === 'month') {
                    matchesDate = orderDate >= monthAgo;
                }

                const isVisible = matchesSearch && matchesStatus && matchesDate;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            const countEl = document.getElementById('ordersCountPhoto');
            if (countEl) countEl.textContent = visibleCount;
        }

        function refreshData() {
            location.reload();
        }

        console.log('🚀 ПРИНТСС Админ PRO v4.5 ULTIMATE + PHOTO загружен!');
        console.log('📊 Статистика:', {
            total: <?= $totalOrders ?>,
            photo: <?= count($orders) ?>,
            print: <?= count($printOrders) ?>,
            today: <?= count($todayOrders) ?>,
            month: <?= count($monthOrders) ?>
        });
    </script>
</body>
</html>
