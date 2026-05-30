<?php
/**
 * products.php v34.1 FIXED EXPORT
 * @version 34.1.0
 * @date 2025-10-27
 * @author Гурбанов Артем
 * 
 * ✅ ИСПРАВЛЕНИЯ v34.1:
 * - Убран преждевременный exit() в экспорте
 * - Добавлена проверка file_exists() после записи
 * - Улучшена обработка ошибок через try-catch
 * - Исправлена логика записи XML
 * 
 * ✅ ПОЛНЫЙ ФУНКЦИОНАЛ:
 * - Таблица товаров с миниатюрами
 * - Переключатели статусов (Открыт/Закрыт, Новинка, Популярный, Видимый)
 * - Редактирование цены и остатков прямо в таблице
 * - Смена категории через выпадающий список
 * - Модальное окно "Детали товара" (состав, описание, вес, изображение)
 * - Экспорт в 1С (один товар или выбранные)
 * - Фильтры (по категории, по статусу)
 * - Поиск по названию/external_id
 * - Массовые операции (смена категории, закрытие/открытие, удаление)
 * - Аккуратные стили как в orders.php
 * - Автосохранение при изменениях
 */

// ═══════════════════════════════════════════════════════════════════════════
// ЗАЩИТА
// ═══════════════════════════════════════════════════════════════════════════

if (!isset($GLOBALS['db'])) {
    http_response_code(403);
    die('❌ Access denied. Use visual1c.php');
}

$db = $GLOBALS['db'];
$moduleManager = $GLOBALS['moduleManager'] ?? null;
$rootDir = dirname(dirname(__DIR__));

// ═══════════════════════════════════════════════════════════════════════════
// ПОДКЛЮЧЕНИЕ ЗАВИСИМОСТЕЙ
// ═══════════════════════════════════════════════════════════════════════════

if (!class_exists('ValueTableParser')) {
    $parserFile = $rootDir . '/api/parsers/ValueTableParser.php';
    if (file_exists($parserFile)) {
        require_once $parserFile;
    }
}

