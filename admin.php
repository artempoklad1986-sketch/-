<?php
$page_title = 'Настройки';
$breadcrumbs = ['Главная', 'Настройки'];

require_once 'header.php';

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        // Собираем настройки синхронизации 1С
        $sync_settings = [
            'products' => isset($_POST['1c_sync_products']),
            'categories' => isset($_POST['1c_sync_categories']),
            'prices' => isset($_POST['1c_sync_prices']),
            'stock' => isset($_POST['1c_sync_stock']),
            'orders' => isset($_POST['1c_sync_orders']),
            'customers' => isset($_POST['1c_sync_customers']),
            'delivery_zones' => isset($_POST['1c_sync_delivery_zones']),
            'delivery_slots' => isset($_POST['1c_sync_delivery_slots']),
            'payment_transactions' => isset($_POST['1c_sync_payment_transactions']),
            'reviews' => isset($_POST['1c_sync_reviews']),
            'promocodes' => isset($_POST['1c_sync_promocodes'])
        ];

        // Получаем текущие настройки для сохранения last_sync
        $current_settings = $db->find('settings', 'main');
        $last_sync = null;
        if ($current_settings && isset($current_settings['1c_integration']['last_sync'])) {
            $last_sync = $current_settings['1c_integration']['last_sync'];
        }

        $settings_to_save = [
            // Основные настройки
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'site_keywords' => trim($_POST['site_keywords'] ?? ''),
            'site_logo' => trim($_POST['site_logo'] ?? ''),
            'site_favicon' => trim($_POST['site_favicon'] ?? ''),

            // Контактная информация
            'phones' => array_filter(array_map('trim', explode("\n", $_POST['phones'] ?? ''))),
            'email' => trim($_POST['email'] ?? ''),
            'support_email' => trim($_POST['support_email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'coordinates' => [
                'lat' => trim($_POST['coord_lat'] ?? ''),
                'lng' => trim($_POST['coord_lng'] ?? '')
            ],

            // График работы
            'work_hours' => [
                'start' => $_POST['work_start'] ?? '10:00',
                'end' => $_POST['work_end'] ?? '23:00'
            ],
            'work_schedule' => $_POST['work_schedule'] ?? [],

            // Социальные сети
            'social_networks' => [
                'vk' => trim($_POST['social_vk'] ?? ''),
                'instagram' => trim($_POST['social_instagram'] ?? ''),
                'telegram' => trim($_POST['social_telegram'] ?? ''),
                'whatsapp' => trim($_POST['social_whatsapp'] ?? ''),
                'youtube' => trim($_POST['social_youtube'] ?? ''),
                'facebook' => trim($_POST['social_facebook'] ?? '')
            ],

            // Баннер "Требуются работники"
            'jobs_banner' => [
                'enabled' => isset($_POST['jobs_banner_enabled']),
                'title' => trim($_POST['jobs_banner_title'] ?? 'Требуются работники'),
                'description' => trim($_POST['jobs_banner_description'] ?? 'Присоединяйтесь к нашей команде'),
                'link' => trim($_POST['jobs_banner_link'] ?? '#'),
                'button_text' => trim($_POST['jobs_banner_button'] ?? 'Подробнее'),
                'background_color' => trim($_POST['jobs_banner_bg_color'] ?? '#10b981'),
                'text_color' => trim($_POST['jobs_banner_text_color'] ?? '#ffffff'),
                'icon' => trim($_POST['jobs_banner_icon'] ?? 'fa-briefcase'),
                'image' => trim($_POST['jobs_banner_image'] ?? ''),
                'use_image' => isset($_POST['jobs_banner_use_image'])
            ],

            // Настройки доставки
            'delivery_cost' => (float)($_POST['delivery_cost'] ?? 0),
            'free_delivery_from' => (float)($_POST['free_delivery_from'] ?? 0),
            'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
            'max_delivery_distance' => (float)($_POST['max_delivery_distance'] ?? 0),
            'delivery_time' => trim($_POST['delivery_time'] ?? '60-90'),
            'delivery_zones' => [],
            'pickup_enabled' => isset($_POST['pickup_enabled']),
            'pickup_discount' => (float)($_POST['pickup_discount'] ?? 0),

            // Способы оплаты
            'payment_methods' => $_POST['payment_methods'] ?? [],

            // ЮKassa
            'yookassa' => [
                'enabled' => isset($_POST['yookassa_enabled']),
                'shop_id' => trim($_POST['yookassa_shop_id'] ?? ''),
                'secret_key' => trim($_POST['yookassa_secret_key'] ?? ''),
                'test_mode' => isset($_POST['yookassa_test_mode']),
                'auto_capture' => isset($_POST['yookassa_auto_capture']),
                'description_template' => trim($_POST['yookassa_description'] ?? 'Оплата заказа #{order_id}'),
                'success_url' => trim($_POST['yookassa_success_url'] ?? ''),
                'fail_url' => trim($_POST['yookassa_fail_url'] ?? '')
            ],

            // Сбербанк
            'sberbank' => [
                'enabled' => isset($_POST['sberbank_enabled']),
                'username' => trim($_POST['sberbank_username'] ?? ''),
                'password' => trim($_POST['sberbank_password'] ?? ''),
                'test_mode' => isset($_POST['sberbank_test_mode']),
                'two_stage' => isset($_POST['sberbank_two_stage']),
                'success_url' => trim($_POST['sberbank_success_url'] ?? ''),
                'fail_url' => trim($_POST['sberbank_fail_url'] ?? '')
            ],

            // Интеграция 1С
            '1c_integration' => [
                'enabled' => isset($_POST['1c_enabled']),
                'server' => trim($_POST['1c_server'] ?? ''),
                'database' => trim($_POST['1c_database'] ?? ''),
                'username' => trim($_POST['1c_username'] ?? ''),
                'password' => trim($_POST['1c_password'] ?? ''),
                'exchange_path' => trim($_POST['1c_exchange_path'] ?? './1c_exchange/'),
                'auto_sync' => isset($_POST['1c_auto_sync']),
                'sync_interval' => (int)($_POST['1c_sync_interval'] ?? 300),
                'sync_settings' => $sync_settings,
                'last_sync' => $last_sync
            ],

            // Уведомления
            'notifications' => [
                'email_enabled' => isset($_POST['notif_email_enabled']),
                'sms_enabled' => isset($_POST['notif_sms_enabled']),
                'telegram_enabled' => isset($_POST['notif_telegram_enabled']),
                'telegram_bot_token' => trim($_POST['notif_telegram_token'] ?? ''),
                'telegram_chat_id' => trim($_POST['notif_telegram_chat'] ?? ''),
                'new_order_notification' => isset($_POST['notif_new_order']),
                'status_change_notification' => isset($_POST['notif_status_change'])
            ],

            // SEO настройки
            'seo' => [
                'meta_title' => trim($_POST['seo_title'] ?? ''),
                'meta_description' => trim($_POST['seo_description'] ?? ''),
                'meta_keywords' => trim($_POST['seo_keywords'] ?? ''),
                'og_image' => trim($_POST['seo_og_image'] ?? ''),
                'robots_txt' => trim($_POST['seo_robots'] ?? ''),
                'sitemap_enabled' => isset($_POST['seo_sitemap']),
                'google_analytics' => trim($_POST['seo_google_analytics'] ?? ''),
                'yandex_metrika' => trim($_POST['seo_yandex_metrika'] ?? '')
            ],

            // Внешний вид
            'appearance' => [
                'theme' => $_POST['appearance_theme'] ?? 'light',
                'primary_color' => $_POST['appearance_primary_color'] ?? '#000000',
                'secondary_color' => $_POST['appearance_secondary_color'] ?? '#10b981',
                'accent_color' => $_POST['appearance_accent_color'] ?? '#ffffff',
                'font_family' => $_POST['appearance_font'] ?? 'Inter',
                'show_prices' => isset($_POST['appearance_show_prices']),
                'show_availability' => isset($_POST['appearance_show_availability']),
                'catalog_view' => $_POST['appearance_catalog_view'] ?? 'grid',
                'products_per_page' => (int)($_POST['appearance_products_per_page'] ?? 12)
            ],

            // Бонусная система
            'bonus_system' => [
                'enabled' => isset($_POST['bonus_enabled']),
                'percent' => (float)($_POST['bonus_percent'] ?? 5),
                'min_order_for_bonus' => (float)($_POST['bonus_min_order'] ?? 1000),
                'max_bonus_payment' => (float)($_POST['bonus_max_payment'] ?? 30),
                'bonus_expiration_days' => (int)($_POST['bonus_expiration'] ?? 365)
            ],

            // Email настройки
            'email_settings' => [
                'smtp_enabled' => isset($_POST['smtp_enabled']),
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                'smtp_password' => trim($_POST['smtp_password'] ?? ''),
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                'from_email' => trim($_POST['smtp_from_email'] ?? ''),
                'from_name' => trim($_POST['smtp_from_name'] ?? '')
            ]
        ];

        // Обработка зон доставки
        $delivery_zones = [];
        foreach ($_POST as $key => $value) {
            if (preg_match('/^delivery_zone_name_(\d+)$/', $key, $matches)) {
                $index = $matches[1];
                if (!empty($value)) {
                    $delivery_zones[] = [
                        'name' => trim($value),
                        'cost' => (float)($_POST['delivery_zone_cost_' . $index] ?? 0),
                        'time' => (int)($_POST['delivery_zone_time_' . $index] ?? 0)
                    ];
                }
            }
        }
        $settings_to_save['delivery_zones'] = $delivery_zones;

        // Сохраняем настройки
        $result = $db->save('settings', $settings_to_save, 'main');

        if ($result) {
            $success_message = 'Настройки успешно сохранены!';
            if (method_exists($db, 'log')) {
                $db->log('Settings updated by admin: ' . ($_SESSION['admin_name'] ?? 'Unknown'));
            }

            // Перезагружаем настройки после сохранения
            $settings = $db->find('settings', 'main');
        } else {
            throw new Exception('Ошибка при сохранении в базу данных');
        }

    } catch (Exception $e) {
        $error_message = 'Ошибка сохранения настроек: ' . $e->getMessage();
        if (method_exists($db, 'log')) {
            $db->log('Settings save error: ' . $e->getMessage(), 'error');
        }
    }
}

