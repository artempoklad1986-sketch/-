<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * UNIFIED ADMIN PANEL v2.0 - MODULAR EDITION
 * Модульная админ-панель для ПРИНТСС.РФ с автозагрузкой модулей
 * 
 * Система Hot Module Loading:
 * - Автоматическое сканирование папки admin/modules/
 * - Уведомления о новых модулях
 * - Индикаторы здоровья модулей (🟢 OK / 🔴 ERROR)
 * - Метаданные через PHPDoc комментарии
 * ═══════════════════════════════════════════════════════════════════════════
 */

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Moscow');
session_start();

/* ═══════════════════════════════════════════════════════════════════════════
   PATHS & CONFIGURATION
   ═══════════════════════════════════════════════════════════════════════════ */
$BASE = __DIR__;
$MODULES_DIR = $BASE . '/admin/modules';
$uploadsDir = $BASE . '/uploads';
$ordersLog  = $BASE . '/orders.txt';
$printOrdersLog = $BASE . '/print_orders.txt';
$configFile = $BASE . '/config.json';
$productsFile = $BASE . '/products.json';
$servicesFile = $BASE . '/services.json';
$photoConfigFile = $BASE . '/photo_config.json';
$chatLog = $BASE . '/chat_log.txt';
$aiKnowledgeFile = $BASE . '/ai_knowledge.json';
$customersFile = $BASE . '/customers.json';
$reviewsFile = $BASE . '/reviews.json';

/* ═══════════════════════════════════════════════════════════════════════════
   FILE SYSTEM INITIALIZATION
   ═══════════════════════════════════════════════════════════════════════════ */
if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
if (!is_dir($MODULES_DIR)) @mkdir($MODULES_DIR, 0775, true);

if (!file_exists($uploadsDir.'/.htaccess')) {
  @file_put_contents($uploadsDir.'/.htaccess', 'php_flag engine off\nOptions -ExecCGI\n<FilesMatch \\.(php|phar|phtml|cgi|pl|py)$>\nDeny from all\n</FilesMatch>\n');
}

$requiredFiles = [
    $ordersLog, $printOrdersLog, $chatLog, $customersFile, $reviewsFile
];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        @file_put_contents($file, $file === $customersFile || $file === $reviewsFile ? '[]' : '');
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   DEFAULT CONFIGURATION
   ═══════════════════════════════════════════════════════════════════════════ */
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$siteURL = $scheme.$host;

$defaultConfig = [
  'brand'         => 'Копировальный фотоцентр ПРИНТСС',
  'slogan'        => 'Печать в Сосновом Бору — ул. Красных Фортов, 49',
  'hero'          => 'Фото на документы — 5 минут (электронный вид бесплатно)',
  'phone_display' => '+7 (952) 200-39-90',
  'phone_raw'     => '+79522003990',
  'email_to'      => 'artcopy78@bk.ru',
  'email_from'    => 'noreply@'.preg_replace('~^www\.~','',$host),
  'email_cc'      => '',
  'email_bcc'     => '',
  'email_reply'   => '',
  'address'       => 'Россия, Ленинградская обл., Сосновый Бор, ул. Красных Фортов, 49',
  'workhours'     => ['Пн–Пт 10:00–20:00', 'Сб–Вс 11:00–18:00'],
  'site'          => $siteURL,
  'logo'          => '',
  'hero_mode'     => 'svg',
  'hero_image'    => '',
  'catalog_desc'  => 'Каталог услуг нашей типографии и фотоцентра.',
  'homepage_features' => [
    'fast_photo' => true,
    'online_payment' => true,
    'delivery' => true,
    'photo_constructor' => true
  ],
  'seo' => [
    'title'       => 'Копировальный фотоцентр ПРИНТСС — типография | Фото 5 минут',
    'description' => 'Типография и фотоцентр: визитки, баннеры, листовки, фото на документы 5 минут.',
    'keywords'    => 'печать, типография, фотоцентр, визитки, баннеры',
    'og_image'    => '',
    'sitemap_enable' => true,
  ],
  'business' => [
    'show_requisites' => true,
    'show_payment_icons' => true,
    'legal_name' => 'ИП Гурбанова Галина Александровна',
    'inn' => '',
    'ogrn' => '',
    'bank' => '',
    'bik' => '',
    'account' => '',
  ],
  'yukassa' => [
    'enabled'  => false,
    'shop_id'  => '',
    'secret_key' => '',
    'test_mode' => true,
    'return_url' => $siteURL . '/oplata.php',
    'services' => [
      'products' => true,
      'photo_constructor' => true,
      'regular_orders' => false
    ]
  ],
  'theme' => [
    'mode'         => 'light',
    'bg'           => '#f3f7ff',
    'text'         => '#0e1220',
    'muted'        => '#5c6b84',
    'brand'        => '#FF8A00',
    'accent'       => '#2D5BFF',
    'card_opacity' => 0.65,
    'blur'         => 12,
    'radius'       => 16,
    'shadow'       => 0.12,
    'container'    => 1200
  ],
  'telegram'      => [
    'enabled' => true,
    'token'   => '8385005974:AAHhQkvdKP5LJSbSI-pge_TGefgcYDLTBZw',
    'chat'    => ''
  ],
  'smtp' => [
    'enabled' => true,
    'host'    => 'smtp.mail.ru',
    'port'    => 465,
    'secure'  => 'ssl',
    'user'    => 'artcopy78@bk.ru',
    'pass'    => ''
  ],
  'admin_pass' => 'printss49',
  'features' => [
    'reviews_enabled' => true,
    'callback_widget' => true,
    'price_alerts' => true,
    'loyalty_program' => false,
  ],
  'social' => [
    'vk' => '',
    'instagram' => '',
    'youtube' => '',
    'whatsapp' => '+79522003990',
    'telegram_channel' => '',
  ]
];

