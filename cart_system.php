<?php
require_login();
check_csrf();

$show_png = false;

/* ====== ДОБАВЛЕНО: каталог категорий (домашние + бизнес) с цветами и пиктограммами ====== */
$CATEGORY_CATALOG = [
  'Домашние' => [
    ['label'=>'Зарплата','type'=>'income','color'=>'#22c55e','icon'=>'💼'],
    ['label'=>'Подработка','type'=>'income','color'=>'#16a34a','icon'=>'🧩'],
    ['label'=>'Подарки','type'=>'income','color'=>'#10b981','icon'=>'🎁'],
    ['label'=>'Проценты','type'=>'income','color'=>'#0ea5e9','icon'=>'💸'],
    ['label'=>'Возвраты','type'=>'income','color'=>'#14b8a6','icon'=>'↩️'],
    ['label'=>'Продукты','type'=>'expense','color'=>'#f59e0b','icon'=>'🛒'],
    ['label'=>'Кафе и рестораны','type'=>'expense','color'=>'#f97316','icon'=>'🍽️'],
    ['label'=>'Транспорт','type'=>'expense','color'=>'#3b82f6','icon'=>'🚌'],
    ['label'=>'Авто','type'=>'expense','color'=>'#0284c7','icon'=>'🚗'],
    ['label'=>'Коммунальные платежи','type'=>'expense','color'=>'#6366f1','icon'=>'💡'],
    ['label'=>'Аренда жилья','type'=>'expense','color'=>'#8b5cf6','icon'=>'🏠'],
    ['label'=>'Интернет и связь','type'=>'expense','color'=>'#06b6d4','icon'=>'📶'],
    ['label'=>'Здоровье','type'=>'expense','color'=>'#ef4444','icon'=>'🩺'],
    ['label'=>'Образование','type'=>'expense','color'=>'#84cc16','icon'=>'🎓'],
    ['label'=>'Одежда','type'=>'expense','color'=>'#a855f7','icon'=>'👕'],
    ['label'=>'Дом и ремонт','type'=>'expense','color'=>'#d946ef','icon'=>'🛠️'],
    ['label'=>'Дети','type'=>'expense','color'=>'#f43f5e','icon'=>'🧸'],
    ['label'=>'Дочь','type'=>'expense','color'=>'#ec4899','icon'=>'👧'],
    ['label'=>'Жена','type'=>'expense','color'=>'#db2777','icon'=>'👩'],
    ['label'=>'Путешествия','type'=>'expense','color'=>'#0ea5e9','icon'=>'✈️'],
    ['label'=>'Подписки','type'=>'expense','color'=>'#94a3b8','icon'=>'📺'],
    ['label'=>'Налоги (личные)','type'=>'expense','color'=>'#ef4444','icon'=>'🧾'],
    ['label'=>'Пожертвования','type'=>'expense','color'=>'#22d3ee','icon'=>'🙏'],
    ['label'=>'Прочее','type'=>'both','color'=>'#64748b','icon'=>'🧩']
  ],
  'Бизнес' => [
    /* Доходы */
    ['label'=>'Продажи','type'=>'income','color'=>'#22c55e','icon'=>'📈'],
    ['label'=>'Фото на документы','type'=>'income','color'=>'#004DFF','icon'=>'📸'],
    ['label'=>'Авансы','type'=>'income','color'=>'#16a34a','icon'=>'💰'],
    ['label'=>'Инвестиции','type'=>'income','color'=>'#10b981','icon'=>'📊'],
    ['label'=>'Прочие доходы','type'=>'income','color'=>'#059669','icon'=>'➕'],
    /* Запрошенные доходы */
    ['label'=>'Распечатка','type'=>'income','color'=>'#14b8a6','icon'=>'🖨️'],
    ['label'=>'Широкоформатная печать','type'=>'income','color'=>'#f43f5e','icon'=>'🖼️'],
    ['label'=>'Печать на холсте','type'=>'income','color'=>'#a855f7','icon'=>'🖌️'],
    ['label'=>'Ксерокопии','type'=>'income','color'=>'#0ea5e9','icon'=>'📄'],
    ['label'=>'Печати и штампы','type'=>'income','color'=>'#d97706','icon'=>'🔖'],
    ['label'=>'Визитки','type'=>'income','color'=>'#22d3ee','icon'=>'🪪'],
    ['label'=>'Листовки','type'=>'income','color'=>'#84cc16','icon'=>'🧾'],
    ['label'=>'Буклеты','type'=>'income','color'=>'#fb923c','icon'=>'📑'],
    /* Доп. доходы (умная детализация под полиграфию) */
    ['label'=>'Дизайн и макет','type'=>'income','color'=>'#eab308','icon'=>'🎨'],
    ['label'=>'Ламинирование','type'=>'income','color'=>'#34d399','icon'=>'🪟'],
    ['label'=>'Сканирование','type'=>'income','color'=>'#93c5fd','icon'=>'📠'],
    ['label'=>'Печать баннеров','type'=>'income','color'=>'#ef4444','icon'=>'🪧'],
    ['label'=>'Печать наклеек','type'=>'income','color'=>'#06b6d4','icon'=>'🏷️'],
    ['label'=>'Печать на одежде','type'=>'income','color'=>'#f59e0b','icon'=>'👕'],
    ['label'=>'Печать на кружках','type'=>'income','color'=>'#e879f9','icon'=>'☕'],
    ['label'=>'Сувенирная продукция','type'=>'income','color'=>'#10b981','icon'=>'🎁'],

    /* Расходы (базовые) */
    ['label'=>'Материалы','type'=>'expense','color'=>'#f59e0b','icon'=>'🧱'],
    ['label'=>'Себестоимость','type'=>'expense','color'=>'#f59e0b','icon'=>'🏷️'],
    ['label'=>'Зарплата (сотрудники)','type'=>'expense','color'=>'#ef4444','icon'=>'👷'],
    ['label'=>'Аренда (офис/склад)','type'=>'expense','color'=>'#8b5cf6','icon'=>'🏢'],
    ['label'=>'Реклама и маркетинг','type'=>'expense','color'=>'#f97316','icon'=>'📣'],
    ['label'=>'Логистика','type'=>'expense','color'=>'#3b82f6','icon'=>'🚚'],
    ['label'=>'Налоги (бизнес)','type'=>'expense','color'=>'#ef4444','icon'=>'🧾'],
    ['label'=>'Связь и ПО','type'=>'expense','color'=>'#06b6d4','icon'=>'🖥️'],
    ['label'=>'Командировки','type'=>'expense','color'=>'#0ea5e9','icon'=>'🧳'],
    ['label'=>'Офисные расходы','type'=>'expense','color'=>'#a78bfa','icon'=>'🖇️'],
    ['label'=>'Банковские комиссии','type'=>'expense','color'=>'#94a3b8','icon'=>'🏦'],
    ['label'=>'Прочие расходы','type'=>'expense','color'=>'#64748b','icon'=>'➖'],
    /* Запрошенные расходы */
    ['label'=>'КФС','type'=>'expense','color'=>'#dc2626','icon'=>'🍗'],
    ['label'=>'ПАБАР','type'=>'expense','color'=>'#b45309','icon'=>'🍺'],
    ['label'=>'ВБ','type'=>'expense','color'=>'#a21caf','icon'=>'🛍️'],
    ['label'=>'Озон','type'=>'expense','color'=>'#2563eb','icon'=>'📦'],
    ['label'=>'Расходники','type'=>'expense','color'=>'#475569','icon'=>'🧰'],
    /* Доп. расходы (умная детализация для полиграфии/офиса) */
    ['label'=>'Бумага и носители','type'=>'expense','color'=>'#60a5fa','icon'=>'📄'],
    ['label'=>'Чернила и тонер','type'=>'expense','color'=>'#ef4444','icon'=>'🖋️'],
    ['label'=>'Обслуживание оборудования','type'=>'expense','color'=>'#d946ef','icon'=>'🛠️'],
    ['label'=>'Оборудование (покупка)','type'=>'expense','color'=>'#a855f7','icon'=>'🖨️'],
    ['label'=>'Упаковка','type'=>'expense','color'=>'#f59e0b','icon'=>'📦'],
    ['label'=>'Комиссии маркетплейсов','type'=>'expense','color'=>'#fb7185','icon'=>'💳'],
    ['label'=>'Фулфилмент и хранение','type'=>'expense','color'=>'#4b5563','icon'=>'🏬'],
    ['label'=>'Юридические услуги','type'=>'expense','color'=>'#0ea5e9','icon'=>'⚖️'],
    ['label'=>'Бухгалтерия','type'=>'expense','color'=>'#16a34a','icon'=>'📚'],
    ['label'=>'Страхование','type'=>'expense','color'=>'#7dd3fc','icon'=>'🛡️'],
    ['label'=>'Проценты по кредитам','type'=>'expense','color'=>'#f97316','icon'=>'📉'],
    ['label'=>'Электроэнергия (производство)','type'=>'expense','color'=>'#fde047','icon'=>'⚡'],
    ['label'=>'Хозтовары','type'=>'expense','color'=>'#9ca3af','icon'=>'🧹']
  ]
];