// Получаем текущие настройки
$settings = $db->find('settings', 'main');
if (!$settings) {
    $settings = [];
}

// Устанавливаем значения по умолчанию
$defaults = [
    'site_name' => "Sasha's Sushi",
    'site_description' => 'Лучшие суши и роллы в городе',
    'site_keywords' => 'суши, роллы, доставка еды',
    'site_logo' => '/assets/logo.png',
    'site_favicon' => '/assets/favicon.ico',
    'delivery_cost' => 200,
    'free_delivery_from' => 1500,
    'min_order_amount' => 800,
    'max_delivery_distance' => 10,
    'delivery_time' => '60-90',
    'delivery_zones' => [],
    'pickup_enabled' => true,
    'pickup_discount' => 10,
    'work_hours' => ['start' => '10:00', 'end' => '23:00'],
    'work_schedule' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
    'phones' => ['+7 999 123-45-67'],
    'email' => 'info@sashas-sushi.ru',
    'support_email' => 'support@sashas-sushi.ru',
    'address' => 'г. Москва, ул. Примерная, 123',
    'coordinates' => ['lat' => '55.751244', 'lng' => '37.618423'],
    'payment_methods' => ['cash', 'card', 'online'],
    'social_networks' => [
        'vk' => '', 'instagram' => '', 'telegram' => '',
        'whatsapp' => '', 'youtube' => '', 'facebook' => ''
    ],
    'jobs_banner' => [
        'enabled' => true,
        'title' => 'Требуются работники',
        'description' => 'Присоединяйтесь к нашей команде',
        'link' => '#',
        'button_text' => 'Подробнее',
        'background_color' => '#10b981',
        'text_color' => '#ffffff',
        'icon' => 'fa-briefcase',
        'image' => '',
        'use_image' => false
    ],
    'yookassa' => [
        'enabled' => false, 'shop_id' => '', 'secret_key' => '',
        'test_mode' => true, 'auto_capture' => true,
        'description_template' => 'Оплата заказа #{order_id}',
        'success_url' => '', 'fail_url' => ''
    ],
    'sberbank' => [
        'enabled' => false, 'username' => '', 'password' => '',
        'test_mode' => true, 'two_stage' => false,
        'success_url' => '', 'fail_url' => ''
    ],
    '1c_integration' => [
        'enabled' => false, 'server' => '192.168.1.100:1541',
        'database' => 'SushiRestaurant', 'username' => 'WebExchange',
        'password' => '', 'exchange_path' => './1c_exchange/',
        'auto_sync' => true, 'sync_interval' => 300,
        'sync_settings' => [
            'products' => true, 'categories' => true, 'prices' => true,
            'stock' => true, 'orders' => true, 'customers' => true,
            'delivery_zones' => true, 'delivery_slots' => true,
            'payment_transactions' => true, 'reviews' => false, 'promocodes' => false
        ],
        'last_sync' => null
    ],
    'notifications' => [
        'email_enabled' => true, 'sms_enabled' => false,
        'telegram_enabled' => false, 'telegram_bot_token' => '',
        'telegram_chat_id' => '', 'new_order_notification' => true,
        'status_change_notification' => true
    ],
    'seo' => [
        'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
        'og_image' => '', 'robots_txt' => '', 'sitemap_enabled' => true,
        'google_analytics' => '', 'yandex_metrika' => ''
    ],
    'appearance' => [
        'theme' => 'light', 'primary_color' => '#000000',
        'secondary_color' => '#10b981', 'accent_color' => '#ffffff',
        'font_family' => 'Inter', 'show_prices' => true,
        'show_availability' => true, 'catalog_view' => 'grid',
        'products_per_page' => 12
    ],
    'bonus_system' => [
        'enabled' => false, 'percent' => 5, 'min_order_for_bonus' => 1000,
        'max_bonus_payment' => 30, 'bonus_expiration_days' => 365
    ],
    'email_settings' => [
        'smtp_enabled' => false, 'smtp_host' => '', 'smtp_port' => 587,
        'smtp_username' => '', 'smtp_password' => '', 'smtp_encryption' => 'tls',
        'from_email' => '', 'from_name' => ''
    ]
];

// Объединяем с дефолтными значениями
$settings = array_replace_recursive($defaults, $settings);

// Получаем статус синхронизации 1С
$sync_status = null;
if (method_exists($db, 'get1CSyncStatus')) {
    $sync_status = $db->get1CSyncStatus();
}