if (!file_exists($configFile)) @file_put_contents($configFile, json_encode($defaultConfig, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$cfg = json_decode(@file_get_contents($configFile), true);
if (!is_array($cfg)) $cfg = $defaultConfig;
$cfg = array_replace_recursive($defaultConfig, $cfg);

// Загружаем остальные данные
$products = json_decode(@file_get_contents($productsFile), true) ?? [];
$customers = json_decode(@file_get_contents($customersFile), true) ?? [];
$reviews = json_decode(@file_get_contents($reviewsFile), true) ?? [];

$defaultServices = [
  ['name' => 'Фото на документы', 'price' => 'от 400 ₽', 'description' => '5–10 минут. Электронная версия бесплатно.', 'enabled' => true, 'yukassa_enabled' => false],
  ['name' => 'Печать визиток', 'price' => 'от 900 ₽', 'description' => 'Мелованная бумага, быстрые сроки.', 'enabled' => true, 'yukassa_enabled' => false],
  ['name' => 'Печать баннеров', 'price' => 'от 1100 ₽', 'description' => 'ПВХ 440 г/м², люверсы, проварка.', 'enabled' => true, 'yukassa_enabled' => false],
  ['name' => 'Фотопечать', 'price' => 'от 30 ₽', 'description' => 'Быстрая фотопечать разных форматов.', 'enabled' => true, 'yukassa_enabled' => true],
];
if (!file_exists($servicesFile)) @file_put_contents($servicesFile, json_encode($defaultServices, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$services = json_decode(@file_get_contents($servicesFile), true) ?? $defaultServices;

$defaultPhotoConfig = [
  'enabled' => true,
  'max_photos' => 100,
  'max_file_size' => 10,
  'supported_formats' => ['jpg', 'jpeg', 'png', 'heic', 'heif'],
  'sizes' => [
    ['name' => '10×15', 'w' => 10, 'h' => 15, 'base' => 30, 'enabled' => true, 'popular' => true],
    ['name' => '15×21', 'w' => 15, 'h' => 21, 'base' => 50, 'enabled' => true, 'popular' => true],
    ['name' => '20×30', 'w' => 20, 'h' => 30, 'base' => 80, 'enabled' => true, 'popular' => false],
    ['name' => 'A4', 'w' => 21, 'h' => 29.7, 'base' => 120, 'enabled' => true, 'popular' => false],
    ['name' => 'A3', 'w' => 29.7, 'h' => 42, 'base' => 200, 'enabled' => true, 'popular' => false],
  ],
  'papers' => [
    ['name' => 'Матовая', 'delta' => 0, 'enabled' => true, 'description' => 'Классическая матовая бумага'],
    ['name' => 'Глянец', 'delta' => 10, 'enabled' => true, 'description' => 'Глянцевая бумага с блеском'],
    ['name' => 'Полуматовая', 'delta' => 5, 'enabled' => true, 'description' => 'Полуматовая премиум бумага'],
  ],
  'corrections' => [
    ['name' => 'Нет', 'delta' => 0, 'enabled' => true, 'description' => 'Без коррекции'],
    ['name' => 'Легкая', 'delta' => 15, 'enabled' => true, 'description' => 'Автокоррекция яркости и контраста'],
    ['name' => 'Профессиональная', 'delta' => 50, 'enabled' => true, 'description' => 'Ручная коррекция дизайнером'],
  ],
  'processing_options' => [
    ['name' => 'Кадрирование', 'price' => 20, 'enabled' => true, 'description' => 'Обрезка по нужному размеру'],
    ['name' => 'Удаление красных глаз', 'price' => 30, 'enabled' => true, 'description' => 'Коррекция эффекта красных глаз'],
    ['name' => 'Черно-белое', 'price' => 10, 'enabled' => true, 'description' => 'Преобразование в ч/б'],
  ],
  'delivery_options' => [
    ['name' => 'Самовывоз', 'price' => 0, 'enabled' => true, 'description' => 'Забрать в офисе'],
    ['name' => 'Доставка по городу', 'price' => 200, 'enabled' => true, 'description' => 'Доставка курьером'],
    ['name' => 'Почта России', 'price' => 300, 'enabled' => true, 'description' => 'Отправка почтой'],
  ],
  'discounts' => [
    ['name' => 'От 50 фото', 'threshold' => 50, 'discount_percent' => 10, 'enabled' => true],
    ['name' => 'От 100 фото', 'threshold' => 100, 'discount_percent' => 15, 'enabled' => true],
  ]
];

if (!file_exists($photoConfigFile)) @file_put_contents($photoConfigFile, json_encode($defaultPhotoConfig, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$photoConfig = json_decode(@file_get_contents($photoConfigFile), true);
if (!is_array($photoConfig)) $photoConfig = $defaultPhotoConfig;
$photoConfig = array_replace_recursive($defaultPhotoConfig, $photoConfig);

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER FUNCTIONS
   ═══════════════════════════════════════════════════════════════════════════ */
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function sanitize_text($s){ return trim(filter_var($s, FILTER_SANITIZE_FULL_SPECIAL_CHARS)); }
function sanitize_hex($s){ $s=trim((string)$s); if($s==='') return ''; if($s[0]!=='#') $s='#'.$s; if(!preg_match('~^#[0-9a-fA-F]{3,8}$~',$s)) return '#000000'; return $s; }
function clamp_float($v,$min,$max,$def){ $v=(float)$v; if(!is_finite($v)) return $def; return max($min, min($max, $v)); }
function clamp_int($v,$min,$max,$def){ $v=(int)$v; if(!is_finite($v)) return $def; return max($min, min($max, $v)); }

function save_upload_general($field, $uploadsDir, $allowExt){
  if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return null;
  $name = preg_replace('~[^a-zA-Z0-9_\.-]+~u','-', $_FILES[$field]['name']);
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowExt)) throw new Exception('Недопустимый формат: '.esc($ext));
  if ($_FILES[$field]['size'] > 100*1024*1024) throw new Exception('Файл слишком большой (макс. 100 МБ).');
  $newName = date('Ymd-His').'-'.bin2hex(random_bytes(3)).'-'.$name;
  $dest = $uploadsDir.'/'.$newName;
  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) throw new Exception('Не удалось сохранить файл.');
  return $newName;
}

function save_upload_image($field, $uploadsDir){
  $allowed = ['jpg','jpeg','png','webp','svg'];
  return save_upload_general($field, $uploadsDir, $allowed);
}

function log_order($path, $data){
  @file_put_contents($path, '['.date('Y-m-d H:i:s').'] '.json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
}

function require_admin($cfg){
  if(empty($_SESSION['is_admin'])){ header('Location: admin.php?login=1'); exit; }
}

/* ═══════════════════════════════════════════════════════════════════════════
   HOT MODULE LOADING SYSTEM
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Сканирует папку модулей и извлекает метаданные
 * 
 * @param string $modulesDir Путь к папке модулей
 * @return array [modules, newModules, errors]
 */
function scanModules($modulesDir) {
    $modules = [];
    $newModules = [];
    $errors = [];

    // Проверяем существование папки
    if (!is_dir($modulesDir)) {
        $errors[] = "Папка модулей не найдена: $modulesDir";
        return [$modules, $newModules, $errors];
    }

    // Сканируем PHP файлы
    $files = glob($modulesDir . '/*.php');

    if (empty($files)) {
        $errors[] = "Модули не найдены в папке: $modulesDir";
    }

    foreach ($files as $file) {
        try {
            // Читаем первые 100 строк для поиска метаданных
            $handle = fopen($file, 'r');
            $content = '';
            $lineCount = 0;
            while (!feof($handle) && $lineCount < 100) {
                $content .= fgets($handle);
                $lineCount++;
            }
            fclose($handle);

            // Парсим метаданные через regex
            preg_match('/@module_id\s+(\S+)/', $content, $id);
            preg_match('/@module_name\s+(.+)/', $content, $name);
            preg_match('/@module_icon\s+(\S+)/', $content, $icon);
            preg_match('/@module_order\s+(\d+)/', $content, $order);
            preg_match('/@module_version\s+(\S+)/', $content, $version);
            preg_match('/@module_enabled\s+(\S+)/', $content, $enabled);
            preg_match('/@module_access\s+(\S+)/', $content, $access);
            preg_match('/@module_description\s+(.+)/', $content, $description);

            // Если минимальные метаданные найдены
            if (!empty($id[1]) && !empty($name[1])) {
                $moduleId = trim($id[1]);
                $moduleName = trim($name[1]);
                $moduleIcon = trim($icon[1] ?? 'fas fa-puzzle-piece');
                $moduleOrder = (int)($order[1] ?? 999);
                $moduleVersion = trim($version[1] ?? '1.0.0');
                $moduleEnabled = trim($enabled[1] ?? 'true') === 'true';
                $moduleAccess = trim($access[1] ?? 'admin');
                $moduleDescription = trim($description[1] ?? '');

                // Проверяем работоспособность модуля (синтаксис PHP)
                $moduleHealthy = true;
                $healthError = '';

                // Базовая проверка: файл существует и читаемый
                if (!is_readable($file)) {
                    $moduleHealthy = false;
                    $healthError = 'Файл не читаем';
                }

                // Проверка на наличие защиты от прямого доступа
                if (!strpos($content, 'ADMIN_CORE_LOADED')) {
                    $moduleHealthy = false;
                    $healthError = 'Отсутствует защита от прямого доступа';
                }

                $modules[$moduleId] = [
                    'id' => $moduleId,
                    'name' => $moduleName,
                    'icon' => $moduleIcon,
                    'order' => $moduleOrder,
                    'version' => $moduleVersion,
                    'enabled' => $moduleEnabled,
                    'access' => $moduleAccess,
                    'description' => $moduleDescription,
                    'file' => basename($file),
                    'path' => $file,
                    'healthy' => $moduleHealthy,
                    'health_error' => $healthError,
                    'loaded_at' => date('Y-m-d H:i:s')
                ];

                // Проверяем, новый ли модуль
                if (!isset($_SESSION['known_modules'][$moduleId])) {
                    $newModules[] = $moduleName;
                }

            } else {
                $errors[] = "Модуль " . basename($file) . " не содержит корректных метаданных (@module_id и @module_name обязательны)";
            }

        } catch (Exception $e) {
            $errors[] = "Ошибка при загрузке модуля " . basename($file) . ": " . $e->getMessage();
        }
    }

    // Сортируем по порядку
    uasort($modules, function($a, $b) {
        return $a['order'] - $b['order'];
    });

    // Сохраняем список известных модулей в сессию
    if (!isset($_SESSION['known_modules'])) {
        $_SESSION['known_modules'] = [];
    }
    foreach ($modules as $mod) {
        $_SESSION['known_modules'][$mod['id']] = true;
    }

    return [$modules, $newModules, $errors];
}

/**
 * Рендерит индикатор здоровья модуля
 */
function renderModuleHealth($module) {
    if ($module['healthy']) {
        return '<span style="color:#10b981;font-size:10px;margin-left:4px" title="Модуль работает корректно">🟢</span>';
    } else {
        return '<span style="color:#ef4444;font-size:10px;margin-left:4px" title="' . esc($module['health_error']) . '">🔴</span>';
    }
}

// Запускаем автозагрузчик модулей
[$availableModules, $newModules, $moduleErrors] = scanModules($MODULES_DIR);

// Подсчёт здоровых/больных модулей
$healthyCount = count(array_filter($availableModules, function($m) { return $m['healthy']; }));
$unhealthyCount = count($availableModules) - $healthyCount;

/* ═══════════════════════════════════════════════════════════════════════════
   EMAIL FUNCTIONS (для краткости - базовая версия)
   ═══════════════════════════════════════════════════════════════════════════ */
function parse_emails($s){
  $s = str_replace([';','\n','\r'],',',$s);
  $parts = array_filter(array_map('trim', explode(',', (string)$s)));
  $out = [];
  foreach($parts as $p){
    if (filter_var($p, FILTER_VALIDATE_EMAIL)) $out[] = $p;
  }
  return array_values(array_unique($out));
}

function send_email_all($cfg,$subject,$body){
  $to = parse_emails($cfg['email_to']);
  if (empty($to)) $to = [$cfg['email_to']];

  $from = $cfg['email_from'];
  $headers = 'From: '.$from.'\r\n';
  $headers .= 'Content-Type: text/plain; charset=UTF-8\r\n';

  $ok = @mail(implode(', ', $to), $subject, $body, $headers);
  return [$ok, $ok ? [] : ['mail() failed']];
}

/* ═══════════════════════════════════════════════════════════════════════════
   TELEGRAM API (базовая версия)
   ═══════════════════════════════════════════════════════════════════════════ */
function tg_api($token,$method,$params=[]){
  $url='https://api.telegram.org/bot'.$token.'/'.$method;
  $ch=curl_init($url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params));
  $resp=curl_exec($ch);
  curl_close($ch);
  $j=json_decode($resp,true);
  if(!$j || empty($j['ok'])) throw new Exception('Telegram API: '.$resp);
  return $j['result'] ?? true;
}

/* ═══════════════════════════════════════════════════════════════════════════
   CRM FUNCTIONS
   ═══════════════════════════════════════════════════════════════════════════ */
function update_customer_info($customersFile, $name, $phone, $email = '', $orderData = null) {
  $customers = json_decode(@file_get_contents($customersFile), true) ?? [];
  $phone_clean = preg_replace('/[^0-9]/', '', $phone);
  $customer_id = $phone_clean;

  $existingCustomer = null;
  foreach ($customers as $key => $customer) {
    if ($customer['id'] === $customer_id) {
      $existingCustomer = $key;
      break;
    }
  }

  if ($existingCustomer !== null) {
    $customers[$existingCustomer]['last_contact'] = date('Y-m-d H:i:s');
    $customers[$existingCustomer]['orders_count']++;
    if ($orderData) {
      $customers[$existingCustomer]['total_spent'] += $orderData['total'] ?? 0;
    }
    if ($email) $customers[$existingCustomer]['email'] = $email;
  } else {
    $customers[] = [
      'id' => $customer_id,
      'name' => $name,
      'phone' => $phone,
      'email' => $email,
      'created_at' => date('Y-m-d H:i:s'),
      'last_contact' => date('Y-m-d H:i:s'),
      'orders_count' => 1,
      'total_spent' => $orderData['total'] ?? 0,
      'notes' => '',
      'tags' => [],
    ];
  }

  @file_put_contents($customersFile, json_encode($customers, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

function get_order_statistics($ordersLog) {
  $lines = @file($ordersLog, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [];
  $stats = [
    'total' => 0,
    'today' => 0,
    'this_week' => 0,
    'this_month' => 0,
    'photo_orders' => 0,
    'cart_orders' => 0,
    'service_orders' => 0,
    'callback_requests' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0
  ];

  $today = date('Y-m-d');
  $week_start = date('Y-m-d', strtotime('monday this week'));
  $month_start = date('Y-m-01');

  foreach($lines as $line) {
    if(!preg_match('~^$$(.+?)$$ (.+)$~u', $line, $m)) continue;
    $ts = $m[1];
    $j = json_decode($m[2], true);
    if(!$j) continue;

    if (isset($j['action'])) continue;

    $stats['total']++;
    $order_date = date('Y-m-d', strtotime($ts));

    if($order_date === $today) $stats['today']++;
    if($order_date >= $week_start) $stats['this_week']++;
    if($order_date >= $month_start) $stats['this_month']++;

    $type = $j['type'] ?? 'service';
    if($type === 'photo_constructor') $stats['photo_orders']++;
    elseif($type === 'cart') $stats['cart_orders']++;
    elseif($type === 'callback') $stats['callback_requests']++;
    else $stats['service_orders']++;

    if(isset($j['total_price'])) $stats['total_revenue'] += $j['total_price'];
  }

  if($stats['total'] > 0) $stats['avg_order_value'] = $stats['total_revenue'] / $stats['total'];

  return $stats;
}

/* ═══════════════════════════════════════════════════════════════════════════
   ПРИНТСС PRO FUNCTIONS
   ═══════════════════════════════════════════════════════════════════════════ */
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

// Загружаем статистику
$stats = get_order_statistics($ordersLog);

// Загружаем заказы печати
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

usort($printOrders, function($a, $b) {
    return strtotime($b['timestamp'] ?? '1970-01-01') - strtotime($a['timestamp'] ?? '1970-01-01');
});

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

$totalOrders = count($printOrders);
$totalAmount = 0;
$totalReceived = 0;
$totalRemaining = 0;

foreach ($printOrders as $order) {
    if (isset($order['pricing']['total'])) {
        $totalAmount += $order['pricing']['total'];
        $totalReceived += $order['pricing']['prepayment'] ?? 0;
        $totalRemaining += ($order['pricing']['total'] - ($order['pricing']['prepayment'] ?? 0));
    }
}

$today = date('Y-m-d');
$todayOrders = array_filter($printOrders, function($o) use ($today) { 
    return strpos($o['timestamp'], $today) === 0; 
});
$todayAmount = 0;
foreach ($todayOrders as $order) {
    if (isset($order['pricing']['total'])) {
        $todayAmount += $order['pricing']['total'];
    }
}

$thisMonth = date('Y-m');
$monthOrders = array_filter($printOrders, function($o) use ($thisMonth) { 
    return strpos($o['timestamp'], $thisMonth) === 0; 
});
$monthAmount = 0;
foreach ($monthOrders as $order) {
    if (isset($order['pricing']['total'])) {
        $monthAmount += $order['pricing']['total'];
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST REQUEST HANDLERS
   ═══════════════════════════════════════════════════════════════════════════ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Авторизация
  if (isset($_POST['admin_login'])){
    $pass=$_POST['password']??'';
    if(hash_equals($cfg['admin_pass'],$pass)){
      $_SESSION['is_admin']=true;
      header('Location: admin.php');
    }
    else {
      header('Location: admin.php?login=1&err=1');
    }
    exit;
  }

  require_admin($cfg);

  // ПРИНТСС PRO: Создание заказа печати
  if (isset($_POST['action']) && $_POST['action'] === 'create_order') {
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

    $logLine = date('Y-m-d H:i:s') . ' | ' . $orderData['id'] . ' | ' . json_encode($orderData, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($printOrdersLog, $logLine, FILE_APPEND | LOCK_EX);

    $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
    $orderDate = date('Y-m-d');
    $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
    file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    update_customer_info($customersFile, $orderData['customer']['name'], $orderData['customer']['phone'], $orderData['customer']['email'], [
      'id' => $orderData['id'],
      'type' => 'print_order',
      'total' => $orderData['pricing']['total'],
      'status' => 'new'
    ]);

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

  // Обновление статуса заказа
  if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json; charset=utf-8');

    $orderId = trim($_POST['order_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    if (empty($orderId) || empty($newStatus)) {
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
            $orderData['status'] = $newStatus;
            $orderData['status_updated_at'] = date('Y-m-d H:i:s');

            if (!isset($orderData['history'])) {
              $orderData['history'] = [];
            }
            $orderData['history'][] = [
              'action' => 'status_change',
              'from' => $orderData['status'] ?? 'new',
              'to' => $newStatus,
              'timestamp' => date('Y-m-d H:i:s')
            ];

            $phone = preg_replace('/[^0-9]/', '', $orderData['customer']['phone']);
            $orderDate = date('Y-m-d', strtotime($orderData['dates']['order_date']));
            $txtFilename = $uploadsDir . '/' . $orderDate . '_' . $phone . '.txt';
            file_put_contents($txtFilename, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

            $line = date('Y-m-d H:i:s') . ' | ' . $orderId . ' | ' . json_encode($orderData, JSON_UNESCAPED_UNICODE);
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

  // Дублирование заказа
  if (isset($_POST['action']) && $_POST['action'] === 'duplicate_order') {
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
            $newOrderData = $orderData;
            $newOrderData['id'] = generateOrderNumber($ordersLog, $printOrdersLog);
            $newOrderData['timestamp'] = date('Y-m-d H:i:s');
            $newOrderData['dates']['order_date'] = date('Y-m-d');
            $newOrderData['status'] = 'new';
            unset($newOrderData['history']);
            unset($newOrderData['comments']);

            $logLine = date('Y-m-d H:i:s') . ' | ' . $newOrderData['id'] . ' | ' . json_encode($newOrderData, JSON_UNESCAPED_UNICODE) . "\n";
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

  // Добавление комментария
  if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
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

            $line = date('Y-m-d H:i:s') . ' | ' . $orderId . ' | ' . json_encode($orderData, JSON_UNESCAPED_UNICODE);
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

  // Сохранение настроек фотоконструктора
  if (isset($_POST['save_photo_config'])){
    $newConfig = $photoConfig;

    $newConfig['enabled'] = !empty($_POST['photo_enabled']);
    $newConfig['max_photos'] = max(1, min(500, (int)($_POST['max_photos'] ?? 100)));
    $newConfig['max_file_size'] = max(1, min(100, (int)($_POST['max_file_size'] ?? 10)));

    if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
      $newConfig['sizes'] = [];
      foreach ($_POST['sizes'] as $i => $size) {
        $name = sanitize_text($size['name'] ?? '');
        $name = str_replace(['&times;', '×times;', '&times', 'times'], '×', $name);
        $newConfig['sizes'][] = [
          'name' => $name,
          'w' => max(1, (float)($size['w'] ?? 10)),
          'h' => max(1, (float)($size['h'] ?? 15)),
          'base' => max(0, (int)($size['base'] ?? 30)),
          'enabled' => !empty($size['enabled']),
          'popular' => !empty($size['popular'])
        ];
      }
    }

    if (isset($_POST['papers']) && is_array($_POST['papers'])) {
      $newConfig['papers'] = [];
      foreach ($_POST['papers'] as $i => $paper) {
        $newConfig['papers'][] = [
          'name' => sanitize_text($paper['name'] ?? ''),
          'delta' => max(0, (int)($paper['delta'] ?? 0)),
          'enabled' => !empty($paper['enabled']),
          'description' => sanitize_text($paper['description'] ?? '')
        ];
      }
    }

    if (isset($_POST['corrections']) && is_array($_POST['corrections'])) {
      $newConfig['corrections'] = [];
      foreach ($_POST['corrections'] as $i => $correction) {
        $newConfig['corrections'][] = [
          'name' => sanitize_text($correction['name'] ?? ''),
          'delta' => max(0, (int)($correction['delta'] ?? 0)),
          'enabled' => !empty($correction['enabled']),
          'description' => sanitize_text($correction['description'] ?? '')
        ];
      }
    }

    if (isset($_POST['processing_options']) && is_array($_POST['processing_options'])) {
      $newConfig['processing_options'] = [];
      foreach ($_POST['processing_options'] as $i => $option) {
        $newConfig['processing_options'][] = [
          'name' => sanitize_text($option['name'] ?? ''),
          'price' => max(0, (int)($option['price'] ?? 0)),
          'enabled' => !empty($option['enabled']),
          'description' => sanitize_text($option['description'] ?? '')
        ];
      }
    }

    if (isset($_POST['delivery_options']) && is_array($_POST['delivery_options'])) {
      $newConfig['delivery_options'] = [];
      foreach ($_POST['delivery_options'] as $i => $option) {
        $newConfig['delivery_options'][] = [
          'name' => sanitize_text($option['name'] ?? ''),
          'price' => max(0, (int)($option['price'] ?? 0)),
          'enabled' => !empty($option['enabled']),
          'description' => sanitize_text($option['description'] ?? '')
        ];
      }
    }

    if (isset($_POST['discounts']) && is_array($_POST['discounts'])) {
      $newConfig['discounts'] = [];
      foreach ($_POST['discounts'] as $i => $discount) {
        $newConfig['discounts'][] = [
          'name' => sanitize_text($discount['name'] ?? ''),
          'threshold' => max(1, (int)($discount['threshold'] ?? 50)),
          'discount_percent' => max(0, min(99, (int)($discount['discount_percent'] ?? 10))),
          'enabled' => !empty($discount['enabled'])
        ];
      }
    }

    @file_put_contents($photoConfigFile, json_encode($newConfig, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Location: admin.php?tab=photoconstructor&photo_config_saved=1'); exit;
  }

  // Сохранение услуг
  if (isset($_POST['save_services'])){
    $newServices = [];
    if(isset($_POST['services']) && is_array($_POST['services'])){
      foreach($_POST['services'] as $i => $service){
        $newServices[] = [
          'name' => sanitize_text($service['name'] ?? ''),
          'price' => sanitize_text($service['price'] ?? ''),
          'description' => sanitize_text($service['description'] ?? ''),
          'enabled' => !empty($service['enabled']),
          'yukassa_enabled' => !empty($service['yukassa_enabled'])
        ];
      }
    }
    @file_put_contents($servicesFile, json_encode($newServices, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Location: admin.php?tab=homepage&services_saved=1'); exit;
  }

  // Сохранение товара
  if (isset($_POST['save_product'])) {
    try {
      $id = (int)($_POST['id'] ?? 0);
      $name = sanitize_text($_POST['name'] ?? '');
      $price = max(0, (int)($_POST['price'] ?? 0));
      $old_price = max(0, (int)($_POST['old_price'] ?? 0));
      $category = sanitize_text($_POST['category'] ?? 'frames');
      $description = sanitize_text($_POST['description'] ?? '');
      $enabled = !empty($_POST['enabled']);
      $featured = !empty($_POST['featured']);
      $stock = max(0, (int)($_POST['stock'] ?? 0));

      if (!$name || !$price) throw new Exception('Укажите название и цену.');

      $image = '';
      if (!empty($_FILES['image']['name'])) {
        $image = save_upload_image('image', $uploadsDir);
      } else {
        foreach ($products as $p) {
          if ($p['id'] == $id) {
            $image = $p['image'] ?? '';
            break;
          }
        }
      }

      $newProduct = [
        'id' => $id ?: (count($products) > 0 ? max(array_column($products, 'id')) + 1 : 1),
        'name' => $name,
        'price' => $price,
        'old_price' => $old_price,
        'category' => $category,
        'image' => $image,
        'description' => $description,
        'enabled' => $enabled,
        'featured' => $featured,
        'stock' => $stock
      ];

      if ($id) {
        foreach ($products as $key => $p) {
          if ($p['id'] == $id) {
            $products[$key] = $newProduct;
            break;
          }
        }
      } else {
        $products[] = $newProduct;
      }

      @file_put_contents($productsFile, json_encode($products, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
      header('Location: admin.php?tab=products&product_saved=1');
      exit;

    } catch (Exception $e) {
      header('Location: admin.php?tab=products&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // Удаление товара
  if (isset($_POST['delete_product'])) {
    $id = (int)($_POST['id'] ?? 0);
    foreach ($products as $key => $p) {
      if ($p['id'] == $id) {
        if ($p['image'] && file_exists($uploadsDir . '/' . $p['image'])) {
          @unlink($uploadsDir . '/' . $p['image']);
        }
        unset($products[$key]);
        break;
      }
    }
    $products = array_values($products);
    @file_put_contents($productsFile, json_encode($products, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Location: admin.php?tab=products&product_deleted=1');
    exit;
  }

  // Управление отзывами
  if (isset($_POST['review_action'])) {
    $action = $_POST['review_action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($action === 'approve' && $review_id) {
      foreach ($reviews as $key => $review) {
        if ($review['id'] == $review_id) {
          $reviews[$key]['status'] = 'approved';
          break;
        }
      }
    } elseif ($action === 'reject' && $review_id) {
      foreach ($reviews as $key => $review) {
        if ($review['id'] == $review_id) {
          $reviews[$key]['status'] = 'rejected';
          break;
        }
      }
    } elseif ($action === 'delete' && $review_id) {
      foreach ($reviews as $key => $review) {
        if ($review['id'] == $review_id) {
          unset($reviews[$key]);
          break;
        }
      }
      $reviews = array_values($reviews);
    }

    @file_put_contents($reviewsFile, json_encode($reviews, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Location: admin.php?tab=reviews&review_updated=1');
    exit;
  }

  // Основные настройки
  if (isset($_POST['admin_save'])){
    $new=$cfg;
    foreach(['brand','slogan','hero','phone_display','phone_raw','email_to','email_from','address','catalog_desc'] as $f){
      if(isset($_POST[$f])) $new[$f]=trim($_POST[$f]);
    }
    if(isset($_POST['workhours'])){
      $wh=array_filter(array_map('trim', explode("\n", str_replace("\r",'',$_POST['workhours']))));
      if($wh) $new['workhours']=array_values($wh);
    }
    if(!empty($_POST['admin_pass'])) $new['admin_pass']=$_POST['admin_pass'];

    if(isset($_POST['seo']) && is_array($_POST['seo'])){
      $new['seo']['title'] = trim($_POST['seo']['title'] ?? $new['seo']['title']);
      $new['seo']['description'] = trim($_POST['seo']['description'] ?? $new['seo']['description']);
      $new['seo']['keywords'] = trim($_POST['seo']['keywords'] ?? $new['seo']['keywords']);
      $new['seo']['og_image'] = trim($_POST['seo']['og_image'] ?? $new['seo']['og_image']);
      $new['seo']['sitemap_enable'] = !empty($_POST['seo']['sitemap_enable']);
    }

    if(isset($_POST['business']) && is_array($_POST['business'])){
      $new['business']['show_requisites'] = !empty($_POST['business']['show_requisites']);
      $new['business']['show_payment_icons'] = !empty($_POST['business']['show_payment_icons']);
      $new['business']['legal_name'] = trim($_POST['business']['legal_name'] ?? $new['business']['legal_name']);
      $new['business']['inn'] = trim($_POST['business']['inn'] ?? $new['business']['inn']);
      $new['business']['ogrn'] = trim($_POST['business']['ogrn'] ?? $new['business']['ogrn']);
      $new['business']['bank'] = trim($_POST['business']['bank'] ?? $new['business']['bank']);
      $new['business']['bik'] = trim($_POST['business']['bik'] ?? $new['business']['bik']);
      $new['business']['account'] = trim($_POST['business']['account'] ?? $new['business']['account']);
    }

    $new['smtp']['enabled']=!empty($_POST['smtp_enabled']);
    $new['smtp']['host']=trim($_POST['smtp_host']??$new['smtp']['host']);
    $new['smtp']['port']=(int)($_POST['smtp_port']??$new['smtp']['port']);
    $new['smtp']['secure']=in_array($_POST['smtp_secure']??'ssl',['ssl','tls','none'])?$_POST['smtp_secure']:'ssl';
    $new['smtp']['user']=trim($_POST['smtp_user']??$new['smtp']['user']);
    if(isset($_POST['smtp_pass']) && $_POST['smtp_pass']!=='__KEEP__') $new['smtp']['pass']=$_POST['smtp_pass'];

    $new['telegram']['enabled']=!empty($_POST['tg_enabled']);
    $new['telegram']['token']=trim($_POST['tg_token']??$new['telegram']['token']);
    $new['telegram']['chat']=trim($_POST['tg_chat']??$new['telegram']['chat']);

    if(isset($_POST['theme']) && is_array($_POST['theme'])){
      $t=$new['theme'];
      $t['mode']  = in_array(($_POST['theme']['mode']??'light'),['light','dark'])?$_POST['theme']['mode']:'light';
      $t['bg']    = sanitize_hex($_POST['theme']['bg']   ?? $t['bg']);
      $t['text']  = sanitize_hex($_POST['theme']['text'] ?? $t['text']);
      $t['muted'] = sanitize_hex($_POST['theme']['muted']?? $t['muted']);
      $t['brand'] = sanitize_hex($_POST['theme']['brand']?? $t['brand']);
      $t['accent']= sanitize_hex($_POST['theme']['accent']??$t['accent']);
      $t['card_opacity']= clamp_float($_POST['theme']['card_opacity']??$t['card_opacity'], 0.3, 1.0, 0.65);
      $t['blur']  = clamp_int($_POST['theme']['blur']??$t['blur'], 0, 30, 12);
      $t['radius']= clamp_int($_POST['theme']['radius']??$t['radius'], 0, 28, 16);
      $t['shadow']= clamp_float($_POST['theme']['shadow']??$t['shadow'], 0, 0.5, 0.12);
      $t['container']= clamp_int($_POST['theme']['container']??$t['container'], 900, 1400, 1200);
      $new['theme']=$t;
    }

    @file_put_contents($configFile, json_encode($new, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header('Location: admin.php?tab=settings&saved=1'); exit;
  }

  // Тестирование Email
  if (isset($_POST['admin_test_email'])){
    [$ok,$errs] = send_email_all($cfg,'Тест системы отправки почты', 'Тест интеграции от '.date('Y-m-d H:i:s'));
    header('Location: admin.php?tab=integrations&test_email='.($ok?'ok':'fail').'&test_errors='.urlencode(implode('; ', $errs)));
    exit;
  }

  // Тестирование Telegram
  if (isset($_POST['admin_test_tg'])){
    try{
      tg_api($cfg['telegram']['token'],'sendMessage',['chat_id'=>$cfg['telegram']['chat'],'text'=>'🧪 Тест Telegram\n\n✅ Всё работает\n📅 '.date('Y-m-d H:i:s')]);
      header('Location: admin.php?tab=integrations&test_tg=ok');
    }
    catch(Throwable $e){
      header('Location: admin.php?tab=integrations&test_tg=fail&tg_error='.urlencode($e->getMessage()));
    }
    exit;
  }
}

/* ═══════════════════════════════════════════════════════════════════════════
   GET REQUEST HANDLERS
   ═══════════════════════════════════════════════════════════════════════════ */

// Экспорт в Excel
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
    fputcsv($output, ['Номер','Дата','Готовность','Клиент','Телефон','Email','Описание','Сумма','Статус'], ';');

    $statusLabels = ['new'=>'Новый','in_progress'=>'В работе','ready'=>'Готов','completed'=>'Выдан','cancelled'=>'Отменён'];
    foreach ($exportOrders as $order) {
        fputcsv($output, [
            str_replace('print_order_', '#', $order['id']),
            date('d.m.Y H:i', strtotime($order['timestamp'])),
            date('d.m.Y', strtotime($order['dates']['ready_date'])),
            $order['customer']['name'],
            $order['customer']['phone'],
            $order['customer']['email'] ?? '',
            $order['details']['description'],
            $order['pricing']['total'],
            $statusLabels[$order['status']] ?? $order['status']
        ], ';');
    }
    fclose($output);
    exit;
}

// API: Получение заказа
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

// API: События календаря
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
                    $statusColors = ['new'=>'#fbbf24','in_progress'=>'#3b82f6','ready'=>'#10b981','completed'=>'#6b7280','cancelled'=>'#ef4444'];
                    $events[] = [
                        'id' => $orderData['id'],
                        'title' => str_replace('print_order_', '#', $orderData['id']) . ' - ' . $orderData['customer']['name'],
                        'start' => $orderData['dates']['ready_date'],
                        'color' => $statusColors[$orderData['status'] ?? 'new'] ?? '#3b82f6',
                    ];
                }
            }
        }
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE);
    exit;
}

// Выход
if (isset($_GET['logout'])){ session_destroy(); header('Location: admin.php'); exit; }

// Скрытие уведомления о новых модулях
if (isset($_GET['dismiss_new_modules'])) {
    $_SESSION['known_modules'] = [];
    foreach ($availableModules as $mod) {
        $_SESSION['known_modules'][$mod['id']] = true;
    }
    header('Location: admin.php');
    exit;
}

$isLogin = !isset($_SESSION['is_admin']);

// Защищаем от прямого доступа к модулям
define('ADMIN_CORE_LOADED', true);

?><!doctype html>
<html lang='ru'>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>🎯 Модульная Админка v2.0 — <?=esc($cfg['brand'])?></title>
  <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet'>
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
  <script src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'></script>
  <meta name='robots' content='noindex,nofollow'>
  <style>
    :root{--bg:#0f1115;--panel:#151a22;--surface:#1a1f2e;--elevated:#232938;--muted:#a7b0c0;--brand:#FFD400;--accent:#2D5BFF;--ok:#66e38a;--bad:#ff7a7a;--text:#e9eef7;--border:rgba(255,255,255,.08)}
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{background:var(--bg);color:var(--text);font-family:system-ui,'Segoe UI',Arial,Roboto;line-height:1.4;font-size:14px}
    .wrap{max-width:1400px;margin:0 auto;padding:16px}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:16px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:980px){.row{grid-template-columns:1fr}}
    input,textarea,select{width:100%;background:#121825;border:1px solid rgba(255,255,255,.15);color:#fff;border-radius:8px;padding:10px;box-sizing:border-box;font-size:14px;font-family:inherit}
    input:focus,textarea:focus,select:focus{border-color:var(--accent);outline:none}
    textarea{resize:vertical;min-height:80px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text)}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
    @media(max-width:768px){.form-row,.form-row-3{grid-template-columns:1fr}}
    .btn{background:linear-gradient(90deg,var(--accent),#00c2ff);border:none;color:#fff;padding:10px 16px;border-radius:8px;cursor:pointer;margin-right:8px;margin-bottom:8px;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:all 0.3s}
    .btn:hover{opacity:0.9;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.2)}
    .btn-primary{background:linear-gradient(90deg,var(--accent),#00c2ff)}
    .btn-danger{background:linear-gradient(90deg,#ff4757,#ff6b7a)}
    .btn-success{background:linear-gradient(90deg,#2ed573,#55eaa3)}
    .btn-warning{background:linear-gradient(90deg,#f59e0b,#d97706)}
    .btn2{background:#1b2232;border:1px solid rgba(255,255,255,.15);color:#fff;padding:10px 16px;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s}
    .btn2:hover{background:#252b3a}
    .btn-sm{padding:8px 16px;font-size:12px}
    .tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;border-bottom:2px solid var(--border);padding-bottom:12px}
    .tab{background:#1b2232;border:1px solid var(--border);color:#fff;padding:12px 18px;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:500;transition:all 0.3s;display:inline-flex;align-items:center;gap:8px}
    .tab:hover{background:#252b3a;transform:translateY(-2px)}
    .tab.active{background:var(--accent);border-color:var(--accent);box-shadow:0 4px 12px rgba(45,91,255,0.3)}
    .tab-content{display:none}
    .tab-content.active{display:block}
    .stats-bar{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px}
    .stat-card{background:var(--surface);padding:20px;border-radius:12px;text-align:center;border:1px solid var(--border);position:relative;overflow:hidden;transition:all 0.3s}
    .stat-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.3)}
    .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--brand)}
    .stat-number{font-size:32px;font-weight:700;color:var(--brand);margin-bottom:8px}
    .stat-label{font-size:12px;color:var(--muted);text-transform:uppercase;margin-top:4px;letter-spacing:0.5px}
    @media(max-width:768px){.stats-bar{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.stats-bar{grid-template-columns:1fr}}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th{background:rgba(255,255,255,.05);padding:15px;text-align:left;font-weight:600;color:var(--text);position:sticky;top:0;z-index:10;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid var(--border)}
    td{padding:15px;border-bottom:1px solid var(--border);vertical-align:top;font-size:14px}
    tr:hover{background:rgba(255,255,255,.02)}
    .badge{display:inline-block;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;margin-right:6px;text-transform:uppercase}
    .badge-success{background:#2ed573;color:#000}
    .badge-danger{background:#ff4757;color:#fff}
    .badge-warning{background:#ffa502;color:#000}
    .badge-info{background:#3742fa;color:#fff}
    .status-badge{padding:6px 12px;border-radius:15px;font-size:11px;font-weight:600;text-transform:uppercase;text-align:center;display:inline-block;cursor:pointer;transition:all 0.3s}
    .status-new{background:#fef3c7;color:#92400e}
    .status-in_progress{background:#dbeafe;color:#1e40af}
    .status-ready{background:#d1fae5;color:#065f46}
    .status-completed{background:#e5e7eb;color:#374151}
    .status-cancelled{background:#fee2e2;color:#991b1b}
    .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;display:flex;align-items:center;gap:10px}
    .alert-success{background:#1a4d3a;border:1px solid #2ed573;color:#66e38a}
    .alert-danger{background:#4d1a1a;border:1px solid #ff4757;color:#ff7a7a}
    .alert-info{background:#1a2d4d;border:1px solid #2d5bff;color:#7aa7ff}
    .alert-warning{background:#4d3a1a;border:1px solid #ffa502;color:#ffd97a}
    .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);backdrop-filter:blur(5px);z-index:2000;align-items:center;justify-content:center}
    .modal.show{display:flex}
    .modal-content{background:var(--panel);border-radius:12px;width:90%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:modalSlideIn 0.3s ease-out}
    @keyframes modalSlideIn{from{opacity:0;transform:translateY(-50px)}to{opacity:1;transform:translateY(0)}}
    .modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
    .modal-body{padding:20px}
    .close-modal{background:none;border:none;color:var(--muted);font-size:24px;cursor:pointer;transition:all 0.3s}
    .close-modal:hover{color:var(--text);transform:rotate(90deg)}
    .muted{color:var(--muted);font-size:13px}
    .mini{font-size:12px}
    .flex{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .toggle{position:relative;display:inline-block;width:50px;height:24px}
    .toggle input{opacity:0;width:0;height:0}
    .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#666;transition:.4s;border-radius:24px}
    .slider:before{position:absolute;content:'';height:18px;width:18px;left:3px;bottom:3px;background:white;transition:.4s;border-radius:50%}
    input:checked+.slider{background:var(--accent)}
    input:checked+.slider:before{transform:translateX(26px)}
    .config-section{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px}
    .config-section h4{margin:0 0 12px;color:var(--brand);font-size:16px}
    .config-item{background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:8px;position:relative}
    .remove-config-item{position:absolute;top:8px;right:8px;background:#ff4757;color:#fff;border:none;border-radius:4px;width:20px;height:20px;cursor:pointer;font-size:12px;line-height:1}
    .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:16px}
    .product-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;position:relative;transition:all 0.3s}
    .product-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.3)}
    .product-image{width:100%;height:160px;background:#0f1624;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:12px}
    .product-image img{width:100%;height:100%;object-fit:cover}
    .login-container{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px}
    .login-card{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:40px;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:400px;width:100%;animation:fadeIn 0.5s ease-out}
    @keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .login-header{text-align:center;margin-bottom:30px}
    .login-header h1{font-size:28px;color:#333;margin-bottom:8px}
    .login-header p{color:#666;font-size:14px}
    .login-form input{background:#fff;border:2px solid #e1e5e9;color:#333;padding:15px;font-size:16px}
    .login-form input:focus{border-color:#667eea}
    .login-form button{width:100%;padding:15px;font-size:16px;margin-top:10px}
    .module-health-indicator{position:absolute;top:8px;right:8px;width:12px;height:12px;border-radius:50%;animation:pulse 2s infinite}
    .module-health-ok{background:#10b981}
    .module-health-error{background:#ef4444}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
  </style>
</head>
<body>

<?php if ($isLogin): ?>
  <!-- LOGIN SCREEN -->
  <div class='login-container'>
    <div class='login-card'>
      <div class='login-header'>
        <h1>🎯 Модульная Админка v2.0</h1>
        <p><?=esc($cfg['brand'])?></p>
      </div>

      <?php if(isset($_GET['err'])): ?>
        <div class='alert alert-danger'>❌ Неверный пароль</div>
      <?php endif; ?>

      <form method='post' class='login-form'>
        <div class='form-group'>
          <label>Пароль администратора</label>
          <input type='password' name='password' placeholder='Введите пароль' required autofocus>
        </div>
        <button class='btn-success' name='admin_login' value='1'>
          🔓 Войти в систему
        </button>
      </form>

      <div class='muted mini' style='margin-top:12px;text-align:center;color:#999'>
        Пароль по умолчанию: <code style='background:#f0f0f0;padding:2px 6px;border-radius:4px;color:#333'><?=esc($cfg['admin_pass'])?></code>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- MAIN ADMIN PANEL -->
  <div class='wrap'>
    <!-- HEADER -->
    <div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:16px'>
      <div>
        <h1 style='font-size:28px;margin-bottom:8px;display:flex;align-items:center;gap:12px'>
          🎯 Модульная Админка v2.0
          <span style='font-size:14px;background:var(--surface);padding:4px 12px;border-radius:20px;color:var(--muted)'>
            <?=count($availableModules)?> модулей
            <?php if($healthyCount > 0): ?>
              <span style='color:#10b981'>🟢 <?=$healthyCount?></span>
            <?php endif; ?>
            <?php if($unhealthyCount > 0): ?>
              <span style='color:#ef4444'>🔴 <?=$unhealthyCount?></span>
            <?php endif; ?>
          </span>
        </h1>
        <p class='muted'>
          <?=esc($cfg['brand'])?> — Hot Module Loading System
        </p>
      </div>
      <div class='flex'>
        <a href='/' class='btn2' target='_blank'>🏠 Сайт</a>
        <a href='?logout=1' class='btn-danger'>🚪 Выход</a>
      </div>
    </div>

    <!-- MODULE ALERTS -->
    <?php if (!empty($newModules) && !isset($_GET['dismiss_new_modules'])): ?>
      <div class='alert alert-success'>
        <i class='fas fa-puzzle-piece'></i>
        <div style='flex:1'>
          <strong>Добавлены новые модули:</strong> <?=implode(', ', $newModules)?>
        </div>
        <a href='?dismiss_new_modules=1' style='color:#fff;text-decoration:underline'>Скрыть</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($moduleErrors)): ?>
      <div class='alert alert-warning'>
        <i class='fas fa-exclamation-triangle'></i>
        <div>
          <strong>Проблемы при загрузке модулей:</strong>
          <ul style='margin:8px 0 0 20px;padding:0'>
            <?php foreach($moduleErrors as $error): ?>
              <li><?=esc($error)?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- SUCCESS ALERTS -->
    <?php if (isset($_GET['saved'])): ?><div class='alert alert-success'>✅ Настройки сохранены!</div><?php endif; ?>
    <?php if (isset($_GET['photo_config_saved'])): ?><div class='alert alert-success'>✅ Настройки фотоконструктора сохранены!</div><?php endif; ?>
    <?php if (isset($_GET['services_saved'])): ?><div class='alert alert-success'>✅ Услуги сохранены!</div><?php endif; ?>
    <?php if (isset($_GET['product_saved'])): ?><div class='alert alert-success'>✅ Товар сохранен!</div><?php endif; ?>
    <?php if (isset($_GET['product_deleted'])): ?><div class='alert alert-success'>🗑️ Товар удален!</div><?php endif; ?>
    <?php if (isset($_GET['review_updated'])): ?><div class='alert alert-success'>⭐ Отзыв обновлен!</div><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><div class='alert alert-danger'>❌ <?=esc($_GET['error'])?></div><?php endif; ?>

    <!-- STATISTICS -->
    <div class='stats-bar'>
      <div class='stat-card'>
        <div class='stat-number'><?=$totalOrders?></div>
        <div class='stat-label'>Заказов печати</div>
      </div>
      <div class='stat-card'>
        <div class='stat-number'><?=number_format($todayAmount, 0, ' ', ' ')?> ₽</div>
        <div class='stat-label'>Сегодня</div>
      </div>
      <div class='stat-card'>
        <div class='stat-number'><?=number_format($monthAmount, 0, ' ', ' ')?> ₽</div>
        <div class='stat-label'>За месяц</div>
      </div>
      <div class='stat-card'>
        <div class='stat-number'><?=count($customers)?></div>
        <div class='stat-label'>Клиентов</div>
      </div>
      <div class='stat-card'>
        <div class='stat-number'><?=count($products)?></div>
        <div class='stat-label'>Товаров</div>
      </div>
    </div>

    <!-- TABS NAVIGATION (AUTO-GENERATED) -->
    <div class='tabs'>
      <?php foreach ($availableModules as $module): ?>
        <?php if ($module['enabled']): ?>
          <a href='#<?=$module['id']?>' 
             class='tab <?=($_GET['tab']??'printss_dashboard')===$module['id']?'active':''?>' 
             onclick='showTab("<?=$module['id']?>", this)'
             title='<?=$module['description']?> v<?=$module['version']?>'
             style='position:relative'>
            <i class='<?=$module['icon']?>'></i> 
            <?=$module['name']?>
            <?=renderModuleHealth($module)?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- TAB CONTENT (AUTO-LOADED) -->
    <?php foreach ($availableModules as $module): ?>
      <?php if ($module['enabled']): ?>
        <div id='tab-<?=$module['id']?>' 
             class='tab-content <?=($_GET['tab']??'printss_dashboard')===$module['id']?'active':''?>'>
          <?php
          // Подключаем модуль
          if (file_exists($module['path'])) {
            try {
              include $module['path'];
            } catch (Exception $e) {
              echo '<div class="alert alert-danger">';
              echo '<i class="fas fa-exclamation-circle"></i> ';
              echo '<strong>Ошибка загрузки модуля:</strong> ' . esc($e->getMessage());
              echo '</div>';
            }
          } else {
            echo '<div class="alert alert-danger">Модуль не найден: ' . esc($module['path']) . '</div>';
          }
          ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>

  </div>
<?php endif; ?>

<script>
/* NAVIGATION */
function showTab(tabName, element) {
  document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));

  const tabContent = document.getElementById('tab-' + tabName);
  if (tabContent) tabContent.classList.add('active');
  if (element) element.classList.add('active');

  history.replaceState(null, null, '?tab=' + tabName);
}

/* INITIALIZATION */
document.addEventListener('DOMContentLoaded', function() {
  console.log('🎯 Модульная админка v2.0 загружена');
  console.log('📦 Модулей загружено:', <?=count($availableModules)?>);
  console.log('🟢 Здоровых:', <?=$healthyCount?>);
  console.log('🔴 С ошибками:', <?=$unhealthyCount?>);

  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab') || 'printss_dashboard';
  const tabElement = document.querySelector(`[onclick*="${tab}"]`);
  if (tabElement) showTab(tab, tabElement);
});
</script>

</body>
</html>