/* Утилиты для категорий (совместимо со старыми версиями PHP) */
function build_category_map($catalog) {
  $map = [];
  foreach ($catalog as $grp => $items) {
    foreach ($items as $it) {
      $k = mb_strtolower(trim($it['label']));
      $map[$k] = [
        'label'=>$it['label'],
        'type'=>$it['type'],
        'color'=>$it['color'],
        'icon'=>$it['icon']
      ];
    }
  }
  $key = mb_strtolower('Прочее');
  if (!isset($map[$key])) {
    $map[$key] = ['label'=>'Прочее','type'=>'both','color'=>'#64748b','icon'=>'🧩'];
  }
  return $map;
}
function cat_meta($name, $map) {
  $k = mb_strtolower(trim($name ?: 'Прочее'));
  return isset($map[$k]) ? $map[$k] : ['label'=>($name ?: 'Прочее'),'type'=>'both','color'=>'#64748b','icon'=>'🏷️'];
}
function hex2rgba($hex, $alpha = 0.12) {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) {
    $r = hexdec(str_repeat($hex[0],2));
    $g = hexdec(str_repeat($hex[1],2));
    $b = hexdec(str_repeat($hex[2],2));
  } else {
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
  }
  return "rgba($r,$g,$b,$alpha)";
}
$CATEGORY_MAP = build_category_map($CATEGORY_CATALOG);