// Функция для безопасного получения значения
function getSetting($settings, $keys, $default = '') {
    $value = $settings;
    foreach ((array)$keys as $key) {
        if (is_array($value) && isset($value[$key])) {
            $value = $value[$key];
        } else {
            return $default;
        }
    }
    return $value;
}
?>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="settings-form" id="settingsForm">
    <input type="hidden" name="save_settings" value="1">

    <div class="settings-tabs">
        <div class="tab-list">
            <button type="button" class="tab-button active" data-tab="general">
                <i class="fas fa-cog"></i> Общие
            </button>
            <button type="button" class="tab-button" data-tab="appearance">
                <i class="fas fa-palette"></i> Внешний вид
            </button>
            <button type="button" class="tab-button" data-tab="banner">
                <i class="fas fa-bullhorn"></i> Баннер
            </button>
            <button type="button" class="tab-button" data-tab="delivery">
                <i class="fas fa-truck"></i> Доставка
            </button>
            <button type="button" class="tab-button" data-tab="payments">
                <i class="fas fa-credit-card"></i> Платежи
            </button>
            <button type="button" class="tab-button" data-tab="1c">
                <i class="fas fa-sync-alt"></i> 1С
                <?php if ($sync_status && isset($sync_status['status']) && $sync_status['status'] === 'recent'): ?>
                    <span class="badge badge-success">●</span>
                <?php endif; ?>
            </button>
            <button type="button" class="tab-button" data-tab="notifications">
                <i class="fas fa-bell"></i> Уведомления
            </button>
            <button type="button" class="tab-button" data-tab="seo">
                <i class="fas fa-search"></i> SEO
            </button>
            <button type="button" class="tab-button" data-tab="bonus">
                <i class="fas fa-gift"></i> Бонусы
            </button>
            <button type="button" class="tab-button" data-tab="email">
                <i class="fas fa-envelope"></i> Email
            </button>
        </div>

        <!-- Общие настройки -->
        <div id="general" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Основная информация</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Название сайта *</label>
                                <input type="text" class="form-input" name="site_name" 
                                       value="<?= htmlspecialchars(getSetting($settings, 'site_name')) ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" name="email" 
                                       value="<?= htmlspecialchars(getSetting($settings, 'email')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Логотип (URL)</label>
                                <input type="text" class="form-input" name="site_logo" 
                                       value="<?= htmlspecialchars(getSetting($settings, 'site_logo')) ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Favicon (URL)</label>
                                <input type="text" class="form-input" name="site_favicon" 
                                       value="<?= htmlspecialchars(getSetting($settings, 'site_favicon')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Описание</label>
                        <textarea class="form-textarea" name="site_description" rows="3"><?= htmlspecialchars(getSetting($settings, 'site_description')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ключевые слова</label>
                        <input type="text" class="form-input" name="site_keywords" 
                               value="<?= htmlspecialchars(getSetting($settings, 'site_keywords')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Адрес</label>
                        <input type="text" class="form-input" name="address" 
                               value="<?= htmlspecialchars(getSetting($settings, 'address')) ?>">
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Координаты (широта)</label>
                                <input type="text" class="form-input" name="coord_lat" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['coordinates', 'lat'])) ?>"
                                       placeholder="55.751244">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Координаты (долгота)</label>
                                <input type="text" class="form-input" name="coord_lng" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['coordinates', 'lng'])) ?>"
                                       placeholder="37.618423">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Телефоны (каждый с новой строки)</label>
                        <textarea class="form-textarea" name="phones" rows="3"><?= htmlspecialchars(implode("\n", getSetting($settings, 'phones', []))) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Время работы: начало</label>
                                <input type="time" class="form-input" name="work_start" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['work_hours', 'start'], '10:00')) ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Время работы: конец</label>
                                <input type="time" class="form-input" name="work_end" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['work_hours', 'end'], '23:00')) ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Среднее время доставки</label>
                                <input type="text" class="form-input" name="delivery_time" 
                                       value="<?= htmlspecialchars(getSetting($settings, 'delivery_time', '60-90')) ?>"
                                       placeholder="60-90">
                                <small class="form-hint">В минутах (например: 60-90)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Рабочие дни</label>
                        <div class="checkbox-group-inline">
                            <?php
                            $days = [
                                'mon' => 'Пн', 'tue' => 'Вт', 'wed' => 'Ср',
                                'thu' => 'Чт', 'fri' => 'Пт', 'sat' => 'Сб', 'sun' => 'Вс'
                            ];
                            $work_schedule = getSetting($settings, 'work_schedule', []);
                            foreach ($days as $key => $label):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="work_schedule[]" value="<?= $key ?>" 
                                       <?= in_array($key, $work_schedule) ? 'checked' : '' ?>>
                                <?= $label ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-share-alt"></i> Социальные сети</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-vk"></i> ВКонтакте</label>
                                <input type="url" class="form-input" name="social_vk" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'vk'])) ?>"
                                       placeholder="https://vk.com/...">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                                <input type="url" class="form-input" name="social_instagram" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'instagram'])) ?>"
                                       placeholder="https://instagram.com/...">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-telegram"></i> Telegram</label>
                                <input type="url" class="form-input" name="social_telegram" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'telegram'])) ?>"
                                       placeholder="https://t.me/...">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-whatsapp"></i> WhatsApp</label>
                                <input type="tel" class="form-input" name="social_whatsapp" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'whatsapp'])) ?>"
                                       placeholder="+79991234567">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-youtube"></i> YouTube</label>
                                <input type="url" class="form-input" name="social_youtube" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'youtube'])) ?>"
                                       placeholder="https://youtube.com/...">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                                <input type="url" class="form-input" name="social_facebook" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['social_networks', 'facebook'])) ?>"
                                       placeholder="https://facebook.com/...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Внешний вид -->
        <div id="appearance" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paint-brush"></i> Настройки внешнего вида</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Тема</label>
                                <select class="form-input" name="appearance_theme">
                                    <option value="light" <?= getSetting($settings, ['appearance', 'theme'], 'light') == 'light' ? 'selected' : '' ?>>Светлая</option>
                                    <option value="dark" <?= getSetting($settings, ['appearance', 'theme']) == 'dark' ? 'selected' : '' ?>>Темная</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Основной цвет</label>
                                <input type="color" class="form-input color-input" name="appearance_primary_color" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['appearance', 'primary_color'], '#000000')) ?>">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Цвет акцента</label>
                                <input type="color" class="form-input color-input" name="appearance_secondary_color" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['appearance', 'secondary_color'], '#10b981')) ?>">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Цвет фона</label>
                                <input type="color" class="form-input color-input" name="appearance_accent_color" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['appearance', 'accent_color'], '#ffffff')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Шрифт</label>
                                <select class="form-input" name="appearance_font">
                                    <option value="Inter" <?= getSetting($settings, ['appearance', 'font_family'], 'Inter') == 'Inter' ? 'selected' : '' ?>>Inter</option>
                                    <option value="Roboto" <?= getSetting($settings, ['appearance', 'font_family']) == 'Roboto' ? 'selected' : '' ?>>Roboto</option>
                                    <option value="Open Sans" <?= getSetting($settings, ['appearance', 'font_family']) == 'Open Sans' ? 'selected' : '' ?>>Open Sans</option>
                                    <option value="Montserrat" <?= getSetting($settings, ['appearance', 'font_family']) == 'Montserrat' ? 'selected' : '' ?>>Montserrat</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Вид каталога</label>
                                <select class="form-input" name="appearance_catalog_view">
                                    <option value="grid" <?= getSetting($settings, ['appearance', 'catalog_view'], 'grid') == 'grid' ? 'selected' : '' ?>>Сетка</option>
                                    <option value="list" <?= getSetting($settings, ['appearance', 'catalog_view']) == 'list' ? 'selected' : '' ?>>Список</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Товаров на странице</label>
                                <input type="number" class="form-input" name="appearance_products_per_page" 
                                       value="<?= getSetting($settings, ['appearance', 'products_per_page'], 12) ?>" min="6" max="48">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="appearance_show_prices" 
                                       <?= getSetting($settings, ['appearance', 'show_prices'], true) ? 'checked' : '' ?>>
                                Показывать цены на товары
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="appearance_show_availability" 
                                       <?= getSetting($settings, ['appearance', 'show_availability'], true) ? 'checked' : '' ?>>
                                Показывать наличие товаров
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye"></i> Предпросмотр цветовой схемы</h3>
                </div>
                <div class="card-body">
                    <div class="color-preview" id="colorPreview">
                        <div class="preview-header">Заголовок</div>
                        <div class="preview-content">
                            <button class="preview-button" type="button">Кнопка</button>
                            <div class="preview-text">Пример текста</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Баннер "Требуются работники" -->
        <div id="banner" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bullhorn"></i> Баннер на главной странице</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="jobs_banner_enabled" 
                               <?= getSetting($settings, ['jobs_banner', 'enabled'], true) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Заголовок баннера</label>
                        <input type="text" class="form-input" name="jobs_banner_title" 
                               value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'title'], 'Требуются работники')) ?>"
                               placeholder="Требуются работники">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Описание</label>
                        <textarea class="form-textarea" name="jobs_banner_description" rows="2"><?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'description'], 'Присоединяйтесь к нашей команде')) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Ссылка</label>
                                <input type="text" class="form-input" name="jobs_banner_link" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'link'], '#')) ?>"
                                       placeholder="https://example.com/vacancies или #">
                                <small class="form-hint">URL страницы вакансий или # для модального окна</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Текст кнопки</label>
                                <input type="text" class="form-input" name="jobs_banner_button" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'button_text'], 'Подробнее')) ?>"
                                       placeholder="Подробнее">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Цвет фона</label>
                                <input type="color" class="form-input color-input" name="jobs_banner_bg_color" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'background_color'], '#10b981')) ?>"
                                       id="bannerBgColor">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Цвет текста</label>
                                <input type="color" class="form-input color-input" name="jobs_banner_text_color" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'text_color'], '#ffffff')) ?>"
                                       id="bannerTextColor">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="jobs_banner_use_image" id="bannerUseImage"
                                   <?= getSetting($settings, ['jobs_banner', 'use_image'], false) ? 'checked' : '' ?>>
                            Использовать изображение вместо иконки
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group" id="iconGroup">
                                <label class="form-label">Иконка FontAwesome</label>
                                <div class="icon-input-wrapper">
                                    <i class="fas <?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'icon'], 'fa-briefcase')) ?>" id="iconPreview"></i>
                                    <input type="text" class="form-input" name="jobs_banner_icon" 
                                           value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'icon'], 'fa-briefcase')) ?>"
                                           id="bannerIcon"
                                           placeholder="fa-briefcase">
                                </div>
                                <small class="form-hint">
                                    <a href="https://fontawesome.com/icons" target="_blank">Посмотреть все иконки</a>
                                    (например: fa-briefcase, fa-users, fa-handshake)
                                </small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group" id="imageGroup">
                                <label class="form-label">URL изображения</label>
                                <input type="text" class="form-input" name="jobs_banner_image" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'image'], '')) ?>"
                                       placeholder="https://example.com/banner-image.jpg">
                                <small class="form-hint">Рекомендуемый размер: 80x80px, формат PNG с прозрачностью</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye"></i> Предпросмотр баннера</h3>
                </div>
                <div class="card-body">
                    <div class="banner-preview" id="bannerPreview">
                        <div class="banner-preview-icon" id="previewIcon">
                            <i class="fas <?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'icon'], 'fa-briefcase')) ?>"></i>
                        </div>
                        <div class="banner-preview-image" id="previewImage" style="display: none;">
                            <img src="" alt="Banner" id="previewImageSrc">
                        </div>
                        <div class="banner-preview-content">
                            <h4 id="previewTitle"><?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'title'], 'Требуются работники')) ?></h4>
                            <p id="previewDesc"><?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'description'], 'Присоединяйтесь к нашей команде')) ?></p>
                        </div>
                        <div class="banner-preview-button">
                            <button type="button" id="previewButton"><?= htmlspecialchars(getSetting($settings, ['jobs_banner', 'button_text'], 'Подробнее')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Настройки доставки -->
        <div id="delivery" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shipping-fast"></i> Параметры доставки</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Стоимость доставки (₽)</label>
                                <input type="number" class="form-input" name="delivery_cost" 
                                       value="<?= getSetting($settings, 'delivery_cost', 0) ?>" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Бесплатная доставка от (₽)</label>
                                <input type="number" class="form-input" name="free_delivery_from" 
                                       value="<?= getSetting($settings, 'free_delivery_from', 0) ?>" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Минимальная сумма заказа (₽)</label>
                                <input type="number" class="form-input" name="min_order_amount" 
                                       value="<?= getSetting($settings, 'min_order_amount', 0) ?>" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Макс. расстояние доставки (км)</label>
                                <input type="number" class="form-input" name="max_delivery_distance" 
                                       value="<?= getSetting($settings, 'max_delivery_distance', 10) ?>" min="0" step="0.1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Зоны доставки</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addDeliveryZone()">
                        <i class="fas fa-plus"></i> Добавить зону
                    </button>
                </div>
                <div class="card-body">
                    <div id="deliveryZones">
                        <?php 
                        $delivery_zones = getSetting($settings, 'delivery_zones', []);
                        if (!empty($delivery_zones)): ?>
                            <?php foreach ($delivery_zones as $index => $zone): ?>
                            <div class="delivery-zone-item" data-zone="<?= $index ?>">
                                <div class="row">
                                    <div class="col-5">
                                        <input type="text" class="form-input" 
                                               name="delivery_zone_name_<?= $index ?>" 
                                               value="<?= htmlspecialchars($zone['name'] ?? '') ?>"
                                               placeholder="Название зоны">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" class="form-input" 
                                               name="delivery_zone_cost_<?= $index ?>" 
                                               value="<?= $zone['cost'] ?? 0 ?>"
                                               placeholder="Стоимость" step="0.01">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" class="form-input" 
                                               name="delivery_zone_time_<?= $index ?>" 
                                               value="<?= $zone['time'] ?? 0 ?>"
                                               placeholder="Время (мин)">
                                    </div>
                                    <div class="col-1">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDeliveryZone(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small class="form-hint">
                        <i class="fas fa-info-circle"></i> Зоны доставки синхронизируются с 1С или добавляются вручную
                    </small>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-store"></i> Самовывоз</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="pickup_enabled" 
                                   <?= getSetting($settings, 'pickup_enabled', true) ? 'checked' : '' ?>>
                            Разрешить самовывоз
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Скидка при самовывозе (%)</label>
                        <input type="number" class="form-input" name="pickup_discount" 
                               value="<?= getSetting($settings, 'pickup_discount', 0) ?>" min="0" max="100" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        <!-- Способы оплаты -->
        <div id="payments" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Способы оплаты</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Доступные способы оплаты</label>
                        <div class="checkbox-group">
                            <?php $payment_methods = getSetting($settings, 'payment_methods', []); ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="payment_methods[]" value="cash" 
                                       <?= in_array('cash', $payment_methods) ? 'checked' : '' ?>>
                                <i class="fas fa-money-bill-alt"></i> Наличными при получении
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="payment_methods[]" value="card" 
                                       <?= in_array('card', $payment_methods) ? 'checked' : '' ?>>
                                <i class="fas fa-credit-card"></i> Картой при получении
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="payment_methods[]" value="online" 
                                       <?= in_array('online', $payment_methods) ? 'checked' : '' ?>>
                                <i class="fas fa-laptop"></i> Онлайн-оплата
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ЮKassa -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <img src="https://yookassa.ru/favicon.ico" alt="ЮKassa" style="width: 20px; height: 20px; margin-right: 8px;" onerror="this.style.display='none'">
                        ЮKassa (Яндекс.Касса)
                    </h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="yookassa_enabled" 
                               <?= getSetting($settings, ['yookassa', 'enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Shop ID</label>
                                <input type="text" class="form-input" name="yookassa_shop_id" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['yookassa', 'shop_id'])) ?>"
                                       placeholder="123456">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Secret Key</label>
                                <input type="password" class="form-input" name="yookassa_secret_key" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['yookassa', 'secret_key'])) ?>"
                                       placeholder="live_...">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Шаблон описания платежа</label>
                        <input type="text" class="form-input" name="yookassa_description" 
                               value="<?= htmlspecialchars(getSetting($settings, ['yookassa', 'description_template'], 'Оплата заказа #{order_id}')) ?>"
                               placeholder="Оплата заказа #{order_id}">
                        <small class="form-hint">Доступные переменные: {order_id}, {customer_name}</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">URL успешной оплаты</label>
                                <input type="url" class="form-input" name="yookassa_success_url" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['yookassa', 'success_url'])) ?>"
                                       placeholder="https://example.com/payment/success">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">URL неуспешной оплаты</label>
                                <input type="url" class="form-input" name="yookassa_fail_url" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['yookassa', 'fail_url'])) ?>"
                                       placeholder="https://example.com/payment/fail">
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="yookassa_test_mode" 
                                   <?= getSetting($settings, ['yookassa', 'test_mode'], false) ? 'checked' : '' ?>>
                            Тестовый режим
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="yookassa_auto_capture" 
                                   <?= getSetting($settings, ['yookassa', 'auto_capture'], false) ? 'checked' : '' ?>>
                            Автоматическое подтверждение платежа
                        </label>
                    </div>
                </div>
            </div>

            <!-- Сбербанк -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-university"></i> Сбербанк Эквайринг
                    </h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="sberbank_enabled" 
                               <?= getSetting($settings, ['sberbank', 'enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Логин</label>
                                <input type="text" class="form-input" name="sberbank_username" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['sberbank', 'username'])) ?>"
                                       placeholder="merchant_username">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Пароль</label>
                                <input type="password" class="form-input" name="sberbank_password" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['sberbank', 'password'])) ?>"
                                       placeholder="merchant_password">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">URL успешной оплаты</label>
                                <input type="url" class="form-input" name="sberbank_success_url" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['sberbank', 'success_url'])) ?>"
                                       placeholder="https://example.com/payment/success">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">URL неуспешной оплаты</label>
                                <input type="url" class="form-input" name="sberbank_fail_url" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['sberbank', 'fail_url'])) ?>"
                                       placeholder="https://example.com/payment/fail">
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sberbank_test_mode" 
                                   <?= getSetting($settings, ['sberbank', 'test_mode'], false) ? 'checked' : '' ?>>
                            Тестовый режим
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="sberbank_two_stage" 
                                   <?= getSetting($settings, ['sberbank', 'two_stage'], false) ? 'checked' : '' ?>>
                            Двухстадийная оплата (с холдированием)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Интеграция 1С -->
        <div id="1c" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database"></i> Подключение к 1С</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="1c_enabled" 
                               <?= getSetting($settings, ['1c_integration', 'enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Сервер 1С</label>
                                <input type="text" class="form-input" name="1c_server" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['1c_integration', 'server'])) ?>"
                                       placeholder="192.168.1.100:1541">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">База данных</label>
                                <input type="text" class="form-input" name="1c_database" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['1c_integration', 'database'])) ?>"
                                       placeholder="SushiRestaurant">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Пользователь</label>
                                <input type="text" class="form-input" name="1c_username" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['1c_integration', 'username'])) ?>"
                                       placeholder="WebExchange">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Пароль</label>
                                <input type="password" class="form-input" name="1c_password" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['1c_integration', 'password'])) ?>"
                                       placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Путь к файлам обмена</label>
                        <input type="text" class="form-input" name="1c_exchange_path" 
                               value="<?= htmlspecialchars(getSetting($settings, ['1c_integration', 'exchange_path'], './1c_exchange/')) ?>">
                    </div>

                    <?php if ($sync_status): ?>
                    <div class="sync-status-block status-<?= htmlspecialchars($sync_status['status'] ?? 'normal') ?>">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Последняя синхронизация:</strong> 
                            <?php if (isset($sync_status['last_sync'])): ?>
                                <?= date('d.m.Y H:i:s', strtotime($sync_status['last_sync'])) ?>
                                <br>
                                <small>(<?= $sync_status['minutes_ago'] ?? 0 ?> минут назад)</small>
                            <?php else: ?>
                                Никогда
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-check"></i> Что синхронизировать с 1С</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="1c_auto_sync" 
                                   <?= getSetting($settings, ['1c_integration', 'auto_sync'], false) ? 'checked' : '' ?>>
                            <strong>Автоматическая синхронизация</strong>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Интервал синхронизации (секунд)</label>
                        <input type="number" class="form-input" name="1c_sync_interval" 
                               value="<?= getSetting($settings, ['1c_integration', 'sync_interval'], 300) ?>" min="60">
                        <small class="form-hint">Рекомендуется не менее 300 секунд (5 минут)</small>
                    </div>

                    <div class="sync-options-grid">
                        <?php 
                        $sync_options = [
                            'products' => ['icon' => 'shopping-bag', 'title' => 'Товары', 'desc' => 'Номенклатура и характеристики'],
                            'categories' => ['icon' => 'folder-open', 'title' => 'Категории', 'desc' => 'Группы товаров'],
                            'prices' => ['icon' => 'ruble-sign', 'title' => 'Цены', 'desc' => 'Актуальные цены'],
                            'stock' => ['icon' => 'boxes', 'title' => 'Остатки', 'desc' => 'Количество на складе'],
                            'orders' => ['icon' => 'receipt', 'title' => 'Заказы', 'desc' => 'Передача заказов в 1С'],
                            'customers' => ['icon' => 'users', 'title' => 'Клиенты', 'desc' => 'Контрагенты'],
                            'delivery_zones' => ['icon' => 'map-marked-alt', 'title' => 'Зоны доставки', 'desc' => 'Тарифы и районы'],
                            'delivery_slots' => ['icon' => 'clock', 'title' => 'Слоты доставки', 'desc' => 'Временные интервалы'],
                            'payment_transactions' => ['icon' => 'money-bill-transfer', 'title' => 'Платежи', 'desc' => 'Транзакции оплаты'],
                            'reviews' => ['icon' => 'star', 'title' => 'Отзывы', 'desc' => 'Отзывы клиентов'],
                            'promocodes' => ['icon' => 'ticket-alt', 'title' => 'Промокоды', 'desc' => 'Акции и скидки']
                        ];

                        foreach ($sync_options as $key => $option):
                            $checked = getSetting($settings, ['1c_integration', 'sync_settings', $key], false);
                        ?>
                        <div class="sync-option-card">
                            <label class="checkbox-label">
                                <input type="checkbox" name="1c_sync_<?= $key ?>" <?= $checked ? 'checked' : '' ?>>
                                <div>
                                    <i class="fas fa-<?= $option['icon'] ?>"></i>
                                    <strong><?= $option['title'] ?></strong>
                                    <small><?= $option['desc'] ?></small>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" onclick="manualSync()">
                            <i class="fas fa-sync-alt"></i> Запустить синхронизацию вручную
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="viewSyncLogs()">
                            <i class="fas fa-file-alt"></i> Просмотреть логи
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Уведомления -->
        <div id="notifications" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-envelope"></i> Email уведомления</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_email_enabled" 
                               <?= getSetting($settings, ['notifications', 'email_enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notif_new_order" 
                                   <?= getSetting($settings, ['notifications', 'new_order_notification'], false) ? 'checked' : '' ?>>
                            Уведомлять о новых заказах
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="notif_status_change" 
                                   <?= getSetting($settings, ['notifications', 'status_change_notification'], false) ? 'checked' : '' ?>>
                            Уведомлять об изменении статуса заказа
                        </label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sms"></i> SMS уведомления</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_sms_enabled" 
                               <?= getSetting($settings, ['notifications', 'sms_enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <p class="text-muted">SMS уведомления требуют подключения SMS-шлюза</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-telegram"></i> Telegram уведомления</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_telegram_enabled" 
                               <?= getSetting($settings, ['notifications', 'telegram_enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Bot Token</label>
                                <input type="text" class="form-input" name="notif_telegram_token" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['notifications', 'telegram_bot_token'])) ?>"
                                       placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Chat ID</label>
                                <input type="text" class="form-input" name="notif_telegram_chat" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['notifications', 'telegram_chat_id'])) ?>"
                                       placeholder="-123456789">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div id="seo" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Основные SEO настройки</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" class="form-input" name="seo_title" 
                               value="<?= htmlspecialchars(getSetting($settings, ['seo', 'meta_title'])) ?>"
                               placeholder="<?= htmlspecialchars(getSetting($settings, 'site_name')) ?>">
                        <small class="form-hint">Рекомендуемая длина: 50-60 символов</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea class="form-textarea" name="seo_description" rows="3" 
                                  placeholder="Описание сайта для поисковых систем"><?= htmlspecialchars(getSetting($settings, ['seo', 'meta_description'])) ?></textarea>
                        <small class="form-hint">Рекомендуемая длина: 150-160 символов</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" class="form-input" name="seo_keywords" 
                               value="<?= htmlspecialchars(getSetting($settings, ['seo', 'meta_keywords'])) ?>"
                               placeholder="ключевое слово 1, ключевое слово 2">
                    </div>

                    <div class="form-group">
                        <label class="form-label">OG Image (URL)</label>
                        <input type="url" class="form-input" name="seo_og_image" 
                               value="<?= htmlspecialchars(getSetting($settings, ['seo', 'og_image'])) ?>"
                               placeholder="https://example.com/og-image.jpg">
                        <small class="form-hint">Изображение для социальных сетей (рекомендуемый размер: 1200x630)</small>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="seo_sitemap" 
                                   <?= getSetting($settings, ['seo', 'sitemap_enabled'], true) ? 'checked' : '' ?>>
                            Автоматически генерировать sitemap.xml
                        </label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-robot"></i> Robots.txt</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Содержимое robots.txt</label>
                        <textarea class="form-textarea" name="seo_robots" rows="10" 
                                  placeholder="User-agent: *&#10;Disallow: /admin/&#10;Sitemap: https://example.com/sitemap.xml"><?= htmlspecialchars(getSetting($settings, ['seo', 'robots_txt'])) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Аналитика</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Google Analytics ID</label>
                        <input type="text" class="form-input" name="seo_google_analytics" 
                               value="<?= htmlspecialchars(getSetting($settings, ['seo', 'google_analytics'])) ?>"
                               placeholder="G-XXXXXXXXXX или UA-XXXXXXXXX-X">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Яндекс.Метрика ID</label>
                        <input type="text" class="form-input" name="seo_yandex_metrika" 
                               value="<?= htmlspecialchars(getSetting($settings, ['seo', 'yandex_metrika'])) ?>"
                               placeholder="12345678">
                    </div>
                </div>
            </div>
        </div>

        <!-- Бонусная система -->
        <div id="bonus" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-gift"></i> Бонусная программа</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="bonus_enabled" 
                               <?= getSetting($settings, ['bonus_system', 'enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Процент начисления бонусов</label>
                                <input type="number" class="form-input" name="bonus_percent" 
                                       value="<?= getSetting($settings, ['bonus_system', 'percent'], 5) ?>" 
                                       min="0" max="100" step="0.1">
                                <small class="form-hint">От суммы заказа</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Минимальная сумма для начисления</label>
                                <input type="number" class="form-input" name="bonus_min_order" 
                                       value="<?= getSetting($settings, ['bonus_system', 'min_order_for_bonus'], 1000) ?>" 
                                       min="0" step="0.01">
                                <small class="form-hint">Рублей</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Максимальная оплата бонусами</label>
                                <input type="number" class="form-input" name="bonus_max_payment" 
                                       value="<?= getSetting($settings, ['bonus_system', 'max_bonus_payment'], 30) ?>" 
                                       min="0" max="100" step="0.1">
                                <small class="form-hint">Процент от суммы заказа</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Срок действия бонусов</label>
                                <input type="number" class="form-input" name="bonus_expiration" 
                                       value="<?= getSetting($settings, ['bonus_system', 'bonus_expiration_days'], 365) ?>" 
                                       min="30">
                                <small class="form-hint">Дней</small>
                            </div>
                        </div>
                    </div>

                    <div class="info-block">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Как работает:</strong>
                            <ul style="margin: 0.5rem 0 0 1.5rem;">
                                <li>Клиент получает <?= getSetting($settings, ['bonus_system', 'percent'], 5) ?>% бонусов от суммы заказа</li>
                                <li>Минимальная сумма заказа для начисления: <?= getSetting($settings, ['bonus_system', 'min_order_for_bonus'], 1000) ?> ₽</li>
                                <li>Можно оплатить бонусами до <?= getSetting($settings, ['bonus_system', 'max_bonus_payment'], 30) ?>% от суммы заказа</li>
                                <li>Бонусы сгорают через <?= getSetting($settings, ['bonus_system', 'bonus_expiration_days'], 365) ?> дней</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email настройки -->
        <div id="email" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server"></i> SMTP настройки</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" name="smtp_enabled" 
                               <?= getSetting($settings, ['email_settings', 'smtp_enabled'], false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label class="form-label">SMTP сервер</label>
                                <input type="text" class="form-input" name="smtp_host" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['email_settings', 'smtp_host'])) ?>"
                                       placeholder="smtp.gmail.com">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Порт</label>
                                <input type="number" class="form-input" name="smtp_port" 
                                       value="<?= getSetting($settings, ['email_settings', 'smtp_port'], 587) ?>"
                                       placeholder="587">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Имя пользователя</label>
                                <input type="text" class="form-input" name="smtp_username" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['email_settings', 'smtp_username'])) ?>"
                                       placeholder="user@example.com">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Пароль</label>
                                <input type="password" class="form-input" name="smtp_password" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['email_settings', 'smtp_password'])) ?>"
                                       placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Шифрование</label>
                                <select class="form-input" name="smtp_encryption">
                                    <option value="tls" <?= getSetting($settings, ['email_settings', 'smtp_encryption'], 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= getSetting($settings, ['email_settings', 'smtp_encryption']) == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="" <?= getSetting($settings, ['email_settings', 'smtp_encryption']) == '' ? 'selected' : '' ?>>Нет</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Email отправителя</label>
                                <input type="email" class="form-input" name="smtp_from_email" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['email_settings', 'from_email'])) ?>"
                                       placeholder="noreply@example.com">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Имя отправителя</label>
                                <input type="text" class="form-input" name="smtp_from_name" 
                                       value="<?= htmlspecialchars(getSetting($settings, ['email_settings', 'from_name'])) ?>"
                                       placeholder="<?= htmlspecialchars(getSetting($settings, 'site_name')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" onclick="testEmail()">
                            <i class="fas fa-paper-plane"></i> Отправить тестовое письмо
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
:root {
    --bg-color: #f8f9fa;
    --border-color: #e5e7eb;
    --text-color: #111827;
    --text-light: #6b7280;
    --primary-color: #000000;
    --secondary-color: #10b981;
}

