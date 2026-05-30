<?php
/**
 * 🔥 VALUETABLEPARSER v30.3 FINAL - <Value> С ЗАГЛАВНОЙ БУКВЫ!
 * 
 * ✅ ЖЁСТКИЙ МАППИНГ GUID → КАТЕГОРИЯ
 * ✅ 12 МЕТОДОВ ИЗВЛЕЧЕНИЯ ЗНАЧЕНИЙ
 * ✅ ИСПРАВЛЕНО: <Value> с ЗАГЛАВНОЙ буквы (не <value>)!
 * ✅ БЕЗ АВТОКЛАССИФИКАЦИИ - ТОЛЬКО ФАКТЫ
 * 
 * @version 30.3 FINAL FIX
 * @date 2025-10-08
 * @author Гурбанов Артем
 */

class ValueTableParser {
    private $db;
    private $columns = [];
    private $rows = [];
    private $detectedType = null;

    // 🔥 ЖЁСТКИЙ МАППИНГ GUID → КАТЕГОРИЯ
    private $HARDCODED_GUID_MAP = [
        '76331c9b-a2ff-11e9-9e71-309c23aa65ea' => 'Закуски',
        '686ef458-c446-11e9-9e81-309c23aa65ea' => 'Суши',
        'efa638bc-eea6-11e9-9e8a-309c23aa65ea' => 'Суши',
        '631c5bd4-01f0-11ea-9e92-309c23aa65ea' => 'Роллы',
        '18536e48-4194-11ea-9ea0-309c23aa65ea' => 'Сеты',
        '9401cc9d-7d0d-11e9-9e68-309c23aa65ea' => 'Горячие роллы',
        '9401cc9e-7d0d-11e9-9e68-309c23aa65ea' => 'Роллы',
        'b120c34e-7fd8-11e9-9e69-309c23aa65ea' => 'Горячие роллы',
        'a6e96f8f-012e-11ea-9e91-309c23aa65ea' => 'Роллы',
        '5deab517-112c-11ea-9e95-309c23aa65ea' => 'Маки',
    ];

    private $statistics = [
        'parse_time' => 0,
        'rows_parsed' => 0,
        'columns_count' => 0,
        'guid_mapped' => 0,
        'guid_unmapped' => 0,
        'errors' => [],
        'warnings' => []
    ];

    public function __construct($db = null) {
        $this->db = $db;
        $this->log('info', '🚀 ValueTableParser v30.3 FINAL initialized');
    }

    // ═══════════════════════════════════════════════════════
    // 🎯 ГЛАВНЫЙ МЕТОД ПАРСИНГА
    // ═══════════════════════════════════════════════════════