/* ====== НОВОЕ: Проверяем существование таблиц и создаем если нужно ====== */
try {
    // Проверяем таблицу managers
    $checkManagers = $pdo->query("SHOW TABLES LIKE 'managers'");
    if ($checkManagers->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE managers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                login VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Создаем тестового менеджера (логин: manager, пароль: manager123)
        $pdo->exec("
            INSERT INTO managers (name, login, password_hash) VALUES 
            ('Менеджер Продаж', 'manager', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
        ");
    }

    // Добавляем поле manager_id в transactions если его нет
    $checkManagerIdColumn = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'manager_id'");
    if ($checkManagerIdColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN manager_id INT NULL");
        $pdo->exec("ALTER TABLE transactions ADD INDEX idx_manager_id (manager_id)");
    }

    // Добавляем created_at если его нет
    $checkCreatedAtColumn = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'created_at'");
    if ($checkCreatedAtColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }

    // Проверяем таблицу sync_log
    $checkSyncLog = $pdo->query("SHOW TABLES LIKE 'manager_sync_log'");
    if ($checkSyncLog->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE manager_sync_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                transaction_id INT NOT NULL,
                manager_id INT NOT NULL,
                action ENUM('create', 'update', 'delete') NOT NULL,
                sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                synced_at TIMESTAMP NULL,
                INDEX idx_sync_status (sync_status),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_manager_id (manager_id)
            )
        ");
    }
} catch (Exception $e) {
    // Игнорируем ошибки создания таблиц для совместимости
}

/* ====== НОВОЕ: Получаем список менеджеров ====== */
$managers = [];
try {
    $managersStmt = $pdo->query("SELECT id, name FROM managers WHERE active = 1 ORDER BY name");
    $managers = $managersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Игнорируем если таблицы еще нет
}

/* ====== ДОБАВЛЕНО: аналитика по месяцам (для диаграммы) ====== */
$month = (isset($_GET['m']) && preg_match('/^\d{4}-\d{2}$/', $_GET['m'])) ? $_GET['m'] : date('Y-m');
$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

$aggStmt = $pdo->prepare("SELECT type, COALESCE(NULLIF(category,''),'Прочее') as category, SUM(amount) as total
                          FROM transactions
                          WHERE txn_date BETWEEN ? AND ?
                          GROUP BY type, category
                          ORDER BY total DESC");
$aggStmt->execute([$monthStart, $monthEnd]);
$aggRows = $aggStmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = ['income'=>[], 'expense'=>[]];
foreach ($aggRows as $r) {
  $t = ($r['type'] === 'income') ? 'income' : 'expense';
  $c = $r['category'] ?: 'Прочее';
  $grouped[$t][$c] = (float)$r['total'];
}
$totals = [
  'income' => array_sum($grouped['income']),
  'expense' => array_sum($grouped['expense'])
];

/* ====== НОВОЕ: Статистика по менеджерам ====== */
$managerStats = [];
try {
    $managerStatsStmt = $pdo->prepare("
        SELECT 
            m.id,
            m.name,
            COUNT(t.id) as transaction_count,
            SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income_total,
            SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as expense_total,
            MAX(t.created_at) as last_transaction
        FROM managers m
        LEFT JOIN transactions t ON m.id = t.manager_id 
            AND t.txn_date >= ? AND t.txn_date <= ?
        WHERE m.active = 1
        GROUP BY m.id, m.name
        ORDER BY transaction_count DESC
    ");
    $managerStatsStmt->execute([$monthStart, $monthEnd]);
    $managerStats = $managerStatsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Игнорируем если таблиц еще нет
}

function build_chart_payload($bucket, $catmap, $topN = 6) {
  arsort($bucket);
  $labels = array_keys($bucket);
  $values = array_values($bucket);
  $topLabels = array_slice($labels, 0, $topN);
  $topValues = array_slice($values, 0, $topN);
  $rest = array_sum(array_slice($values, $topN));
  if ($rest > 0) { $topLabels[] = 'Остальное'; $topValues[] = $rest; }

  $colors = [];
  $icons  = [];
  $legend = [];
  foreach ($topLabels as $i => $lbl) {
    $meta = cat_meta($lbl, $catmap);
    if ($lbl === 'Остальное') {
      $colors[] = '#94a3b8';
      $icons[]  = '…';
      $legend[] = ['label'=>$lbl,'value'=>$topValues[$i],'color'=>'#94a3b8','icon'=>'…'];
    } else {
      $colors[] = $meta['color'];
      $icons[]  = $meta['icon'];
      $legend[] = ['label'=>$lbl,'value'=>$topValues[$i],'color'=>$meta['color'],'icon'=>$meta['icon']];
    }
  }
  return ['labels'=>$topLabels,'data'=>$topValues,'colors'=>$colors,'icons'=>$icons,'legend'=>$legend];
}
$chartIncome = build_chart_payload($grouped['income'], $CATEGORY_MAP);
$chartExpense = build_chart_payload($grouped['expense'], $CATEGORY_MAP);
$chartPayload = [
  'month' => $month,
  'totals' => $totals,
  'income' => $chartIncome,
  'expense' => $chartExpense
];

/* ====== /конец добавлений ====== */

if ($_SERVER['REQUEST_METHOD']==='POST' && can(['director','manager'])) {
  $act=$_POST['action'] ?? '';
  if ($act==='create') {
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

    $st=$pdo->prepare("INSERT INTO transactions (txn_date,amount,type,category,comment,order_id,manager_id,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
    $st->execute([$_POST['txn_date'],(float)$_POST['amount'],$_POST['type'],($_POST['category']?:null),$_POST['comment']??'', $_POST['order_id']?:null, $manager_id]);

    // Логируем для синхронизации если транзакция от менеджера
    if ($manager_id) {
        try {
            $transaction_id = $pdo->lastInsertId();
            $sync_stmt = $pdo->prepare("INSERT INTO manager_sync_log (transaction_id, manager_id, action, sync_status, created_at) VALUES (?, ?, 'create', 'pending', NOW())");
            $sync_stmt->execute([$transaction_id, $manager_id]);
        } catch (Exception $e) {
            // Игнорируем ошибки логирования
        }
    }

    set_flash('Транзакция добавлена');
    if ($_POST['type']==='income') $show_png = true;
  } elseif ($act==='update') {
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

    $st=$pdo->prepare("UPDATE transactions SET txn_date=?,amount=?,type=?,category=?,comment=?,order_id=?,manager_id=? WHERE id=?");
    $st->execute([$_POST['txn_date'],(float)$_POST['amount'],$_POST['type'],($_POST['category']?:null),$_POST['comment']??'', $_POST['order_id']?:null, $manager_id, (int)$_POST['id']]);

    // Логируем изменение
    if ($manager_id) {
        try {
            $sync_stmt = $pdo->prepare("INSERT INTO manager_sync_log (transaction_id, manager_id, action, sync_status, created_at) VALUES (?, ?, 'update', 'pending', NOW())");
            $sync_stmt->execute([(int)$_POST['id'], $manager_id]);
        } catch (Exception $e) {
            // Игнорируем ошибки логирования
        }
    }

    set_flash('Транзакция обновлена');
    if ($_POST['type']==='income') $show_png = true;
  } elseif ($act==='delete') {
    // Получаем manager_id перед удалением для логирования
    $txnToDelete = $pdo->prepare("SELECT manager_id FROM transactions WHERE id = ?");
    $txnToDelete->execute([(int)$_POST['id']]);
    $txnData = $txnToDelete->fetch();

    $pdo->prepare("DELETE FROM transactions WHERE id=?")->execute([(int)$_POST['id']]);

    // Логируем удаление
    if ($txnData && $txnData['manager_id']) {
        try {
            $sync_stmt = $pdo->prepare("INSERT INTO manager_sync_log (transaction_id, manager_id, action, sync_status, created_at) VALUES (?, ?, 'delete', 'pending', NOW())");
            $sync_stmt->execute([(int)$_POST['id'], $txnData['manager_id']]);
        } catch (Exception $e) {
            // Игнорируем ошибки логирования
        }
    }

    set_flash('Транзакция удалена','warn');
  } elseif ($act==='create_manager') {
    // Новое действие для создания менеджера
    if (can(['director'])) {
        $name = trim($_POST['manager_name']);
        $login = trim($_POST['manager_login']);
        $password = trim($_POST['manager_password']);

        if ($name && $login && $password) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $createManager = $pdo->prepare("INSERT INTO managers (name, login, password_hash) VALUES (?, ?, ?)");
                $createManager->execute([$name, $login, $password_hash]);
                set_flash("Менеджер '$name' создан успешно!");
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    set_flash('Логин уже используется', 'error');
                } else {
                    set_flash('Ошибка создания менеджера: ' . $e->getMessage(), 'error');
                }
            }
        } else {
            set_flash('Заполните все поля для создания менеджера', 'error');
        }
    }
  }
}

$q = trim($_GET['q'] ?? ''); $type=$_GET['type'] ?? ''; $cat=$_GET['category'] ?? ''; $mgr=$_GET['manager'] ?? '';
$cond="1=1"; $p=[];
if ($q!==''){ $cond.=" AND (comment LIKE ? OR CAST(amount AS CHAR) LIKE ?)"; $p[]="%$q%"; $p[]="%$q%"; }
if (in_array($type,['income','expense'],true)){ $cond.=" AND type=?"; $p[]=$type; }
if ($cat!==''){ $cond.=" AND category=?"; $p[]=$cat; }
if ($mgr!==''){ $cond.=" AND manager_id=?"; $p[]=(int)$mgr; }

$tx=$pdo->prepare("
    SELECT t.*, m.name as manager_name 
    FROM transactions t 
    LEFT JOIN managers m ON t.manager_id = m.id 
    WHERE $cond 
    ORDER BY t.txn_date DESC, t.id DESC 
    LIMIT 300
"); 
$tx->execute($p); 
$tx=$tx->fetchAll();

$orders=$pdo->query("SELECT id, description FROM orders ORDER BY id DESC LIMIT 200")->fetchAll();
$cats=$pdo->query("SELECT DISTINCT COALESCE(NULLIF(category,''),'Прочее') c FROM transactions ORDER BY c")->fetchAll(PDO::FETCH_COLUMN);
$png = setting('txn_success_png_path','');

/* ====== ДОБАВЛЕНО: сервисные функции для меток месяцев ====== */
function ru_month_label($ym) {
  $parts = explode('-', $ym);
  $y = isset($parts[0]) ? $parts[0] : date('Y');
  $m = isset($parts[1]) ? (int)$parts[1] : (int)date('n');
  $names = [1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь'];
  return $names[$m] . ' ' . $y;
}
?>
<style>
/* ====== ДОБАВЛЕНО: стили для категорий и аналитики с повышенной читаемостью ====== */
.cat-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;border:1px solid var(--c,#64748b); background: var(--bg, #0f172a); color:#e5e7eb;font-size:12px;line-height:18px;white-space:nowrap}
.cat-pill .ico{font-size:14px}
.table .neg{color:#ef4444}.table .pos{color:#16a34a}

.analytics.panel{margin-top:16px}
.analytics-wrap{display:grid;grid-template-columns: 340px 1fr;gap:20px; align-items:start}
@media (max-width:900px){.analytics-wrap{grid-template-columns:1fr}}
.donut-wrap{display:flex;flex-direction:column;align-items:center;gap:8px;padding:8px}
.legend{display:flex;flex-wrap:wrap;gap:8px}
.legend .item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;background:#0b1220;color:#e5e7eb}
.legend .dot{width:12px;height:12px;border-radius:50%}
.legend .lbl{font-weight:600}
.legend .val{opacity:.9}
.analytics-header{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.analytics-header .btnseg{display:inline-flex;border:1px solid #374151;border-radius:8px;overflow:hidden}
.analytics-header .btnseg button{padding:6px 10px;border:0;background:#0b0e13;color:#e5e7eb;cursor:pointer}
.analytics-header .btnseg button.active{background:#111827;color:#fff}
.analytics-header select{padding:6px;border-radius:8px;border:1px solid #374151;background:#0b0e13;color:#e5e7eb}
.total-amount{font-size:20px;font-weight:800;margin-left:auto;color:#fff}
.badge-click{cursor:pointer}

/* Быстрый выбор категорий в модалке */
.cat-preset{margin-top:6px}
.cat-tabs{display:inline-flex;border:1px solid #374151;border-radius:8px;overflow:hidden;margin-bottom:8px}
.cat-tabs button{padding:6px 10px;border:0;background:#0b0e13;color:#e5e7eb;cursor:pointer}
.cat-tabs button.active{background:#111827;color:#fff}
.cat-chips{display:flex;flex-wrap:wrap;gap:6px}
.cat-chip{padding:4px 8px;border-radius:999px;border:1px solid #475569;background:#0f172a;color:#e5e7eb;font-size:12px;cursor:pointer}
.cat-chip:hover{filter:brightness(1.1)}

/* ====== НОВОЕ: Стили для менеджера ====== */
.manager-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#1e293b;border:1px solid #475569;color:#e5e7eb;font-size:11px;white-space:nowrap}
.manager-pill .ico{font-size:12px}

.manager-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px}
.manager-stat-card{background:#1e293b;padding:12px;border-radius:8px;border:1px solid #334155}
.manager-stat-card .name{font-weight:600;margin-bottom:4px;font-size:13px}
.manager-stat-card .stats{display:flex;gap:12px;font-size:12px}
.manager-stat-card .stat{display:flex;flex-direction:column;align-items:center}
.manager-stat-card .stat .val{font-weight:600;font-size:14px}
.manager-stat-card .stat .lbl{color:#94a3b8;font-size:10px}

.manager-form{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end;margin-top:12px;padding:12px;background:#0f172a;border-radius:8px;border:1px solid #334155}
.manager-form input{padding:6px;font-size:12px}
.manager-form button{padding:6px 12px;font-size:12px}

/* Индикаторы синхронизации */
.sync-indicator{display:inline-flex;align-items:center;gap:4px;padding:2px 6px;border-radius:12px;font-size:10px;font-weight:600}
.sync-indicator.pending{background:#f59e0b;color:#000}
.sync-indicator.synced{background:#10b981;color:#000}
.sync-indicator.failed{background:#ef4444;color:#fff}

/* Улучшенные фильтры */
.filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.filters input,.filters select{padding:8px;border-radius:6px;border:1px solid #374151;background:#0f172a;color:#e5e7eb;font-size:13px}
.filters .btn{padding:8px 16px;font-size:13px}

/* ====== НОВОЕ: Складные секции (collapsible) ====== */
details{margin-bottom:16px}
details summary{
  cursor:pointer;
  padding:12px 16px;
  background:linear-gradient(135deg, rgba(17,24,39,0.95), rgba(31,41,55,0.95));
  border:1px solid rgba(255,255,255,0.1);
  border-radius:12px;
  color:#f3f4f6;
  font-weight:700;
  font-size:15px;
  display:flex;
  align-items:center;
  gap:8px;
  transition:all 0.2s ease;
  box-shadow:0 2px 8px rgba(0,0,0,0.2);
}
details summary:hover{
  background:linear-gradient(135deg, rgba(31,41,55,0.95), rgba(55,65,81,0.95));
  border-color:rgba(255,255,255,0.15);
  box-shadow:0 4px 12px rgba(0,0,0,0.3);
}
details summary::marker{content:'▶ '}
details[open] summary::marker{content:'▼ '}
details[open] summary{
  border-bottom-left-radius:0;
  border-bottom-right-radius:0;
  margin-bottom:0;
}
details .details-content{
  border:1px solid rgba(255,255,255,0.1);
  border-top:none;
  border-radius:0 0 12px 12px;
  padding:16px;
  background:rgba(15,23,42,0.6);
  backdrop-filter:blur(8px);
}

/* Счётчик результатов */
.results-counter{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:4px 12px;
  background:#1e293b;
  border:1px solid #334155;
  border-radius:999px;
  font-size:13px;
  color:#94a3b8;
  margin-left:auto;
}
.results-counter .count{
  font-weight:700;
  color:#fff;
}

@media (max-width:768px){
  .manager-form{grid-template-columns:1fr;gap:8px}
  .analytics-wrap{grid-template-columns:1fr}
  .manager-stats{grid-template-columns:1fr}
  .filters{flex-direction:column;align-items:stretch}
  .filters input, .filters select, .filters .btn{width:100%}
}
</style>

<!-- ====== ГЛАВНОЕ: ТАБЛИЦА ТРАНЗАКЦИЙ (сразу видна) ====== -->
<div class="panel glass">
  <div class="panel-header">
    <h2>💰 Транзакции</h2>
    <div class="results-counter">
      <span>Найдено:</span>
      <span class="count"><?=count($tx)?></span>
    </div>
    <?php if (can(['director','manager'])): ?>
      <button type="button" class="btn primary" data-open="#txnModal">+ Добавить транзакцию</button>
    <?php endif; ?>
  </div>

  <form class="filters" method="get">
    <input type="hidden" name="page" value="transactions">
    <input type="text" name="q" placeholder="🔍 Поиск по комментарию или сумме" value="<?=e($q)?>">
    <select name="type">
      <option value="">Все типы</option>
      <option value="income" <?= $type==='income'?'selected':'' ?>>📈 Поступление</option>
      <option value="expense" <?= $type==='expense'?'selected':'' ?>>📉 Расход</option>
    </select>
    <select name="category">
      <option value="">Все категории</option>
      <?php foreach($cats as $c): ?>
        <option value="<?=e($c)?>" <?= $cat===$c?'selected':'' ?>><?=e($c)?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($managers)): ?>
    <select name="manager">
      <option value="">Все менеджеры</option>
      <?php foreach($managers as $m): ?>
        <option value="<?=$m['id']?>" <?= $mgr==(string)$m['id']?'selected':'' ?>>👤 <?=e($m['name'])?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <button class="btn primary">🔍 Применить фильтры</button>
    <?php if($q || $type || $cat || $mgr): ?>
      <a href="?page=transactions" class="btn outline">✕ Сбросить</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Дата</th>
          <th>Сумма</th>
          <th>Тип</th>
          <th>Категория</th>
          <th>Комментарий</th>
          <th>Заказ</th>
          <?php if (!empty($managers)): ?>
          <th>Менеджер</th>
          <?php endif; ?>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($tx as $t): ?>
        <?php $cm = cat_meta($t['category'] ?: 'Прочее', $CATEGORY_MAP); ?>
        <tr>
          <td><?=$t['id']?></td>
          <td><?=e($t['txn_date'])?></td>
          <td class="<?= $t['type']==='income'?'pos':'neg' ?>"><?=e(number_format($t['amount'],2,',',' '))?> ₽</td>
          <td><?= $t['type']==='income'?'📈 Поступление':'📉 Расход' ?></td>
          <td>
            <?php if ($t['category']): ?>
              <span class="cat-pill badge-click" onclick="filterByCategory('<?=e($t['category'])?>')" style="--c:<?=$cm['color']?>;--bg:<?=hex2rgba($cm['color'],0.18)?>;border-color:<?=$cm['color']?>">
                <span class="ico"><?=$cm['icon']?></span><span><?=e($t['category'])?></span>
              </span>
            <?php else: ?>
              <span style="color:#94a3b8">—</span>
            <?php endif; ?>
          </td>
          <td><?=e($t['comment'])?></td>
          <td><?= $t['order_id'] ? ('#'.$t['order_id']) : '<span style="color:#94a3b8">—</span>' ?></td>
          <?php if (!empty($managers)): ?>
          <td>
            <?php if ($t['manager_name']): ?>
              <span class="manager-pill badge-click" onclick="filterByManager('<?=$t['manager_id']?>')">
                <span class="ico">👤</span><span><?=e($t['manager_name'])?></span>
              </span>
            <?php else: ?>
              <span style="color:#94a3b8">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td class="row-actions">
            <?php if (can(['director','manager'])): ?>
              <button class="btn ghost small" data-edit='<?= e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>' data-open="#txnModal">✏️</button>
              <form method="post" onsubmit="return confirm('Удалить транзакцию #<?=$t['id']?>?')" style="display:inline">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=$t['id']?>">
                <button class="btn outline small">🗑️</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; if(!$tx): ?>
        <tr><td colspan="<?=!empty($managers)?'9':'8'?>" style="color:#94a3b8;text-align:center;padding:40px">
          <?php if($q || $type || $cat || $mgr): ?>
            🔍 Ничего не найдено по заданным фильтрам
          <?php else: ?>
            📭 Транзакций пока нет
          <?php endif; ?>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ====== НОВОЕ: АНАЛИТИКА (складная секция, внизу) ====== -->
<details open>
  <summary>📈 Аналитика по доходам и расходам</summary>
  <div class="details-content">
    <div class="panel glass analytics" style="margin:0">
      <div class="analytics-header" style="padding:8px 12px 0 12px;">
        <div>
          <label for="mSel" style="font-size:12px;opacity:.85">Месяц</label>
          <select id="mSel" onchange="location.search=setQuery(location.search,'m',this.value)">
            <?php for($i=0;$i<18;$i++):
              $ym = date('Y-m', strtotime("-$i month"));
              $sel = $ym===$month ? 'selected' : '';
            ?>
              <option value="<?=e($ym)?>" <?=$sel?>><?=e(ru_month_label($ym))?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="btnseg" id="typeSeg">
          <button data-t="income" class="active">Доходы</button>
          <button data-t="expense">Расходы</button>
        </div>
        <div class="total-amount">
          <span id="totalLabel">Доходы</span>: <span id="totalAmount"><?=number_format($totals['income'],0,',',' ')?></span> ₽
        </div>
      </div>
      <div class="analytics-wrap" style="padding:12px">
        <div class="donut-wrap">
          <canvas id="donutChart" width="280" height="280" aria-label="Диаграмма"></canvas>
        </div>
        <div>
          <div class="legend" id="legendBox">
            <!-- элементы подставятся скриптом -->
          </div>
        </div>
      </div>
    </div>
  </div>
</details>

<!-- ====== НОВОЕ: СТАТИСТИКА МЕНЕДЖЕРОВ (складная секция, только для director) ====== -->
<?php if (!empty($managerStats) && can(['director'])): ?>
<details>
  <summary>📊 Эффективность менеджеров за <?=e(ru_month_label($month))?></summary>
  <div class="details-content">
    <div class="manager-stats">
      <?php foreach ($managerStats as $stat): 
        $balance = $stat['income_total'] - $stat['expense_total'];
      ?>
        <div class="manager-stat-card">
          <div class="name">👤 <?=e($stat['name'])?></div>
          <div class="stats">
            <div class="stat">
              <span class="val"><?=number_format($stat['transaction_count'])?></span>
              <span class="lbl">операций</span>
            </div>
            <div class="stat">
              <span class="val pos">+<?=number_format($stat['income_total'],0,',',' ')?></span>
              <span class="lbl">доходы</span>
            </div>
            <div class="stat">
              <span class="val neg">-<?=number_format($stat['expense_total'],0,',',' ')?></span>
              <span class="lbl">расходы</span>
            </div>
            <div class="stat">
              <span class="val <?=$balance>=0?'pos':'neg'?>"><?=$balance>=0?'+':''?><?=number_format($balance,0,',',' ')?></span>
              <span class="lbl">баланс</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Форма создания нового менеджера -->
    <?php if (can(['director'])): ?>
    <details style="margin-top:16px">
      <summary style="cursor:pointer;color:#94a3b8;font-size:13px;padding:8px">➕ Добавить нового менеджера</summary>
      <form method="post" class="manager-form" style="margin-top:8px">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="create_manager">
        <input type="text" name="manager_name" placeholder="Имя менеджера" required>
        <input type="text" name="manager_login" placeholder="Логин" required>
        <input type="password" name="manager_password" placeholder="Пароль" required>
        <button type="submit" class="btn primary">Создать</button>
      </form>
      <div style="margin-top:8px;font-size:11px;color:#94a3b8">
        💡 Менеджер сможет войти в систему используя созданный логин и пароль
      </div>
    </details>
    <?php endif; ?>
  </div>
</details>
<?php endif; ?>

<!-- ====== МОДАЛЬНОЕ ОКНО для создания/редактирования ====== -->
<?php if (can(['director','manager'])): ?>
<div class="modal" id="txnModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="txnModalTitle">✨ Новая транзакция</h3>
      <button class="btn ghost" data-close>✕</button>
    </div>
    <form method="post" class="form-grid">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="action" value="create" id="txnFormAction">
      <input type="hidden" name="id" id="txnId">

      <label>📅 Дата</label>
      <input type="date" name="txn_date" id="txnDate" value="<?=date('Y-m-d')?>" required>

      <label>💰 Сумма</label>
      <input type="number" step="0.01" name="amount" id="txnAmount" value="0" min="0.01" required>

      <label>📊 Тип</label>
      <select name="type" id="txnType" required>
        <option value="income">📈 Поступление</option>
        <option value="expense">📉 Расход</option>
      </select>

      <label>🏷️ Категория</label>
      <input type="text" name="category" id="txnCategory" list="catlist" placeholder="Выберите или начните ввод">
      <datalist id="catlist">
        <?php
          $dlSet = [];
          foreach ($CATEGORY_CATALOG as $grp => $items) {
            foreach ($items as $it) { $dlSet[$it['label']] = 1; }
          }
          foreach ($cats as $c) { $dlSet[$c] = 1; }
          foreach (array_keys($dlSet) as $val): ?>
            <option value="<?=e($val)?>">
        <?php endforeach; ?>
      </datalist>

      <!-- Быстрый выбор категорий по группам -->
      <div class="cat-preset">
        <div class="cat-tabs" id="catTabs">
          <button type="button" data-group="Домашние" class="active">🏠 Домашние</button>
          <button type="button" data-group="Бизнес">🏢 Бизнес</button>
        </div>
        <div class="cat-chips" id="catChips"></div>
      </div>

      <label>💬 Комментарий</label>
      <input type="text" name="comment" id="txnComment" placeholder="Описание операции">

      <label>📦 Заказ (опц.)</label>
      <select name="order_id" id="txnOrder">
        <option value="">— Без привязки к заказу —</option>
        <?php foreach($orders as $o): ?>
          <option value="<?=$o['id']?>">#<?=$o['id']?> — <?=e(mb_strimwidth($o['description'],0,48,'…','UTF-8'))?></option>
        <?php endforeach; ?>
      </select>

      <?php if (!empty($managers)): ?>
      <label>👤 Менеджер (опц.)</label>
      <select name="manager_id" id="txnManager">
        <option value="">— Без менеджера —</option>
        <?php foreach($managers as $m): ?>
          <option value="<?=$m['id']?>"><?=e($m['name'])?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <div class="modal-actions">
        <button class="btn primary">💾 Сохранить транзакцию</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ====== SUCCESS PNG (если была добавлена транзакция дохода) ====== -->
<?php if ($show_png && $png): ?>
<style>
.kiss-overlay{position:fixed; inset:0; background:rgba(0,0,0,0.55); display:flex; align-items:center; justify-content:center; z-index:2000}
.kiss-card{position:relative; border-radius:18px; padding:16px; text-align:center; color:#fff;
  background:rgba(17,24,39,.88);
  border:1px solid rgba(255,255,255,0.18); backdrop-filter: blur(10px);
  box-shadow:0 10px 30px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.06), 0 0 10px rgba(255,140,0,0.14);
}
.kiss-img{max-width:320px; max-height:320px; display:block; border-radius:12px}
</style>
<div class="kiss-overlay" id="kissOverlay">
  <div class="kiss-card">
    <img class="kiss-img" src="<?=e($png)?>" alt="success">
    <div style="margin-top:8px"><button class="btn" onclick="document.getElementById('kissOverlay').remove()">👍 Отлично!</button></div>
  </div>
</div>
<?php endif; ?>

<!-- Chart.js (для диаграммы) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
/* ====== Диаграмма и легенда ====== */
const chartPayload = <?=json_encode($chartPayload, JSON_UNESCAPED_UNICODE)?>;

function setQuery(qs, key, val){
  const p = new URLSearchParams(qs.startsWith('?')?qs.substring(1):qs);
  p.set('page', p.get('page') || 'transactions');
  p.set(key, val);
  return '?' + p.toString();
}
function filterByCategory(cat){
  const p = new URLSearchParams(location.search);
  p.set('page', p.get('page') || 'transactions');
  p.set('category', cat);
  location.search = '?' + p.toString();
}
function filterByManager(managerId){
  const p = new URLSearchParams(location.search);
  p.set('page', p.get('page') || 'transactions');
  p.set('manager', managerId);
  location.search = '?' + p.toString();
}

const typeSeg = document.getElementById('typeSeg');
let currentType = 'income';
const ctx = document.getElementById('donutChart').getContext('2d');

const centerText = {
  id:'centerText',
  afterDatasetsDraw(chart){
    const ctx = chart.ctx;
    const {left,right,top,bottom} = chart.chartArea;
    const txt = (currentType==='income'?'Доходы':'Расходы');
    const total = currentType==='income' ? chartPayload.totals.income : chartPayload.totals.expense;
    ctx.save();
    ctx.font = '600 14px system-ui, -apple-system, Segoe UI, Roboto';
    ctx.fillStyle = '#9ca3af';
    ctx.textAlign = 'center';
    ctx.fillText(txt, (left+right)/2, (top+bottom)/2 - 8);
    ctx.font = '800 18px system-ui, -apple-system, Segoe UI, Roboto';
    ctx.fillStyle = '#f3f4f6';
    ctx.fillText(new Intl.NumberFormat('ru-RU').format(total) + ' ₽', (left+right)/2, (top+bottom)/2 + 16);
    ctx.restore();
  }
};

let chart;
function renderLegend(payload){
  const box = document.getElementById('legendBox');
  box.innerHTML = '';
  const total = (currentType==='income'?chartPayload.totals.income:chartPayload.totals.expense) || 0;
  payload.legend.forEach(item=>{
    const perc = total>0 ? Math.round(item.value/total*100) : 0;
    const el = document.createElement('div');
    el.className = 'item badge-click';
    el.style.border = '1px solid '+item.color;
    el.onclick = ()=>filterByCategory(item.label === 'Остальное' ? '' : item.label);
    el.innerHTML = `<span class="dot" style="background:${item.color}"></span>
                    <span class="lbl">${item.icon} ${item.label}</span>
                    <span class="val">— ${new Intl.NumberFormat('ru-RU').format(item.value)} ₽ (${perc}%)</span>`;
    box.appendChild(el);
  });
}
function updateTotalHeader(){
  document.getElementById('totalLabel').textContent = currentType==='income' ? 'Доходы' : 'Расходы';
  const total = currentType==='income' ? chartPayload.totals.income : chartPayload.totals.expense;
  document.getElementById('totalAmount').textContent = new Intl.NumberFormat('ru-RU').format(total);
}
function renderChart(){
  const payload = chartPayload[currentType];
  updateTotalHeader();
  renderLegend(payload);
  const data = { labels: payload.labels, datasets: [{ data: payload.data, backgroundColor: payload.colors, borderWidth: 0 }] };
  const opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display:false } } };
  if (chart) chart.destroy();
  chart = new Chart(ctx, { type:'doughnut', data, options:opts, plugins:[centerText] });
}
typeSeg.querySelectorAll('button').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    typeSeg.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    currentType = btn.dataset.t;
    renderChart();
  });
});
renderChart();

/* ====== Быстрый выбор категорий в модалке ====== */
const CAT_CATALOG = <?=json_encode($CATEGORY_CATALOG, JSON_UNESCAPED_UNICODE)?>;
const catTabs = document.getElementById('catTabs');
const catChips = document.getElementById('catChips');
const catInput = document.getElementById('txnCategory');
const typeSel = document.getElementById('txnType');

function renderCatChips(group){
  const items = CAT_CATALOG[group] || [];
  const needType = typeSel ? typeSel.value : 'income';
  catChips.innerHTML = '';
  items.filter(it => it.type==='both' || it.type===needType).forEach(it=>{
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'cat-chip';
    b.style.borderColor = it.color;
    b.textContent = `${it.icon} ${it.label}`;
    b.onclick = ()=>{ catInput.value = it.label; };
    catChips.appendChild(b);
  });
}
if (catTabs && catChips) {
  let currentGroup = 'Домашние';
  renderCatChips(currentGroup);
  catTabs.querySelectorAll('button').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      catTabs.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      currentGroup = btn.getAttribute('data-group');
      renderCatChips(currentGroup);
    });
  });
  if (typeSel) typeSel.addEventListener('change', ()=>renderCatChips(currentGroup));
}

/* ====== Редактирование транзакции ====== */
document.querySelectorAll('[data-edit]').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const d=JSON.parse(btn.getAttribute('data-edit')); 
    const el=document.getElementById('txnModal');
    el.querySelector('#txnModalTitle').textContent='✏️ Изменение транзакции #'+(d.id||'');
    el.querySelector('#txnFormAction').value='update';
    el.querySelector('#txnId').value=d.id||'';
    el.querySelector('#txnDate').value=d.txn_date||'';
    el.querySelector('#txnAmount').value=d.amount||0;
    el.querySelector('#txnType').value=d.type||'income';
    el.querySelector('#txnCategory').value=d.category||'';
    el.querySelector('#txnComment').value=d.comment||'';
    el.querySelector('#txnOrder').value=d.order_id||'';

    const managerSelect = el.querySelector('#txnManager');
    if (managerSelect) {
      managerSelect.value = d.manager_id || '';
    }

    // Обновляем категории для выбранного типа
    const currentGroup = catTabs.querySelector('button.active')?.getAttribute('data-group') || 'Домашние';
    renderCatChips(currentGroup);
  });
});

/* ====== Сброс формы при открытии для создания ====== */
const openModalBtn = document.querySelector('[data-open="#txnModal"]');
if(openModalBtn) {
  openModalBtn.addEventListener('click', ()=>{
    const el = document.getElementById('txnModal');
    el.querySelector('#txnModalTitle').textContent = '✨ Новая транзакция';
    el.querySelector('#txnFormAction').value = 'create';
    el.querySelector('#txnId').value = '';
    el.querySelector('#txnDate').value = '<?=date('Y-m-d')?>';
    el.querySelector('#txnAmount').value = '';
    el.querySelector('#txnType').value = 'income';
    el.querySelector('#txnCategory').value = '';
    el.querySelector('#txnComment').value = '';
    el.querySelector('#txnOrder').value = '';

    const managerSelect = el.querySelector('#txnManager');
    if (managerSelect) {
      managerSelect.value = '';
    }

    // Сбрасываем на домашние категории с доходами
    catTabs.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
    catTabs.querySelector('[data-group="Домашние"]').classList.add('active');
    renderCatChips('Домашние');
  });
}

console.log('✅ Транзакции загружены успешно!');
console.log('📊 Менеджеров в системе: <?=count($managers)?>');
console.log('💰 Найдено транзакций: <?=count($tx)?>');
</script>