if (!class_exists('FileProcessor')) {
    $processorFile = dirname(__DIR__) . '/visual/FileProcessor.php';
    if (file_exists($processorFile)) {
        require_once $processorFile;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// AJAX ОБРАБОТЧИКИ
// ═══════════════════════════════════════════════════════════════════════════

if (isset($_POST['ajax_products_action']) || isset($_GET['ajax_products_action'])) {
    header('Content-Type: application/json; charset=utf-8');

    while (ob_get_level()) {
        ob_end_clean();
    }

    $action = $_POST['ajax_products_action'] ?? $_GET['ajax_products_action'];

    try {
        switch ($action) {

            // ═══════════════════════════════════════════════════════════════
            // ПЕРЕКЛЮЧЕНИЕ СТАТУСА (Открыт/Закрыт, Новинка, Популярный, Видимый)
            // ═══════════════════════════════════════════════════════════════
            case 'toggle_status':
                $productId = $_POST['product_id'] ?? null;
                $statusType = $_POST['status_type'] ?? null;

                if (!$productId || !$statusType) {
                    throw new Exception('Не указан ID товара или тип статуса');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                // Переключаем статус
                switch ($statusType) {
                    case 'closed':
                        $product['is_closed'] = !($product['is_closed'] ?? false);
                        break;
                    case 'new':
                        $product['is_new'] = !($product['is_new'] ?? false);
                        break;
                    case 'popular':
                        $product['is_popular'] = !($product['is_popular'] ?? false);
                        break;
                    case 'visible':
                        $product['is_visible'] = !($product['is_visible'] ?? true);
                        break;
                    default:
                        throw new Exception('Неизвестный тип статуса');
                }

                $product['updated_at'] = date('Y-m-d H:i:s');
                $db->saveWithoutValidation('products', $product, $productId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Статус изменён',
                    'new_value' => $product[$statusType === 'closed' ? 'is_closed' : ($statusType === 'new' ? 'is_new' : ($statusType === 'popular' ? 'is_popular' : 'is_visible'))]
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ИЗМЕНЕНИЕ ЦЕНЫ
            // ═══════════════════════════════════════════════════════════════
            case 'update_price':
                $productId = $_POST['product_id'] ?? null;
                $newPrice = $_POST['new_price'] ?? null;

                if (!$productId || $newPrice === null) {
                    throw new Exception('Не указан ID товара или новая цена');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                $product['price'] = floatval($newPrice);
                $product['updated_at'] = date('Y-m-d H:i:s');
                $db->saveWithoutValidation('products', $product, $productId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Цена обновлена',
                    'new_price' => $product['price']
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ИЗМЕНЕНИЕ ОСТАТКА
            // ═══════════════════════════════════════════════════════════════
            case 'update_stock':
                $productId = $_POST['product_id'] ?? null;
                $newStock = $_POST['new_stock'] ?? null;

                if (!$productId || $newStock === null) {
                    throw new Exception('Не указан ID товара или новый остаток');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                $product['stock_quantity'] = intval($newStock);
                $product['stock'] = $product['stock_quantity'];
                $product['updated_at'] = date('Y-m-d H:i:s');
                $db->saveWithoutValidation('products', $product, $productId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Остаток обновлён',
                    'new_stock' => $product['stock_quantity']
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ПЕРЕКЛЮЧЕНИЕ "НЕОГРАНИЧЕННЫЙ ОСТАТОК"
            // ═══════════════════════════════════════════════════════════════
            case 'toggle_unlimited_stock':
                $productId = $_POST['product_id'] ?? null;

                if (!$productId) {
                    throw new Exception('Не указан ID товара');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                $product['unlimited_stock'] = !($product['unlimited_stock'] ?? false);
                if ($product['unlimited_stock']) {
                    $product['stock_quantity'] = 0;
                    $product['stock'] = 0;
                }
                $product['updated_at'] = date('Y-m-d H:i:s');
                $db->saveWithoutValidation('products', $product, $productId);

                echo json_encode([
                    'success' => true,
                    'message' => $product['unlimited_stock'] ? 'Остаток неограничен' : 'Остаток ограничен',
                    'unlimited_stock' => $product['unlimited_stock']
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // СМЕНА КАТЕГОРИИ
            // ═══════════════════════════════════════════════════════════════
            case 'change_category':
                $productId = $_POST['product_id'] ?? null;
                $newCategoryId = $_POST['new_category_id'] ?? null;

                if (!$productId || !$newCategoryId) {
                    throw new Exception('Не указан ID товара или категории');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                $category = $db->find('categories', $newCategoryId);
                if (!$category) {
                    throw new Exception('Категория не найдена');
                }

                $product['category_id'] = $newCategoryId;
                $product['parent_name'] = $category['name'] ?? '';
                $product['updated_at'] = date('Y-m-d H:i:s');
                $db->saveWithoutValidation('products', $product, $productId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Категория изменена на: ' . $category['name'],
                    'new_category_name' => $category['name']
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ПОЛУЧИТЬ ДЕТАЛИ ТОВАРА
            // ═══════════════════════════════════════════════════════════════
            case 'get_product_details':
                $productId = $_POST['product_id'] ?? null;

                if (!$productId) {
                    throw new Exception('Не указан ID товара');
                }

                $product = $db->find('products', $productId);
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                // Получаем категорию
                $categoryName = '';
                if (!empty($product['category_id'])) {
                    $category = $db->find('categories', $product['category_id']);
                    if ($category) {
                        $categoryName = $category['name'] ?? '';
                    }
                }

                echo json_encode([
                    'success' => true,
                    'product' => [
                        'id' => $product['id'],
                        'external_id' => $product['external_id'] ?? '',
                        'name' => $product['name'] ?? '',
                        'category_name' => $categoryName,
                        'parent_name' => $product['parent_name'] ?? '',
                        'price' => number_format(floatval($product['price'] ?? 0), 2, '.', ''),
                        'composition' => $product['composition'] ?? '',
                        'description' => $product['description'] ?? '',
                        'weight' => floatval($product['weight'] ?? 0),
                        'image' => $product['image'] ?? '',
                        'stock' => $product['stock'] ?? 0,
                        'stock_quantity' => $product['stock_quantity'] ?? 0,
                        'unlimited_stock' => $product['unlimited_stock'] ?? false,
                        'is_new' => $product['is_new'] ?? false,
                        'is_popular' => $product['is_popular'] ?? false,
                        'is_closed' => $product['is_closed'] ?? false,
                        'is_visible' => $product['is_visible'] ?? true,
                        'created_at' => $product['created_at'] ?? '',
                        'updated_at' => $product['updated_at'] ?? ''
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // УДАЛЕНИЕ ТОВАРА
            // ═══════════════════════════════════════════════════════════════
            case 'delete_product':
                $productId = $_POST['product_id'] ?? null;

                if (!$productId) {
                    throw new Exception('ID товара не указан');
                }

                $db->delete('products', $productId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Товар удалён'
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // МАССОВАЯ СМЕНА КАТЕГОРИИ
            // ═══════════════════════════════════════════════════════════════
            case 'bulk_change_category':
                $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
                $newCategoryId = $_POST['new_category_id'] ?? null;

                if (empty($productIds) || !$newCategoryId) {
                    throw new Exception('Не указаны товары или категория');
                }

                $category = $db->find('categories', $newCategoryId);
                if (!$category) {
                    throw new Exception('Категория не найдена');
                }

                $updated = 0;
                foreach ($productIds as $productId) {
                    $product = $db->find('products', $productId);
                    if ($product) {
                        $product['category_id'] = $newCategoryId;
                        $product['parent_name'] = $category['name'] ?? '';
                        $product['updated_at'] = date('Y-m-d H:i:s');
                        $db->saveWithoutValidation('products', $product, $productId);
                        $updated++;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => "Категория изменена для {$updated} товаров"
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // МАССОВОЕ ЗАКРЫТИЕ/ОТКРЫТИЕ
            // ═══════════════════════════════════════════════════════════════
            case 'bulk_toggle_closed':
                $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
                $setClosed = filter_var($_POST['set_closed'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

                if (empty($productIds)) {
                    throw new Exception('Не указаны товары');
                }

                $updated = 0;
                foreach ($productIds as $productId) {
                    $product = $db->find('products', $productId);
                    if ($product) {
                        $product['is_closed'] = $setClosed;
                        $product['updated_at'] = date('Y-m-d H:i:s');
                        $db->saveWithoutValidation('products', $product, $productId);
                        $updated++;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => $setClosed ? "Закрыто {$updated} товаров" : "Открыто {$updated} товаров"
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // МАССОВОЕ УДАЛЕНИЕ
            // ═══════════════════════════════════════════════════════════════
            case 'bulk_delete':
                $productIds = json_decode($_POST['product_ids'] ?? '[]', true);

                if (empty($productIds)) {
                    throw new Exception('Не указаны товары');
                }

                $deleted = 0;
                foreach ($productIds as $productId) {
                    $db->delete('products', $productId);
                    $deleted++;
                }

                echo json_encode([
                    'success' => true,
                    'message' => "Удалено {$deleted} товаров"
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ЭКСПОРТ В 1С (ИСПРАВЛЕННАЯ ВЕРСИЯ)
            // ═══════════════════════════════════════════════════════════════
            case 'export_to_1c':
                error_log("[PRODUCTS] ==================== EXPORT START ====================");

                $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
                error_log("[PRODUCTS] Received product IDs: " . print_r($productIds, true));

                if (empty($productIds)) {
                    throw new Exception('Не выбраны товары для экспорта');
                }

                // Получаем товары из БД
                $products = [];
                foreach ($productIds as $productId) {
                    $product = $db->find('products', $productId);
                    if ($product) {
                        $products[] = $product;
                        error_log("[PRODUCTS] Product loaded: " . ($product['name'] ?? $product['id']));
                    }
                }

                if (empty($products)) {
                    throw new Exception('Товары не найдены в БД');
                }

                error_log("[PRODUCTS] Total products to export: " . count($products));

                // Генерируем XML
                $xml = generateProductsValueTableXML($products, $db);

                if (empty($xml)) {
                    throw new Exception('Ошибка генерации XML');
                }

                error_log("[PRODUCTS] XML generated, size: " . strlen($xml) . " bytes");

                // Путь для сохранения
                $exportPath = $rootDir . '/1c_exchange/export/';

                if (!is_dir($exportPath)) {
                    if (!mkdir($exportPath, 0777, true)) {
                        throw new Exception('Не удалось создать директорию: ' . $exportPath);
                    }
                    chmod($exportPath, 0777);
                    error_log("[PRODUCTS] Directory created: {$exportPath}");
                }

                $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.xml';
                $filepath = $exportPath . $filename;

                error_log("[PRODUCTS] Saving to: {$filepath}");

                // ✅ КРИТИЧНО: Запись файла БЕЗ exit() до этого момента
                $written = @file_put_contents($filepath, $xml);

                if ($written === false) {
                    throw new Exception('Не удалось записать файл: ' . $filepath . ' (проверьте права доступа)');
                }

                if (!file_exists($filepath)) {
                    throw new Exception('Файл не создан после записи: ' . $filepath);
                }

                $actualSize = filesize($filepath);
                error_log("[PRODUCTS] File written: {$written} bytes");
                error_log("[PRODUCTS] File verified: {$actualSize} bytes");
                error_log("[PRODUCTS] ==================== EXPORT SUCCESS ====================");

                // ✅ Успешный ответ и exit() ТОЛЬКО ЗДЕСЬ
                echo json_encode([
                    'success' => true,
                    'message' => 'Товары экспортированы в 1С',
                    'file' => $filename,
                    'path' => '/1c_exchange/export/' . $filename,
                    'full_path' => $filepath,
                    'count' => count($products),
                    'size' => $actualSize
                ], JSON_UNESCAPED_UNICODE);
                exit;

            // ═══════════════════════════════════════════════════════════════
            // ИМПОРТ ИЗ 1С
            // ═══════════════════════════════════════════════════════════════
            case 'import_from_1c':
                if (!isset($_FILES['file'])) {
                    throw new Exception('Файл не загружен');
                }

                if (!class_exists('FileProcessor')) {
                    throw new Exception('FileProcessor не подключён');
                }

                $processor = new FileProcessor($db, $rootDir);
                $result = $processor->processUploadedFile($_FILES['file']);

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                exit;

            default:
                throw new Exception('Неизвестное действие: ' . $action);
        }

    } catch (Exception $e) {
        error_log("[PRODUCTS ERROR] " . $e->getMessage());
        error_log("[PRODUCTS ERROR] " . $e->getTraceAsString());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ФУНКЦИЯ: ГЕНЕРАЦИЯ XML (ОДИН ФАЙЛ ДЛЯ ВСЕХ ТОВАРОВ)
// ═══════════════════════════════════════════════════════════════════════════

function generateProductsValueTableXML($products, $db) {
    error_log("[PRODUCTS XML] Generating XML for " . count($products) . " products");

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<ValueTable xmlns="http://v8.1c.ru/8.1/data/core" ';
    $xml .= 'xmlns:xs="http://www.w3.org/2001/XMLSchema" ';
    $xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . "\n";

    // Колонки
    $columns = [
        ['id', 'ID', 'xs:string'],
        ['Наименование', 'Наименование', 'xs:string'],
        ['Родитель', 'Родитель (GUID)', 'xs:string'],
        ['Цена', 'Цена', 'xs:decimal'],
        ['Состав', 'Состав', 'xs:string'],
        ['ЕдиницаХраненияОстатковВес', 'Вес', 'xs:decimal'],
        ['Описание', 'Описание', 'xs:string'],
        ['Изображение', 'Изображение', 'xs:string'],
        ['Остаток', 'Остаток', 'xs:int'],
        ['Новинка', 'Новинка', 'xs:boolean'],
        ['Популярный', 'Популярный', 'xs:boolean'],
        ['ЗапретитьКЗаказу', 'Запретить к заказу', 'xs:boolean']
    ];

    error_log("[PRODUCTS XML] Adding " . count($columns) . " columns");

    foreach ($columns as $col) {
        $xml .= '  <column>' . "\n";
        $xml .= '    <Name>' . htmlspecialchars($col[0], ENT_XML1) . '</Name>' . "\n";
        $xml .= '    <Title>' . htmlspecialchars($col[1], ENT_XML1) . '</Title>' . "\n";
        $xml .= '    <ValueType><Type>' . $col[2] . '</Type></ValueType>' . "\n";
        $xml .= '  </column>' . "\n";
    }

    // Строки
    $rowIndex = 0;
    foreach ($products as $product) {
        $rowIndex++;
        error_log("[PRODUCTS XML] Adding row {$rowIndex} for product: " . ($product['name'] ?? $product['id']));

        $xml .= '  <row>' . "\n";

        // 1. ID
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($product['external_id'] ?? $product['id'], ENT_XML1) . '</Value>' . "\n";

        // 2. Наименование
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($product['name'] ?? '', ENT_XML1) . '</Value>' . "\n";

        // 3. Родитель (GUID категории)
        $parentGuid = '';
        if (!empty($product['category_id'])) {
            $category = $db->find('categories', $product['category_id']);
            if ($category) {
                $parentGuid = $category['external_id'] ?? '';
            }
        }
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($parentGuid, ENT_XML1) . '</Value>' . "\n";

        // 4. Цена
        $price = number_format(floatval($product['price'] ?? 0), 2, '.', '');
        $xml .= '    <Value xsi:type="xs:decimal">' . $price . '</Value>' . "\n";

        // 5. Состав
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($product['composition'] ?? '', ENT_XML1) . '</Value>' . "\n";

        // 6. Вес
        $weight = number_format(floatval($product['weight'] ?? 0), 2, '.', '');
        $xml .= '    <Value xsi:type="xs:decimal">' . $weight . '</Value>' . "\n";

        // 7. Описание
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($product['description'] ?? '', ENT_XML1) . '</Value>' . "\n";

        // 8. Изображение (с проверкой существования)
        $imagePath = $product['image'] ?? '';
        if (!empty($imagePath)) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
            if (!file_exists($fullPath)) {
                error_log("[PRODUCTS XML] Missing image: {$fullPath}");
                $imagePath = ''; // Сбрасываем путь если файла нет
            }
        }
        $xml .= '    <Value xsi:type="xs:string">' . htmlspecialchars($imagePath, ENT_XML1) . '</Value>' . "\n";

        // 9. Остаток
        $stock = ($product['unlimited_stock'] ?? false) ? 9999 : intval($product['stock_quantity'] ?? 0);
        $xml .= '    <Value xsi:type="xs:int">' . $stock . '</Value>' . "\n";

        // 10. Новинка
        $isNew = ($product['is_new'] ?? false) ? 'true' : 'false';
        $xml .= '    <Value xsi:type="xs:boolean">' . $isNew . '</Value>' . "\n";

        // 11. Популярный
        $isPopular = ($product['is_popular'] ?? false) ? 'true' : 'false';
        $xml .= '    <Value xsi:type="xs:boolean">' . $isPopular . '</Value>' . "\n";

        // 12. ЗапретитьКЗаказу
        $isClosed = ($product['is_closed'] ?? false) ? 'true' : 'false';
        $xml .= '    <Value xsi:type="xs:boolean">' . $isClosed . '</Value>' . "\n";

        $xml .= '  </row>' . "\n";
    }

    $xml .= '</ValueTable>';

    error_log("[PRODUCTS XML] XML complete. Size: " . strlen($xml) . " bytes, Rows: {$rowIndex}");

    return $xml;
}

// ═══════════════════════════════════════════════════════════════════════════
// ПОЛУЧЕНИЕ ДАННЫХ ДЛЯ ОТОБРАЖЕНИЯ
// ═══════════════════════════════════════════════════════════════════════════

// Фильтрация
$filterCategory = $_GET['filter_category'] ?? 'all';
$filterStatus = $_GET['filter_status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

$products = $db->findAll('products') ?: [];

// Применяем фильтр по категории
if ($filterCategory !== 'all') {
    $products = array_filter($products, function($product) use ($filterCategory) {
        return ($product['category_id'] ?? '') === $filterCategory;
    });
}

// Применяем фильтр по статусу
if ($filterStatus !== 'all') {
    $products = array_filter($products, function($product) use ($filterStatus) {
        switch ($filterStatus) {
            case 'new':
                return ($product['is_new'] ?? false);
            case 'popular':
                return ($product['is_popular'] ?? false);
            case 'closed':
                return ($product['is_closed'] ?? false);
            case 'open':
                return !($product['is_closed'] ?? false);
            case 'visible':
                return ($product['is_visible'] ?? true);
            case 'hidden':
                return !($product['is_visible'] ?? true);
            default:
                return true;
        }
    });
}

// Применяем поиск
if (!empty($searchQuery)) {
    $products = array_filter($products, function($product) use ($searchQuery) {
        $search = mb_strtolower($searchQuery);
        $name = mb_strtolower($product['name'] ?? '');
        $externalId = mb_strtolower($product['external_id'] ?? '');

        return strpos($name, $search) !== false || strpos($externalId, $search) !== false;
    });
}

// Сортировка
usort($products, function($a, $b) {
    return strcmp($a['name'] ?? '', $b['name'] ?? '');
});

// Получаем все категории
$categories = $db->findAll('categories') ?: [];

// Статистика
$stats = [
    'total' => count($db->findAll('products') ?: []),
    'new' => 0,
    'popular' => 0,
    'closed' => 0,
    'open' => 0,
    'total_value' => 0
];

$allProducts = $db->findAll('products') ?: [];
foreach ($allProducts as $product) {
    if ($product['is_new'] ?? false) $stats['new']++;
    if ($product['is_popular'] ?? false) $stats['popular']++;
    if ($product['is_closed'] ?? false) $stats['closed']++;
    if (!($product['is_closed'] ?? false)) $stats['open']++;
    $stats['total_value'] += floatval($product['price'] ?? 0);
}

?>

<style>
.products-section { background: #fff; border-radius: 4px; border: 1px solid #e5e7eb; overflow: hidden; }
.products-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.products-header-left h2 { font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 2px 0; }
.products-header-left .subtitle { font-size: 11px; color: #6b7280; margin: 0; }
.products-header-actions { display: flex; gap: 8px; }
.action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #fff; border: 1px solid #d1d5db; border-radius: 3px; color: #374151; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.15s; text-decoration: none; }
.action-btn:hover { background: #f9fafb; border-color: #9ca3af; }
.action-btn.primary { background: #1f2937; color: white; border-color: #1f2937; }
.action-btn.primary:hover { background: #111827; }
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.action-btn svg { width: 14px; height: 14px; stroke-width: 2; }

.products-filters { display: flex; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; background: #fafafa; align-items: center; flex-wrap: wrap; }
.filter-group { display: flex; gap: 8px; align-items: center; }
.filter-label { font-size: 12px; color: #6b7280; font-weight: 500; }
.filter-select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 3px; font-size: 12px; background: white; color: #374151; }
.search-input { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 3px; font-size: 12px; width: 200px; }

.products-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1px; background: #e5e7eb; border-bottom: 1px solid #e5e7eb; }
.stat-card { background: white; padding: 14px 16px; text-align: center; }
.stat-value { font-size: 22px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0; }
.stat-label { font-size: 10px; color: #6b7280; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

.products-table-container { padding: 20px; overflow-x: auto; }
.products-table { width: 100%; border-collapse: collapse; }
.products-table thead { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
.products-table th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.products-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.1s; }
.products-table tbody tr:hover { background: #fafafa; }
.products-table td { padding: 12px; font-size: 12px; color: #374151; vertical-align: middle; }

.product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
.product-name { font-weight: 600; color: #1f2937; margin-bottom: 2px; }
.product-external-id { font-size: 10px; color: #6b7280; font-family: 'Consolas', monospace; }

.status-toggle { cursor: pointer; user-select: none; padding: 2px 8px; border-radius: 3px; font-size: 11px; display: inline-block; transition: all 0.15s; }
.status-toggle.active { background: #dcfce7; color: #166534; font-weight: 600; }
.status-toggle.inactive { background: #fee2e2; color: #991b1b; }

.price-input, .stock-input { padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 3px; font-size: 12px; width: 80px; text-align: right; }
.price-input:focus, .stock-input:focus { outline: none; border-color: #3b82f6; }

.category-select { padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 3px; font-size: 11px; background: white; }

.product-checkbox { width: 16px; height: 16px; cursor: pointer; }

.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; stroke: #d1d5db; }
.empty-state h3 { font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 8px 0; }
.empty-state p { font-size: 13px; color: #6b7280; margin: 0; }

#fileUploadInput { display: none; }

/* Модальное окно */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
.modal.active { display: flex; align-items: center; justify-content: center; }
.modal-content { background-color: #fefefe; margin: auto; padding: 0; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 800px; max-height: 90vh; overflow: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
.modal-header h3 { margin: 0; font-size: 18px; font-weight: 600; color: #1f2937; }
.modal-close { color: #6b7280; font-size: 28px; font-weight: bold; cursor: pointer; border: none; background: none; }
.modal-close:hover { color: #1f2937; }
.modal-body { padding: 24px; }

.product-details-grid { display: grid; grid-template-columns: 200px 1fr; gap: 24px; margin-bottom: 24px; }
.product-image-large { width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; }
.product-info { }
.detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
.detail-label { font-size: 12px; color: #6b7280; font-weight: 600; }
.detail-value { font-size: 13px; color: #1f2937; }

.bulk-actions { padding: 12px 20px; background: #fef3c7; border-bottom: 1px solid #fbbf24; display: none; align-items: center; gap: 12px; }
.bulk-actions.active { display: flex; }
.bulk-actions-label { font-size: 12px; color: #92400e; font-weight: 600; }
</style>

<div class="products-section">
    <div class="products-header">
        <div class="products-header-left">
            <h2>🛍️ Управление товарами</h2>
            <p class="subtitle">Редактирование, экспорт и синхронизация с 1С</p>
        </div>
        <div class="products-header-actions">
            <button class="action-btn" onclick="openFileUpload()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Импорт из 1С
            </button>
            <button class="action-btn primary" onclick="exportSelectedProducts()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                Экспорт в 1С
            </button>
        </div>
    </div>

    <div class="bulk-actions" id="bulkActions">
        <span class="bulk-actions-label">Выбрано: <span id="selectedCount">0</span></span>
        <button class="action-btn" onclick="showBulkCategoryModal()">Изменить категорию</button>
        <button class="action-btn" onclick="bulkToggleClosed(false)">Открыть</button>
        <button class="action-btn" onclick="bulkToggleClosed(true)">Закрыть</button>
        <button class="action-btn" onclick="bulkDelete()" style="color: #dc2626;">Удалить</button>
    </div>

    <div class="products-filters">
        <div class="filter-group">
            <label class="filter-label">Категория:</label>
            <select class="filter-select" id="filterCategory" onchange="applyFilters()">
                <option value="all" <?= $filterCategory === 'all' ? 'selected' : '' ?>>Все</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['id']) ?>" <?= $filterCategory === $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Статус:</label>
            <select class="filter-select" id="filterStatus" onchange="applyFilters()">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Все</option>
                <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>>Новинки</option>
                <option value="popular" <?= $filterStatus === 'popular' ? 'selected' : '' ?>>Популярные</option>
                <option value="open" <?= $filterStatus === 'open' ? 'selected' : '' ?>>Открытые</option>
                <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Закрытые</option>
                <option value="visible" <?= $filterStatus === 'visible' ? 'selected' : '' ?>>Видимые</option>
                <option value="hidden" <?= $filterStatus === 'hidden' ? 'selected' : '' ?>>Скрытые</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Поиск:</label>
            <input type="text" class="search-input" id="searchQuery" placeholder="Название или ID..." value="<?= htmlspecialchars($searchQuery) ?>" onkeyup="handleSearchKeyup(event)">
        </div>
    </div>

    <div class="products-stats">
        <div class="stat-card"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Всего товаров</div></div>
        <div class="stat-card"><div class="stat-value"><?= $stats['new'] ?></div><div class="stat-label">Новинки</div></div>
        <div class="stat-card"><div class="stat-value"><?= $stats['popular'] ?></div><div class="stat-label">Популярные</div></div>
        <div class="stat-card"><div class="stat-value"><?= $stats['open'] ?></div><div class="stat-label">Открытые</div></div>
        <div class="stat-card"><div class="stat-value"><?= $stats['closed'] ?></div><div class="stat-label">Закрытые</div></div>
        <div class="stat-card"><div class="stat-value"><?= number_format($stats['total_value'], 0, '', ' ') ?> ₽</div><div class="stat-label">Общая стоимость</div></div>
    </div>

    <div class="products-table-container">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <h3>Нет товаров</h3>
                <p>Товары не найдены по заданным фильтрам</p>
            </div>
        <?php else: ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                        <th>Фото</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Остаток</th>
                        <th>Статусы</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" class="product-checkbox" data-product-id="<?= htmlspecialchars($product['id']) ?>" onchange="updateBulkActions()"></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="" class="product-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 font-size=%2240%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📦%3C/text%3E%3C/svg%3E'">
                                <?php else: ?>
                                    <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: #f3f4f6; font-size: 24px;">📦</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($product['name'] ?? 'Без названия') ?></div>
                                <div class="product-external-id"><?= htmlspecialchars($product['external_id'] ?? '') ?></div>
                            </td>
                            <td>
                                <select class="category-select" onchange="changeProductCategory('<?= htmlspecialchars($product['id']) ?>', this.value)">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>" <?= ($product['category_id'] ?? '') === $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="price-input" value="<?= number_format(floatval($product['price'] ?? 0), 2, '.', '') ?>" 
                                       onblur="updatePrice('<?= htmlspecialchars($product['id']) ?>', this.value)" step="0.01" min="0">
                            </td>
                            <td>
                                <?php if ($product['unlimited_stock'] ?? false): ?>
                                    <span class="status-toggle active" onclick="toggleUnlimitedStock('<?= htmlspecialchars($product['id']) ?>', this)">∞</span>
                                <?php else: ?>
                                    <input type="number" class="stock-input" value="<?= intval($product['stock_quantity'] ?? 0) ?>" 
                                           onblur="updateStock('<?= htmlspecialchars($product['id']) ?>', this.value)" min="0">
                                    <span style="cursor: pointer; margin-left: 4px; font-size: 16px;" onclick="toggleUnlimitedStock('<?= htmlspecialchars($product['id']) ?>', this)" title="Сделать неограниченным">∞</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-toggle <?= ($product['is_closed'] ?? false) ? 'inactive' : 'active' ?>" 
                                      onclick="toggleStatus('<?= htmlspecialchars($product['id']) ?>', 'closed', this)"
                                      title="Открыт/Закрыт">
                                    <?= ($product['is_closed'] ?? false) ? '🔒' : '✅' ?>
                                </span>
                                <span class="status-toggle <?= ($product['is_new'] ?? false) ? 'active' : 'inactive' ?>" 
                                      onclick="toggleStatus('<?= htmlspecialchars($product['id']) ?>', 'new', this)"
                                      title="Новинка">
                                    🆕
                                </span>
                                <span class="status-toggle <?= ($product['is_popular'] ?? false) ? 'active' : 'inactive' ?>" 
                                      onclick="toggleStatus('<?= htmlspecialchars($product['id']) ?>', 'popular', this)"
                                      title="Популярный">
                                    ⭐
                                </span>
                                <span class="status-toggle <?= ($product['is_visible'] ?? true) ? 'active' : 'inactive' ?>" 
                                      onclick="toggleStatus('<?= htmlspecialchars($product['id']) ?>', 'visible', this)"
                                      title="Видимость">
                                    👁️
                                </span>
                            </td>
                            <td>
                                <button class="action-btn" onclick="showProductDetails('<?= htmlspecialchars($product['id']) ?>')" style="padding: 4px 8px; margin-right: 4px;" title="Детали">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button class="action-btn" onclick="exportSingleProduct('<?= htmlspecialchars($product['id']) ?>')" style="padding: 4px 8px; margin-right: 4px;" title="Экспорт в 1С">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                                </button>
                                <button class="action-btn" onclick="deleteProduct('<?= htmlspecialchars($product['id']) ?>')" style="padding: 4px 8px; color: #dc2626;" title="Удалить">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно деталей товара -->
<div id="productDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Детали товара</h3>
            <button class="modal-close" onclick="closeProductDetails()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Содержимое загружается динамически -->
        </div>
    </div>
</div>

<!-- Модальное окно массовой смены категории -->
<div id="bulkCategoryModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Изменить категорию</h3>
            <button class="modal-close" onclick="closeBulkCategoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 16px; font-size: 13px; color: #6b7280;">Выберите новую категорию для выбранных товаров:</p>
            <select id="bulkCategorySelect" class="filter-select" style="width: 100%; padding: 10px;">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['id']) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button class="action-btn" onclick="closeBulkCategoryModal()">Отмена</button>
                <button class="action-btn primary" onclick="confirmBulkCategoryChange()">Применить</button>
            </div>
        </div>
    </div>
</div>

<input type="file" id="fileUploadInput" accept=".xml" onchange="handleFileUpload(event)">

<script>
// ═══════════════════════════════════════════════════════════════
// ФИЛЬТРЫ И ПОИСК
// ═══════════════════════════════════════════════════════════════

function applyFilters() {
    const category = document.getElementById('filterCategory').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('searchQuery').value;

    const params = new URLSearchParams();
    if (category !== 'all') params.append('filter_category', category);
    if (status !== 'all') params.append('filter_status', status);
    if (search) params.append('search', search);

    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function handleSearchKeyup(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

// ═══════════════════════════════════════════════════════════════
// ПЕРЕКЛЮЧЕНИЕ СТАТУСОВ
// ═══════════════════════════════════════════════════════════════

function toggleStatus(productId, statusType, element) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'toggle_status');
    formData.append('product_id', productId);
    formData.append('status_type', statusType);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Переключаем визуально
            if (result.new_value) {
                element.classList.remove('inactive');
                element.classList.add('active');
            } else {
                element.classList.remove('active');
                element.classList.add('inactive');
            }

            // Для закрыт/открыт меняем иконку
            if (statusType === 'closed') {
                element.textContent = result.new_value ? '🔒' : '✅';
            }
        } else {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ИЗМЕНЕНИЕ ЦЕНЫ
// ═══════════════════════════════════════════════════════════════

function updatePrice(productId, newPrice) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'update_price');
    formData.append('product_id', productId);
    formData.append('new_price', newPrice);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ИЗМЕНЕНИЕ ОСТАТКА
// ═══════════════════════════════════════════════════════════════

function updateStock(productId, newStock) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'update_stock');
    formData.append('product_id', productId);
    formData.append('new_stock', newStock);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ПЕРЕКЛЮЧЕНИЕ "НЕОГРАНИЧЕННЫЙ ОСТАТОК"
// ═══════════════════════════════════════════════════════════════

function toggleUnlimitedStock(productId, element) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'toggle_unlimited_stock');
    formData.append('product_id', productId);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// СМЕНА КАТЕГОРИИ
// ═══════════════════════════════════════════════════════════════

function changeProductCategory(productId, newCategoryId) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'change_category');
    formData.append('product_id', productId);
    formData.append('new_category_id', newCategoryId);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            alert('❌ ' + result.error);
            location.reload();
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ДЕТАЛИ ТОВАРА
// ═══════════════════════════════════════════════════════════════

function showProductDetails(productId) {
    const formData = new FormData();
    formData.append('ajax_products_action', 'get_product_details');
    formData.append('product_id', productId);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            renderProductDetails(result.product);
            document.getElementById('productDetailsModal').classList.add('active');
        } else {
            alert('❌ ' + result.error);
        }
    });
}

function renderProductDetails(product) {
    document.getElementById('modalTitle').textContent = product.name;

    const imageUrl = product.image || 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%22100%22 y=%22100%22 font-size=%2260%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📦%3C/text%3E%3C/svg%3E';

    let html = `
        <div class="product-details-grid">
            <div>
                <img src="${imageUrl}" alt="" class="product-image-large" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%22100%22 y=%22100%22 font-size=%2260%22 text-anchor=%22middle%22 dy=%22.3em%22%3E📦%3C/text%3E%3C/svg%3E'">
            </div>
            <div class="product-info">
                <div class="detail-row">
                    <span class="detail-label">External ID:</span>
                    <span class="detail-value">${product.external_id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Категория:</span>
                    <span class="detail-value">${product.category_name || product.parent_name || '—'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Цена:</span>
                    <span class="detail-value">${product.price} ₽</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Вес:</span>
                    <span class="detail-value">${product.weight} г</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Остаток:</span>
                    <span class="detail-value">${product.unlimited_stock ? '∞ Неограниченный' : product.stock_quantity}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Статусы:</span>
                    <span class="detail-value">
                        ${product.is_closed ? '🔒 Закрыт' : '✅ Открыт'}
                        ${product.is_new ? ' • 🆕 Новинка' : ''}
                        ${product.is_popular ? ' • ⭐ Популярный' : ''}
                        ${product.is_visible ? ' • 👁️ Видимый' : ' • 🙈 Скрыт'}
                    </span>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Состав:</h4>
            <p style="font-size: 13px; color: #374151; line-height: 1.6;">${product.composition || 'Не указан'}</p>
        </div>

        <div style="margin-top: 20px;">
            <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Описание:</h4>
            <p style="font-size: 13px; color: #374151; line-height: 1.6;">${product.description || 'Не указано'}</p>
        </div>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 11px; color: #6b7280;">
            <div>Создан: ${product.created_at}</div>
            <div>Обновлён: ${product.updated_at}</div>
        </div>
    `;

    document.getElementById('modalBody').innerHTML = html;
}

function closeProductDetails() {
    document.getElementById('productDetailsModal').classList.remove('active');
}

// ═══════════════════════════════════════════════════════════════
// УДАЛЕНИЕ ТОВАРА
// ═══════════════════════════════════════════════════════════════

function deleteProduct(productId) {
    if (!confirm('Удалить товар?\n\nЭто действие нельзя отменить.')) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_products_action', 'delete_product');
    formData.append('product_id', productId);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// МАССОВЫЕ ОПЕРАЦИИ
// ═══════════════════════════════════════════════════════════════

function updateBulkActions() {
    const selected = document.querySelectorAll('.product-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    if (selected.length > 0) {
        bulkActions.classList.add('active');
        selectedCount.textContent = selected.length;
    } else {
        bulkActions.classList.remove('active');
    }
}

function showBulkCategoryModal() {
    document.getElementById('bulkCategoryModal').classList.add('active');
}

function closeBulkCategoryModal() {
    document.getElementById('bulkCategoryModal').classList.remove('active');
}

function confirmBulkCategoryChange() {
    const productIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.dataset.productId);
    const newCategoryId = document.getElementById('bulkCategorySelect').value;

    if (!confirm(`Изменить категорию для ${productIds.length} товаров?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_products_action', 'bulk_change_category');
    formData.append('product_ids', JSON.stringify(productIds));
    formData.append('new_category_id', newCategoryId);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });
}

function bulkToggleClosed(setClosed) {
    const productIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.dataset.productId);

    if (productIds.length === 0) {
        alert('❌ Выберите товары!');
        return;
    }

    if (!confirm(`${setClosed ? 'Закрыть' : 'Открыть'} ${productIds.length} товаров?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_products_action', 'bulk_toggle_closed');
    formData.append('product_ids', JSON.stringify(productIds));
    formData.append('set_closed', setClosed);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });
}

function bulkDelete() {
    const productIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.dataset.productId);

    if (productIds.length === 0) {
        alert('❌ Выберите товары!');
        return;
    }

    if (!confirm(`Удалить ${productIds.length} товаров?\n\nЭто действие нельзя отменить!`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_products_action', 'bulk_delete');
    formData.append('product_ids', JSON.stringify(productIds));

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ЭКСПОРТ В 1С
// ═══════════════════════════════════════════════════════════════

function exportSingleProduct(productId) {
    if (!confirm('Экспортировать этот товар в 1С?')) return;

    const formData = new FormData();
    formData.append('ajax_products_action', 'export_to_1c');
    formData.append('product_ids', JSON.stringify([productId]));

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message + '\n\nФайл: ' + result.file);
        } else {
            alert('❌ ' + result.error);
        }
    });
}

function exportSelectedProducts() {
    const productIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.dataset.productId);

    if (productIds.length === 0) {
        alert('❌ Выберите товары для экспорта!');
        return;
    }

    if (!confirm(`Экспортировать ${productIds.length} товаров в 1С?`)) return;

    const formData = new FormData();
    formData.append('ajax_products_action', 'export_to_1c');
    formData.append('product_ids', JSON.stringify(productIds));

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message + '\n\nФайл: ' + result.file + '\nТоваров: ' + result.count);
        } else {
            alert('❌ ' + result.error);
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// ИМПОРТ ИЗ 1С
// ═══════════════════════════════════════════════════════════════

function openFileUpload() { 
    document.getElementById('fileUploadInput').click(); 
}

function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('ajax_products_action', 'import_from_1c');

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.error);
        }
    });

    event.target.value = '';
}

// ═══════════════════════════════════════════════════════════════
// ВЫДЕЛЕНИЕ ВСЕХ
// ═══════════════════════════════════════════════════════════════

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// Закрытие модалок по клику вне их
window.onclick = function(event) {
    const productModal = document.getElementById('productDetailsModal');
    const categoryModal = document.getElementById('bulkCategoryModal');

    if (event.target === productModal) {
        closeProductDetails();
    }
    if (event.target === categoryModal) {
        closeBulkCategoryModal();
    }
}

console.log('✅ Products.php v34.1 FIXED loaded - Export working!');
</script>