.settings-form {
    max-width: 1400px;
    margin: 0 auto;
}

.settings-tabs {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.tab-list {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid var(--border-color);
    background: var(--bg-color);
    padding: 0.5rem;
    gap: 0.25rem;
}

.tab-button {
    background: none;
    border: none;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-light);
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.tab-button i {
    font-size: 1rem;
}

.tab-button:hover {
    color: var(--text-color);
    background: rgba(0, 0, 0, 0.05);
}

.tab-button.active {
    color: white;
    background: var(--primary-color);
}

.badge {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-left: 4px;
}

.badge-success {
    background: var(--secondary-color);
    box-shadow: 0 0 8px var(--secondary-color);
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.card-header {
    padding: 1.25rem 1.5rem;
    background: var(--bg-color);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    transition: all 0.2s;
    font-family: inherit;
    box-sizing: border-box;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
}

.color-input {
    height: 42px;
    padding: 0.25rem;
    cursor: pointer;
}

.form-hint {
    display: block;
    font-size: 0.75rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.checkbox-group-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
    color: var(--text-color);
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary-color);
    flex-shrink: 0;
}

.checkbox-label i {
    color: var(--text-light);
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--secondary-color);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Icon Input */
.icon-input-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icon-input-wrapper i {
    font-size: 2rem;
    color: var(--primary-color);
    width: 40px;
    text-align: center;
}

/* Banner Preview */
.banner-preview {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 2rem;
    border-radius: 12px;
    background: <?= getSetting($settings, ['jobs_banner', 'background_color'], '#10b981') ?>;
    color: <?= getSetting($settings, ['jobs_banner', 'text_color'], '#ffffff') ?>;
}

.banner-preview-icon {
    font-size: 3rem;
    flex-shrink: 0;
}

.banner-preview-image {
    flex-shrink: 0;
}

.banner-preview-image img {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.banner-preview-content {
    flex: 1;
}

.banner-preview-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.banner-preview-content p {
    margin: 0;
    opacity: 0.9;
}

.banner-preview-button button {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    color: inherit;
    border: 2px solid currentColor;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.banner-preview-button button:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Sync Options Grid */
.sync-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.sync-option-card {
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    transition: all 0.2s;
}

.sync-option-card:hover {
    border-color: var(--primary-color);
    background: var(--bg-color);
}

.sync-option-card .checkbox-label {
    display: block;
}

.sync-option-card i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
    display: block;
}

.sync-option-card strong {
    display: block;
    margin-bottom: 0.25rem;
}

.sync-option-card small {
    display: block;
    color: var(--text-light);
    font-size: 0.75rem;
}

/* Sync Status Block */
.sync-status-block {
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.sync-status-block.status-recent {
    background: #d1fae5;
    border: 1px solid var(--secondary-color);
    color: #065f46;
}

.sync-status-block.status-normal {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
}

.sync-status-block.status-outdated {
    background: #fee2e2;
    border: 1px solid #dc2626;
    color: #991b1b;
}

.sync-status-block i {
    font-size: 1.5rem;
}

/* Delivery Zones */
.delivery-zone-item {
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 8px;
    margin-bottom: 0.75rem;
}

/* Color Preview */
.color-preview {
    padding: 2rem;
    border-radius: 8px;
    border: 2px dashed var(--border-color);
}

.preview-header {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid;
}

.preview-button {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-right: 1rem;
}

.preview-text {
    margin-top: 1rem;
    font-size: 0.875rem;
}

/* Info Block */
.info-block {
    padding: 1rem;
    background: #f0f9ff;
    border-left: 4px solid #0284c7;
    border-radius: 8px;
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.info-block i {
    color: #0284c7;
    font-size: 1.25rem;
}

/* Form Actions */
.form-actions {
    margin-top: 2rem;
    padding: 1.5rem 2rem;
    background: white;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}

/* Buttons */
.btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #1f2937;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: white;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-color);
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-lg {
    padding: 0.875rem 1.75rem;
    font-size: 1rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

/* Alert */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-success i {
    color: #10b981;
    font-size: 1.25rem;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #dc2626;
}

.alert-error i {
    color: #dc2626;
    font-size: 1.25rem;
}

/* Grid System */
.row {
    display: flex;
    margin: 0 -0.5rem;
    flex-wrap: wrap;
}

.col-3,
.col-4,
.col-5,
.col-6,
.col-8,
.col-1 {
    padding: 0 0.5rem;
}

.col-1 { flex: 0 0 8.333%; max-width: 8.333%; }
.col-3 { flex: 0 0 25%; max-width: 25%; }
.col-4 { flex: 0 0 33.333%; max-width: 33.333%; }
.col-5 { flex: 0 0 41.666%; max-width: 41.666%; }
.col-6 { flex: 0 0 50%; max-width: 50%; }
.col-8 { flex: 0 0 66.666%; max-width: 66.666%; }

@media (max-width: 768px) {
    .col-3,
    .col-4,
    .col-5,
    .col-6,
    .col-8,
    .col-1 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .tab-list {
        overflow-x: auto;
    }

    .tab-button {
        white-space: nowrap;
    }

    .form-actions {
        flex-direction: column;
    }

    .sync-options-grid {
        grid-template-columns: 1fr;
    }

    .banner-preview {
        flex-direction: column;
        text-align: center;
    }
}

.text-muted {
    color: var(--text-light);
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            this.classList.add('active');
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // Color preview
    const primaryColor = document.querySelector('[name="appearance_primary_color"]');
    const secondaryColor = document.querySelector('[name="appearance_secondary_color"]');
    const accentColor = document.querySelector('[name="appearance_accent_color"]');
    const preview = document.getElementById('colorPreview');

    function updatePreview() {
        if (preview && primaryColor && secondaryColor && accentColor) {
            preview.style.background = accentColor.value;
            const header = preview.querySelector('.preview-header');
            const button = preview.querySelector('.preview-button');
            const text = preview.querySelector('.preview-text');

            if (header) {
                header.style.color = primaryColor.value;
                header.style.borderColor = primaryColor.value;
            }
            if (button) {
                button.style.background = primaryColor.value;
                button.style.color = accentColor.value;
            }
            if (text) {
                text.style.color = primaryColor.value;
            }
        }
    }

    if (primaryColor) primaryColor.addEventListener('input', updatePreview);
    if (secondaryColor) secondaryColor.addEventListener('input', updatePreview);
    if (accentColor) accentColor.addEventListener('input', updatePreview);

    updatePreview();

    // Banner Preview
    const bannerBgColor = document.getElementById('bannerBgColor');
    const bannerTextColor = document.getElementById('bannerTextColor');
    const bannerTitle = document.querySelector('[name="jobs_banner_title"]');
    const bannerDesc = document.querySelector('[name="jobs_banner_description"]');
    const bannerButton = document.querySelector('[name="jobs_banner_button"]');
    const bannerIcon = document.getElementById('bannerIcon');
    const bannerImage = document.querySelector('[name="jobs_banner_image"]');
    const bannerUseImage = document.getElementById('bannerUseImage');
    const bannerPreview = document.getElementById('bannerPreview');
    const previewIcon = document.getElementById('previewIcon');
    const previewImage = document.getElementById('previewImage');
    const previewImageSrc = document.getElementById('previewImageSrc');
    const iconGroup = document.getElementById('iconGroup');
    const imageGroup = document.getElementById('imageGroup');

    function updateBannerPreview() {
        if (!bannerPreview) return;

        // Цвета
        if (bannerBgColor) {
            bannerPreview.style.background = bannerBgColor.value;
        }
        if (bannerTextColor) {
            bannerPreview.style.color = bannerTextColor.value;
        }

        // Текст
        const previewTitle = document.getElementById('previewTitle');
        const previewDesc = document.getElementById('previewDesc');
        const previewButton = document.getElementById('previewButton');

        if (previewTitle && bannerTitle) {
            previewTitle.textContent = bannerTitle.value || 'Требуются работники';
        }
        if (previewDesc && bannerDesc) {
            previewDesc.textContent = bannerDesc.value || 'Присоединяйтесь к нашей команде';
        }
        if (previewButton && bannerButton) {
            previewButton.textContent = bannerButton.value || 'Подробнее';
        }

        // Иконка или изображение
        if (bannerUseImage && bannerUseImage.checked) {
            if (previewIcon) previewIcon.style.display = 'none';
            if (previewImage) {
                previewImage.style.display = 'block';
                if (bannerImage && previewImageSrc) {
                    previewImageSrc.src = bannerImage.value || '';
                }
            }
            if (iconGroup) iconGroup.style.opacity = '0.5';
            if (imageGroup) imageGroup.style.opacity = '1';
        } else {
            if (previewIcon) {
                previewIcon.style.display = 'block';
                const iconElement = previewIcon.querySelector('i');
                if (iconElement && bannerIcon) {
                    iconElement.className = 'fas ' + (bannerIcon.value || 'fa-briefcase');
                }
            }
            if (previewImage) previewImage.style.display = 'none';
            if (iconGroup) iconGroup.style.opacity = '1';
            if (imageGroup) imageGroup.style.opacity = '0.5';
        }

        // Обновление preview иконки в input
        const iconPreview = document.getElementById('iconPreview');
        if (iconPreview && bannerIcon) {
            iconPreview.className = 'fas ' + (bannerIcon.value || 'fa-briefcase');
        }
    }

    // Слушатели для баннера
    if (bannerBgColor) bannerBgColor.addEventListener('input', updateBannerPreview);
    if (bannerTextColor) bannerTextColor.addEventListener('input', updateBannerPreview);
    if (bannerTitle) bannerTitle.addEventListener('input', updateBannerPreview);
    if (bannerDesc) bannerDesc.addEventListener('input', updateBannerPreview);
    if (bannerButton) bannerButton.addEventListener('input', updateBannerPreview);
    if (bannerIcon) bannerIcon.addEventListener('input', updateBannerPreview);
    if (bannerImage) bannerImage.addEventListener('input', updateBannerPreview);
    if (bannerUseImage) bannerUseImage.addEventListener('change', updateBannerPreview);

    updateBannerPreview();

    // Form submit handler
    const form = document.getElementById('settingsForm');
    const saveButton = document.getElementById('saveButton');

    if (form && saveButton) {
        form.addEventListener('submit', function(e) {
            // Предотвращаем двойную отправку
            if (saveButton.disabled) {
                e.preventDefault();
                return false;
            }

            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';

            // Если форма не отправится, через 10 секунд разблокируем кнопку
            setTimeout(function() {
                if (saveButton.disabled) {
                    saveButton.disabled = false;
                    saveButton.innerHTML = '<i class="fas fa-save"></i> Сохранить все настройки';
                }
            }, 10000);
        });
    }
});

let deliveryZoneIndex = <?= count(getSetting($settings, 'delivery_zones', [])) ?>;

function addDeliveryZone() {
    const container = document.getElementById('deliveryZones');
    if (!container) return;

    const zoneHtml = `
        <div class="delivery-zone-item" data-zone="${deliveryZoneIndex}">
            <div class="row">
                <div class="col-5">
                    <input type="text" class="form-input" 
                           name="delivery_zone_name_${deliveryZoneIndex}" 
                           placeholder="Название зоны">
                </div>
                <div class="col-3">
                    <input type="number" class="form-input" 
                           name="delivery_zone_cost_${deliveryZoneIndex}" 
                           placeholder="Стоимость" step="0.01">
                </div>
                <div class="col-3">
                    <input type="number" class="form-input" 
                           name="delivery_zone_time_${deliveryZoneIndex}" 
                           placeholder="Время (мин)">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeDeliveryZone(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', zoneHtml);
    deliveryZoneIndex++;
}

function removeDeliveryZone(button) {
    if (confirm('Удалить эту зону доставки?')) {
        button.closest('.delivery-zone-item').remove();
    }
}

function manualSync() {
    if (confirm('Запустить синхронизацию с 1С?')) {
        alert('Функция синхронизации будет реализована в sync_1c.php');
        // TODO: Ajax запрос на синхронизацию
    }
}

function viewSyncLogs() {
    alert('Просмотр логов будет реализован в logs_1c.php');
    // TODO: Открыть страницу логов
}

function testEmail() {
    const email = prompt('Введите email для отправки тестового письма:');
    if (email) {
        alert('Отправка тестового письма будет реализована в test_email.php');
        // TODO: Ajax запрос на отправку тестового письма
    }
}
</script>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg" id="saveButton">
            <i class="fas fa-save"></i>
            Сохранить все настройки
        </button>
        <a href="index.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i>
            Отмена
        </a>
    </div>
</form>

<?php require_once 'footer.php'; ?>