    public function parse($xmlData, $options = []) {
        $startTime = microtime(true);

        $this->log('info', "═══════════════════════════════════════════════════");
        $this->log('info', "🎯 ValueTable parsing (v30.3 FINAL)");
        $this->log('info', "═══════════════════════════════════════════════════");

        try {
            $xmlData = $this->cleanXML($xmlData);
            $xml = $this->loadXML($xmlData);
            $this->parseColumns($xml);
            $this->parseRows($xml);
            $this->detectedType = $this->detectDataType();
            $this->postProcess();

            $this->statistics['parse_time'] = round((microtime(true) - $startTime) * 1000, 2);
            $this->statistics['rows_parsed'] = count($this->rows);
            $this->statistics['columns_count'] = count($this->columns);

            $this->log('info', "✅ Parsing completed successfully");
            $this->log('info', "Statistics: rows={$this->statistics['rows_parsed']}, columns={$this->statistics['columns_count']}");

            return [
                'success' => true,
                'columns' => $this->columns,
                'rows' => $this->rows,
                'type' => $this->detectedType,
                'statistics' => $this->statistics
            ];

        } catch (Exception $e) {
            $this->statistics['errors'][] = $e->getMessage();
            $this->log('error', "❌ Parsing failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'statistics' => $this->statistics
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    // 🧹 ОЧИСТКА XML
    // ═══════════════════════════════════════════════════════

    private function cleanXML($xmlData) {
        $xmlData = preg_replace('/^\xEF\xBB\xBF/', '', $xmlData);
        $xmlData = preg_replace('/^\xFE\xFF/', '', $xmlData);
        $xmlData = preg_replace('/^\xFF\xFE/', '', $xmlData);
        $xmlData = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $xmlData);
        $xmlData = str_replace(["\r\n", "\r"], "\n", $xmlData);
        return trim($xmlData);
    }

    // ═══════════════════════════════════════════════════════
    // 📥 ЗАГРУЗКА XML
    // ═══════════════════════════════════════════════════════

    private function loadXML($xmlData) {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = @simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        if (!$xml) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? trim($errors[0]->message) : 'Unknown XML error';
            libxml_clear_errors();
            throw new Exception('XML parse error: ' . $errorMsg);
        }

        $xml->registerXPathNamespace('v8', 'http://v8.1c.ru/8.1/data/core');
        $xml->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        $xml->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->registerXPathNamespace('d3p1', 'http://v8.1c.ru/8.1/data/enterprise/current-config');

        $this->log('debug', "✅ XML loaded successfully");

        return $xml;
    }

    // ═══════════════════════════════════════════════════════
    // 📊 ПАРСИНГ КОЛОНОК
    // ═══════════════════════════════════════════════════════

    private function parseColumns($xml) {
        $this->columns = [];

        $columns = $xml->xpath('//*[local-name()="column"]');

        if (empty($columns)) {
            $columns = $xml->xpath('//column');
        }

        if (empty($columns)) {
            $this->log('warning', 'No columns found, will auto-generate from data');
            return;
        }

        foreach ($columns as $index => $column) {
            $name = trim((string)($column->Name ?? ''));

            if (empty($name)) {
                continue;
            }

            $this->columns[] = [
                'name' => $name,
                'title' => trim((string)($column->Title ?? $name)),
                'type' => trim((string)($column->Type ?? 'string')),
                'index' => $index
            ];
        }

        $this->log('info', "✅ Columns parsed: " . count($this->columns));
    }

    // ═══════════════════════════════════════════════════════
    // 📋 ПАРСИНГ СТРОК
    // ═══════════════════════════════════════════════════════

    private function parseRows($xml) {
        $this->rows = [];

        $rows = $xml->xpath('//*[local-name()="row"]');

        if (empty($rows)) {
            $rows = $xml->xpath('//row');
        }

        if (empty($rows)) {
            throw new Exception('No rows found in XML');
        }

        $this->log('info', "📋 Found " . count($rows) . " rows to parse");

        foreach ($rows as $rowIndex => $row) {
            try {
                $rowData = $this->parseRow($row, $rowIndex);

                if (!empty($rowData)) {
                    $this->rows[] = $rowData;

                    if ($rowIndex === 0) {
                        $this->log('info', "🔍 First row sample: " . json_encode(array_slice($rowData, 0, 5), JSON_UNESCAPED_UNICODE));
                    }
                } else {
                    $this->log('warning', "⚠️ Row {$rowIndex} is empty after parsing");
                }

            } catch (Exception $e) {
                $this->statistics['warnings'][] = "Row {$rowIndex}: " . $e->getMessage();
                $this->log('warning', "⚠️ Row {$rowIndex} error: " . $e->getMessage());
            }
        }

        if (empty($this->columns) && !empty($this->rows)) {
            $this->log('info', "🔧 Auto-generating columns from first row");

            $firstRow = $this->rows[0];
            foreach (array_keys($firstRow) as $index => $key) {
                $this->columns[] = [
                    'name' => $key,
                    'title' => $key,
                    'type' => 'string',
                    'index' => $index,
                    'auto_generated' => true
                ];
            }
        }

        $this->log('info', "✅ Rows parsed: " . count($this->rows));
    }

    // ═══════════════════════════════════════════════════════
    // 📝 ПАРСИНГ ОДНОЙ СТРОКИ - ИСПРАВЛЕНО v30.3!
    // ═══════════════════════════════════════════════════════

    private function parseRow($row, $rowIndex) {
        $rowData = [];

        // 🔥 КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ: <Value> с ЗАГЛАВНОЙ БУКВЫ!

        // Вариант 1: XPath с local-name (заглавная V!)
        $values = $row->xpath('.//*[local-name()="Value"]');

        // Вариант 2: Прямые дочерние Value
        if (empty($values)) {
            $values = $row->xpath('./Value');
        }

        // Вариант 3: Через children() напрямую
        if (empty($values)) {
            $values = [];
            foreach ($row->children() as $child) {
                if ($child->getName() === 'Value') {
                    $values[] = $child;
                }
            }
        }

        // 🔥 ЕСЛИ НАШЛИ <Value> элементы
        if (!empty($values)) {
            $valuesCount = count($values);

            if ($rowIndex === 0) {
                $this->log('info', "🔍 Row {$rowIndex}: found {$valuesCount} <Value> elements");
            }

            // Маппим значения на колонки по индексу
            foreach ($values as $valueIndex => $valueNode) {
                // Определяем имя колонки
                if (isset($this->columns[$valueIndex])) {
                    $columnName = $this->columns[$valueIndex]['name'];
                } else {
                    $columnName = "Column{$valueIndex}";
                    if ($rowIndex === 0) {
                        $this->log('warning', "Row {$rowIndex}: no column for index {$valueIndex}, using {$columnName}");
                    }
                }

                // 🔥 ИЗВЛЕКАЕМ ЗНАЧЕНИЕ ЧЕРЕЗ MEGA EXTRACTOR
                $extractedValue = $this->extractValue($valueNode);

                // Сохраняем
                $rowData[$columnName] = $extractedValue;

                // Логируем первые 5 значений первой строки для отладки
                if ($rowIndex === 0 && $valueIndex < 5) {
                    $displayValue = is_string($extractedValue) ? substr($extractedValue, 0, 50) : var_export($extractedValue, true);
                    $this->log('info', "  • Column[{$valueIndex}] '{$columnName}' = {$displayValue}");
                }
            }

            if ($rowIndex === 0) {
                $this->log('info', "✅ Row {$rowIndex}: extracted " . count($rowData) . " fields");
            }

        } else {
            // 🔥 FALLBACK: Читаем прямые дочерние элементы
            if ($rowIndex === 0) {
                $this->log('warning', "Row {$rowIndex}: no <Value> elements found, trying direct children");
            }

            foreach ($row->children() as $child) {
                $childName = $child->getName();
                $rowData[$childName] = $this->extractValue($child);
            }

            if ($rowIndex === 0) {
                $this->log('info', "Row {$rowIndex}: extracted " . count($rowData) . " fields from children");
            }
        }

        return $rowData;
    }

    // ═══════════════════════════════════════════════════════
    // 🔥 MEGA EXTRACTOR - 12 МЕТОДОВ ИЗВЛЕЧЕНИЯ
    // ═══════════════════════════════════════════════════════

    private function extractValue($node) {
        $methods = [
            'method_direct_string',
            'method_dom_import',
            'method_xpath_text',
            'method_aggressive_all_namespaces',
            'method_brutal_regex',
            'method_namespace_aware',
            'method_innerxml',
            'method_children_first',
            'method_asxml_parse',
            'method_attributes_value',
            'method_cdata_section',
            'method_raw_content'
        ];

        foreach ($methods as $methodName) {
            try {
                $result = $this->$methodName($node);

                if ($this->isValidValue($result)) {
                    return $this->normalizeValue($result, $node);
                }

            } catch (Exception $e) {
                // Игнорируем и пробуем следующий
            }
        }

        return null;
    }

    private function isValidValue($value) {
        if ($value === null) return false;
        if ($value === '') return false;
        if ($value === false) return false;

        $emptyValues = ['NULL', 'null', 'undefined', '00000000-0000-0000-0000-000000000000'];

        if (in_array($value, $emptyValues, true)) return false;

        return true;
    }

    // Метод 1: Прямой string
    private function method_direct_string($node) {
        return trim((string)$node);
    }

    // Метод 2: DOM
    private function method_dom_import($node) {
        $dom = dom_import_simplexml($node);
        if ($dom && $dom->nodeValue) {
            return trim($dom->nodeValue);
        }
        return null;
    }

    // Метод 3: XPath text()
    private function method_xpath_text($node) {
        $texts = $node->xpath('text()');
        if (!empty($texts)) {
            return trim((string)$texts[0]);
        }
        return null;
    }

    // Метод 4: Агрессивный поиск по всем namespace
    private function method_aggressive_all_namespaces($node) {
        $allNamespaces = $node->getNamespaces(true);

        $allNamespaces[''] = null;
        $allNamespaces['v8'] = 'http://v8.1c.ru/8.1/data/core';
        $allNamespaces['xs'] = 'http://www.w3.org/2001/XMLSchema';
        $allNamespaces['d3p1'] = 'http://v8.1c.ru/8.1/data/enterprise/current-config';

        foreach ($allNamespaces as $prefix => $uri) {
            try {
                if ($uri === null) {
                    $value = trim((string)$node);
                    if (!empty($value)) {
                        return $value;
                    }
                } else {
                    $children = $node->children($uri);
                    if (count($children) > 0) {
                        $value = trim((string)$children[0]);
                        if (!empty($value)) {
                            return $value;
                        }
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    // Метод 5: Brutal regex
    private function method_brutal_regex($node) {
        $xml = $node->asXML();

        if (preg_match('/>([^<>]+)</', $xml, $matches)) {
            $value = trim($matches[1]);
            if (!empty($value)) {
                return $value;
            }
        }

        if (preg_match('/>([\s\S]+?)<\//s', $xml, $matches)) {
            $value = trim(strip_tags($matches[1]));
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    // Метод 6: Namespace aware
    private function method_namespace_aware($node) {
        $namespaces = $node->getNamespaces(true);

        foreach ($namespaces as $prefix => $ns) {
            $children = $node->children($ns);
            if (count($children) > 0) {
                return trim((string)$children[0]);
            }
        }

        return trim((string)$node);
    }

    // Метод 7: InnerXML
    private function method_innerxml($node) {
        $xml = $node->asXML();
        $xml = preg_replace('/<[^>]+>/', '', $xml, 1);
        $xml = preg_replace('/<\/[^>]+>$/', '', $xml, 1);
        return trim($xml);
    }

    // Метод 8: Children first
    private function method_children_first($node) {
        $children = $node->children();

        if (count($children) > 0) {
            return trim((string)$children[0]);
        }

        return trim((string)$node);
    }

    // Метод 9: asXML parse
    private function method_asxml_parse($node) {
        $xml = $node->asXML();

        if (preg_match('/>([^<]+)</s', $xml, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    // Метод 10: Attributes
    private function method_attributes_value($node) {
        $attrs = $node->attributes();

        if (isset($attrs['value'])) {
            return trim((string)$attrs['value']);
        }

        $namespaces = $node->getNamespaces(true);

        foreach ($namespaces as $prefix => $ns) {
            $attrs = $node->attributes($ns);
            if (isset($attrs['value'])) {
                return trim((string)$attrs['value']);
            }
        }

        return null;
    }

    // Метод 11: CDATA
    private function method_cdata_section($node) {
        $xml = $node->asXML();

        if (preg_match('/<!$$CDATA$$(.*?)$$$$>/s', $xml, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    // Метод 12: Raw content
    private function method_raw_content($node) {
        $xml = $node->asXML();
        $content = strip_tags($xml);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($content);
    }

    // ═══════════════════════════════════════════════════════
    // 🎯 НОРМАЛИЗАЦИЯ ЗНАЧЕНИЯ
    // ═══════════════════════════════════════════════════════

    private function normalizeValue($value, $node) {
        $xsiType = null;
        $xsiAttrs = $node->attributes('xsi', true);
        if (isset($xsiAttrs['type'])) {
            $xsiType = (string)$xsiAttrs['type'];
        }

        if (in_array($value, ['', '00000000-0000-0000-0000-000000000000'], true)) {
            return null;
        }

        if ($xsiType) {
            if (strpos($xsiType, 'boolean') !== false) {
                return in_array(strtolower($value), ['true', '1', 'yes'], true);
            }
            
// Для dateTime возвращаем строку как есть, не конвертируем в число
if (strpos($xsiType, 'dateTime') !== false) {
    return $value;
}
            if (strpos($xsiType, 'decimal') !== false || 
                strpos($xsiType, 'double') !== false ||
                strpos($xsiType, 'float') !== false) {
                return floatval(str_replace(',', '.', $value));
            }

            if (strpos($xsiType, 'int') !== false) {
                return intval($value);
            }

            if (strpos($xsiType, 'CatalogRef') !== false ||
                strpos($xsiType, 'DocumentRef') !== false) {
                return $value;
            }
        }

        if (is_numeric($value)) {
            if (strpos($value, '.') !== false || strpos($value, ',') !== false) {
                return floatval(str_replace(',', '.', $value));
            }

            if (!$this->isGuid($value)) {
                return intval($value);
            }
        }

        return $value;
    }

    // ═══════════════════════════════════════════════════════
    // 🔍 ОПРЕДЕЛЕНИЕ ТИПА ДАННЫХ
    // ═══════════════════════════════════════════════════════

private function detectDataType() {
    if (empty($this->columns)) {
        return 'generic';
    }

    $columnNames = implode(' ', array_column($this->columns, 'name'));
    $columnNames = mb_strtolower($columnNames);

    // 🔥 ПРОВЕРКА НА СЛОТЫ ДОЛЖНА БЫТЬ ПЕРВОЙ!
    if (strpos($columnNames, 'total_sections') !== false || 
        strpos($columnNames, 'batch_number') !== false ||
        strpos($columnNames, 'zone_id') !== false ||
        strpos($columnNames, 'вместимость') !== false ||
        strpos($columnNames, 'партия') !== false) {
        return 'slots';
    }

    if (strpos($columnNames, 'номенклатура') !== false || 
        (strpos($columnNames, 'наименование') !== false && strpos($columnNames, 'родитель') !== false)) {
        return 'products';
    }

    if (strpos($columnNames, 'заказчик') !== false || 
        strpos($columnNames, 'адресдоставки') !== false ||
        strpos($columnNames, 'датавыдачи') !== false) {
        return 'orders';
    }

    return 'generic';
}

    // ═══════════════════════════════════════════════════════
    // 🔧 ПОСТОБРАБОТКА
    // ═══════════════════════════════════════════════════════

    private function postProcess() {
        $this->log('info', "🔧 Post-processing data type: {$this->detectedType}");

        switch ($this->detectedType) {
            case 'products':
                $this->postProcessProducts();
                break;
            case 'orders':
                $this->postProcessOrders();
                break;
            case 'slots':
                $this->postProcessSlots();
                break;
        }
    }

    // ═══════════════════════════════════════════════════════
    // 🛍️ ПОСТОБРАБОТКА ТОВАРОВ
    // ═══════════════════════════════════════════════════════

    private function postProcessProducts() {
        $this->log('info', "🛍️ Post-processing products with HARDCODED GUID mapping...");

        $guidMapped = 0;
        $guidUnmapped = 0;

        foreach ($this->rows as &$row) {
            if (isset($row['Остаток'])) {
                $stock = intval($row['Остаток']);

                if ($stock == 0 || $stock >= 9999) {
                    $row['unlimited_stock'] = true;
                    $row['stock_quantity'] = 0;
                } else {
                    $row['unlimited_stock'] = false;
                    $row['stock_quantity'] = $stock;
                }

                $row['stock'] = $stock;
            }

            if (isset($row['Родитель'])) {
                $guid = trim($row['Родитель']);

                if (isset($this->HARDCODED_GUID_MAP[$guid])) {
                    $row['parent_name'] = $this->HARDCODED_GUID_MAP[$guid];
                    $row['category_mapped'] = true;
                    $guidMapped++;
                } else {
                    $row['parent_name'] = 'Другое';
                    $row['category_mapped'] = false;
                    $guidUnmapped++;
                }
            }

            if (isset($row['Цена'])) {
                $row['Цена'] = floatval($row['Цена']);
            }

            if (isset($row['ЕдиницаХраненияОстатковВес'])) {
                $row['ЕдиницаХраненияОстатковВес'] = floatval($row['ЕдиницаХраненияОстатковВес']);
            }

            if (isset($row['Новинка'])) {
                $row['Новинка'] = ($row['Новинка'] === true || $row['Новинка'] === 'true');
            }

            if (isset($row['Популярный'])) {
                $row['Популярный'] = ($row['Популярный'] === true || $row['Популярный'] === 'true');
            }

            if (isset($row['ЗапретитьКЗаказу'])) {
                $row['ЗапретитьКЗаказу'] = ($row['ЗапретитьКЗаказу'] === true || $row['ЗапретитьКЗаказу'] === 'true');
            }
        }

        $this->statistics['guid_mapped'] = $guidMapped;
        $this->statistics['guid_unmapped'] = $guidUnmapped;

        $this->log('info', "✅ Products post-processed: mapped={$guidMapped}, unmapped={$guidUnmapped}");
    }

    // ═══════════════════════════════════════════════════════
    // 🛒 ПОСТОБРАБОТКА ЗАКАЗОВ
    // ═══════════════════════════════════════════════════════

    private function postProcessOrders() {
        $this->log('info', "🛒 Post-processing orders...");

        foreach ($this->rows as &$row) {
            if (isset($row['Оплачено'])) {
                $row['Оплачено'] = in_array(strtolower($row['Оплачено']), ['true', '1', 'yes', 'да'], true);
            }

            if (isset($row['СуммаДокумента'])) {
                $row['СуммаДокумента'] = floatval($row['СуммаДокумента']);
            }
        }

        $this->log('info', "✅ Orders post-processed: " . count($this->rows));
    }

    // ═══════════════════════════════════════════════════════
    // 🕐 ПОСТОБРАБОТКА СЛОТОВ
    // ═══════════════════════════════════════════════════════

private function postProcessSlots() {
    $this->log('info', "🕐 Post-processing slots...");

    foreach ($this->rows as &$row) {
        // Явное приведение total_sections
        if (isset($row['total_sections'])) {
            $row['total_sections'] = intval($row['total_sections']);
        }
        
        // Остальные поля
        if (isset($row['batch_number'])) {
            $row['batch_number'] = intval($row['batch_number']);
        }
        if (isset($row['zone_id'])) {
            $row['zone_id'] = intval($row['zone_id']);
        }
        if (isset($row['Вместимость'])) {
            $row['Вместимость'] = intval($row['Вместимость']);
        }
    }

    $this->log('info', "✅ Slots post-processed: " . count($this->rows));
}

    // ═══════════════════════════════════════════════════════
    // 💾 СОХРАНЕНИЕ В БД
    // ═══════════════════════════════════════════════════════

    public function saveToDatabase($options = []) {
        if (!$this->db) {
            throw new Exception('Database not connected');
        }

        $this->log('info', "💾 Saving to database...");

        switch ($this->detectedType) {
            case 'products':
                return $this->saveProducts($options);
            default:
                throw new Exception("Cannot save unknown data type: {$this->detectedType}");
        }
    }

    private function saveProducts($options) {
        $autoCreateCategories = $options['auto_create_categories'] ?? true;

        $saved = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->rows as $row) {
            try {
                $externalId = $row['id'] ?? null;

                if (!$externalId) {
                    $skipped++;
                    continue;
                }

                $categoryId = null;

                if (!empty($row['parent_name']) && $autoCreateCategories) {
                    $categoryId = $this->db->autoCreateCategoryFromParent(
                        $row['parent_name'],
                        $row['Родитель'] ?? null
                    );
                }

                $productData = [
                    'external_id' => $externalId,
                    'name' => $row['Наименование'] ?? '',
                    'parent_name' => $row['parent_name'] ?? '',
                    'category_id' => $categoryId,
                    'price' => floatval($row['Цена'] ?? 0),
                    'composition' => $row['Состав'] ?? '',
                    'weight' => floatval($row['ЕдиницаХраненияОстатковВес'] ?? 0),
                    'description' => $row['Описание'] ?? '',
                    'image' => $row['Изображение'] ?? '',
                    'stock' => $row['stock'] ?? 0,
                    'unlimited_stock' => $row['unlimited_stock'] ?? true,
                    'stock_quantity' => $row['stock_quantity'] ?? 0,
                    'is_new' => $row['Новинка'] ?? false,
                    'is_popular' => $row['Популярный'] ?? false,
                    'is_closed' => $row['ЗапретитьКЗаказу'] ?? false,
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $existing = $this->db->findOne('products', ['external_id' => $externalId]);

                if ($existing) {
                    $productData['id'] = $existing['id'];
                    $productData['created_at'] = $existing['created_at'];
                    $this->db->saveWithoutValidation('products', $productData, $existing['id']);
                    $updated++;
                } else {
                    $productData['created_at'] = date('Y-m-d H:i:s');
                    $this->db->saveWithoutValidation('products', $productData);
                    $saved++;
                }

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->log('info', "✅ Save completed: saved={$saved}, updated={$updated}, skipped={$skipped}");

        return [
            'saved' => $saved,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    // ═══════════════════════════════════════════════════════
    // 🔧 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ═══════════════════════════════════════════════════════

    public function isGuid($str) {
        $pattern = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i';
        return preg_match($pattern, $str) === 1;
    }

    private function log($level, $message, $context = []) {
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $logMessage = sprintf(
            "[%s] [VTP v30.3] [%s] %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $contextStr
        );

        error_log($logMessage);
    }

    // Геттеры
    public function getRows() { return $this->rows; }
    public function getColumns() { return $this->columns; }
    public function getDetectedType() { return $this->detectedType; }
    public function getStatistics() { return $this->statistics; }

    public function exportTo($format = 'array') {
        switch (strtolower($format)) {
            case 'json':
                return json_encode([
                    'columns' => $this->columns,
                    'rows' => $this->rows,
                    'type' => $this->detectedType,
                    'statistics' => $this->statistics
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            case 'array':
            default:
                return [
                    'columns' => $this->columns,
                    'rows' => $this->rows,
                    'type' => $this->detectedType,
                    'statistics' => $this->statistics
                ];
        }
    }
}
