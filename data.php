<?php
require_login();
if ($_SERVER['REQUEST_METHOD']==='POST') check_csrf();

/* Миграции: безопасно добавим недостающие поля/индексы для модуля заказов */
try{
  $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
  if ($cols && !in_array('priority',$cols,true)) {
    $pdo->exec("ALTER TABLE orders ADD COLUMN priority TINYINT NOT NULL DEFAULT 1");
  }
  // Индекс для ускорения сортировки/фильтрации (мягко, в try)
  if ($cols && !in_array('status',$cols,true)){} // просто проверка наличия
  $pdo->exec("ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_orders_status_due_prio (status, due_date, priority)");
}catch(Throwable $e){}

/* Утилиты */
function create_income_txn(PDO $pdo, float $amount, int $order_id, string $note){
  if ($amount <= 0) return;
  $pdo->prepare("INSERT INTO transactions (txn_date,amount,type,category,comment,order_id)
                 VALUES (CURDATE(),?,'income','Оплаты заказов',?,?)")
      ->execute([$amount, $note, $order_id]);
}
function fetch_order_items(PDO $pdo, array $orderIds): array {
  if (!$orderIds) return [];
  try {
    $in = implode(',', array_map('intval',$orderIds));
    $q = $pdo->query("SELECT oi.order_id, oi.item_id, oi.qty, ii.name
                      FROM order_items oi
                      LEFT JOIN inventory_items ii ON ii.id=oi.item_id
                      WHERE oi.order_id IN ($in)");
    $map = [];
    foreach ($q as $r) $map[$r['order_id']][] = ['id'=>(int)$r['item_id'],'qty'=>(int)$r['qty'],'name'=>$r['name']];
    return $map;
  } catch (Throwable $e) { return []; }
}
function apply_order_items(PDO $pdo, int $order_id, array $items): void {
  // Транзакция для целостности остатков на складе
  try {
    $pdo->beginTransaction();

    // Вернём старые остатки
    $old = $pdo->prepare("SELECT item_id, qty FROM order_items WHERE order_id=?");
    $old->execute([$order_id]);
    foreach ($old->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $pdo->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE id=?")
          ->execute([(int)$r['qty'], (int)$r['item_id']]);
    }
    // Очистим старые позиции
    $pdo->prepare("DELETE FROM order_items WHERE order_id=?")->execute([$order_id]);

    // Добавим новые позиции и спишем остатки
    foreach ($items as $it) {
      $iid = (int)($it['id'] ?? 0);
      $q   = max(0, (int)($it['qty'] ?? 0));
      if ($iid && $q>0) {
        $priceSt = $pdo->prepare("SELECT price FROM inventory_items WHERE id=?");
        $priceSt->execute([$iid]);
        $p = (float)($priceSt->fetchColumn() ?: 0);
        $pdo->prepare("INSERT INTO order_items (order_id,item_id,qty,price) VALUES (?,?,?,?)")
            ->execute([$order_id,$iid,$q,$p]);
        // Позволяем уходить в минус при необходимости (не блокируем заказ), но в проде можно проверять >= q
        $pdo->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id=?")->execute([$q,$iid]);
      }
    }

    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
  }
}

/* CRUD */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $act = $_POST['action'] ?? '';
  if ($act==='create' && can(['director','manager'])) {
    $order_date   = $_POST['order_date'] ?: date('Y-m-d');
    $client_id    = $_POST['client_id'] ?: null;
    $description  = $_POST['description'] ?? '';
    $price        = (float)($_POST['price'] ?? 0);
    $paid_amount  = (float)($_POST['paid_amount'] ?? 0);
    $product_type = $_POST['product_type'] ?: null;
    $quantity     = (int)($_POST['quantity'] ?? 1);
    $size         = $_POST['size'] ?: null;
    $material     = $_POST['material'] ?: null;
    $color_mode   = $_POST['color_mode'] ?: null;
    $lamination   = isset($_POST['lamination']) ? 1 : 0;
    $frame        = isset($_POST['frame']) ? 1 : 0;
    $dpi          = $_POST['dpi'] ?: null;
    $status       = $_POST['status'] ?? 'in_progress';
    $due_date     = $_POST['due_date'] ?: null;
    $priority     = (int)($_POST['priority'] ?? 1);

    $pdo->prepare("INSERT INTO orders (order_date,client_id,description,price,paid_amount,product_type,quantity,size,material,color_mode,lamination,frame,dpi,status,due_date,priority)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$order_date,$client_id,$description,$price,$paid_amount,$product_type,$quantity,$size,$material,$color_mode,$lamination,$frame,$dpi,$status,$due_date,$priority]);
    $order_id = (int)$pdo->lastInsertId();

    $items=[]; $ids=$_POST['item_id']??[]; $qts=$_POST['item_qty']??[];
    for($i=0;$i<count((array)$ids);$i++){ $iid=(int)$ids[$i]; $q=(int)$qts[$i]; if($iid>0 && $q>0) $items[]=['id'=>$iid,'qty'=>$q]; }
    apply_order_items($pdo, $order_id, $items);

    if ($paid_amount>0) create_income_txn($pdo, $paid_amount, $order_id, 'Оплата при создании');

    set_flash('success','Заказ создан'); redirect('index.php?page=orders');
  }

  if ($act==='update' && can(['director','manager'])) {
    $order_id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT paid_amount FROM orders WHERE id=?");
    $st->execute([$order_id]); $old_paid = (float)($st->fetchColumn() ?: 0);

    $order_date   = $_POST['order_date'] ?: date('Y-m-d');
    $client_id    = $_POST['client_id'] ?: null;
    $description  = $_POST['description'] ?? '';
    $price        = (float)($_POST['price'] ?? 0);
    $paid_amount  = (float)($_POST['paid_amount'] ?? 0);
    $product_type = $_POST['product_type'] ?: null;
    $quantity     = (int)($_POST['quantity'] ?? 1);
    $size         = $_POST['size'] ?: null;
    $material     = $_POST['material'] ?: null;
    $color_mode   = $_POST['color_mode'] ?: null;
    $lamination   = isset($_POST['lamination']) ? 1 : 0;
    $frame        = isset($_POST['frame']) ? 1 : 0;
    $dpi          = $_POST['dpi'] ?: null;
    $status       = $_POST['status'] ?? 'in_progress';
    $due_date     = $_POST['due_date'] ?: null;
    $priority     = (int)($_POST['priority'] ?? 1);

    $pdo->prepare("UPDATE orders SET order_date=?, client_id=?, description=?, price=?, paid_amount=?, product_type=?, quantity=?, size=?, material=?, color_mode=?, lamination=?, frame=?, dpi=?, status=?, due_date=?, priority=?, updated_at=NOW()
                   WHERE id=?")
        ->execute([$order_date,$client_id,$description,$price,$paid_amount,$product_type,$quantity,$size,$material,$color_mode,$lamination,$frame,$dpi,$status,$due_date,$priority,$order_id]);

    $items=[]; $ids=$_POST['item_id']??[]; $qts=$_POST['item_qty']??[];
    for($i=0;$i<count((array)$ids);$i++){ $iid=(int)$ids[$i]; $q=(int)$qts[$i]; if($iid>0 && $q>0) $items[]=['id'=>$iid,'qty'=>$q]; }
    apply_order_items($pdo, $order_id, $items);

    $delta = $paid_amount - $old_paid;
    if ($delta > 0.00001) create_income_txn($pdo, $delta, $order_id, 'Доплата');

    set_flash('success','Заказ обновлен'); redirect('index.php?page=orders');
  }

  if ($act==='delete' && can(['director','manager'])) {
    try{
      $st=$pdo->prepare("SELECT item_id, qty FROM order_items WHERE order_id=?"); $st->execute([(int)$_POST['id']]);
      foreach($st->fetchAll() as $r){ $pdo->prepare("UPDATE inventory_items SET quantity=quantity+? WHERE id=?")->execute([(int)$r['qty'], (int)$r['item_id']]); }
    }catch(Throwable $e){}
    $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([(int)$_POST['id']]);
    set_flash('success','Заказ удален'); redirect('index.php?page=orders');
  }
}

/* список + фильтры */
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$cond = "1=1"; $params = [];
if ($search !== '') {
  // Умный поиск: #123 по ID, иначе по тексту
  if (preg_match('/^#?(\d+)$/u', $search, $m)) {
    $cond .= " AND o.id = ?"; $params[] = (int)$m[1];
  } else {
    $cond .= " AND (o.description LIKE ? OR c.name LIKE ? OR o.product_type LIKE ? OR o.material LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[]="%$search%"; $params[]="%$search%";
  }
}
if (in_array($status, ['in_progress','done','canceled'], true)) { $cond .= " AND o.status=?"; $params[] = $status; }

$sql = "SELECT o.*, c.name AS client_name
        FROM orders o
        LEFT JOIN clients c ON c.id=o.client_id
        WHERE $cond
        ORDER BY FIELD(o.status,'in_progress','done','canceled'), o.priority DESC, COALESCE(o.due_date,'9999-12-31') ASC, o.id DESC
        LIMIT 200";
$st = $pdo->prepare($sql);
try { $st->execute($params); $orders = $st->fetchAll(PDO::FETCH_ASSOC); }
catch (Throwable $e) { echo '<div class="alert error">Ошибка выборки заказов: '.e($e->getMessage()).'</div>'; $orders = []; }

$orderIds = array_column($orders,'id');
$itemsMap = fetch_order_items($pdo, $orderIds);
$clients = $pdo->query("SELECT id,name FROM clients ORDER BY name")->fetchAll();

try { $invItems = $pdo->query("SELECT id,name,sku,quantity FROM inventory_items ORDER BY name")->fetchAll(); }
catch (Throwable $e) { $invItems = []; }

/* Подсчёт итогов */
$totPrice=0; $totPaid=0; foreach($orders as $o){ $totPrice += (float)$o['price']; $totPaid += (float)$o['paid_amount']; }
$totDebt = $totPrice - $totPaid;

/* стиль: темная тема для всего раздела заказов */
?>
<style>
/* Базовая типографика — темная тема */
.orders-scope{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;font-size:14px;color:#f8fafc;background:#0f172a;padding:20px;border-radius:16px}
.orders-scope .muted{color:#94a3b8}

/* Панель заголовка */
.orders-scope .panel.glass{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:16px}
.orders-scope .panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.orders-scope .panel-header h2{margin:0;color:#f8fafc}

/* Карточки сумм — темные */
.orders-scope .sum-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.orders-scope .sum-card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:12px}
.orders-scope .sum-card b{font-size:14px;color:#cbd5e1}
.orders-scope .sum-card .val{font-weight:700;font-size:20px;color:#f8fafc}

/* Фильтры — темные */
.orders-scope .filters input,.orders-scope .filters select{border:1px solid #334155;border-radius:10px;padding:10px;background:#1e293b;color:#f8fafc}
.orders-scope .filters input::placeholder{color:#64748b}
.orders-scope .filters .btn{background:#6366f1;color:#fff;border:1px solid #6366f1;padding:10px 16px;border-radius:10px;cursor:pointer}
.orders-scope .filters .btn:hover{filter:brightness(1.1)}

/* Таблица заказов — темная */
.orders-scope .table-wrap{max-width:100%;overflow:auto;border:1px solid #334155;border-radius:12px;background:#1e293b}
.orders-scope .table-wrap .table{font-size:13.5px;width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0}
.orders-scope .table thead th{background:#0f172a;color:#e2e8f0;position:sticky;top:0;z-index:2;border-bottom:1px solid #334155}
.orders-scope .table th,.orders-scope .table td{padding:10px 12px;border-bottom:1px solid #334155;vertical-align:top;color:#f8fafc}
.orders-scope .table tbody tr:hover td{background:#334155}
.orders-scope .table tbody tr:nth-child(even) td{background:#1e293b}
.orders-scope .table tbody tr:nth-child(odd) td{background:#0f172a}

/* Ширины колонок */
.orders-scope #ordersTable thead th:nth-child(1),
.orders-scope #ordersTable tbody td:nth-child(1){width:64px;white-space:nowrap}
.orders-scope #ordersTable thead th:nth-child(2),
.orders-scope #ordersTable tbody td:nth-child(2){width:100px;white-space:nowrap}
.orders-scope #ordersTable thead th:nth-child(3),
.orders-scope #ordersTable tbody td:nth-child(3){width:160px}
.orders-scope #ordersTable thead th:nth-child(4),
.orders-scope #ordersTable tbody td:nth-child(4){width:220px}
.orders-scope #ordersTable thead th:nth-child(5),
.orders-scope #ordersTable tbody td:nth-child(5){width:200px}
.orders-scope #ordersTable thead th:nth-child(6),
.orders-scope #ordersTable tbody td:nth-child(6){width:240px}
.orders-scope #ordersTable thead th:nth-child(7),
.orders-scope #ordersTable tbody td:nth-child(7),
.orders-scope #ordersTable thead th:nth-child(8),
.orders-scope #ordersTable tbody td:nth-child(8),
.orders-scope #ordersTable thead th:nth-child(9),
.orders-scope #ordersTable tbody td:nth-child(9){width:110px;text-align:right;white-space:nowrap}
.orders-scope #ordersTable thead th:nth-child(10),
.orders-scope #ordersTable tbody td:nth-child(10){width:240px}
.orders-scope #ordersTable thead th:nth-child(11),
.orders-scope #ordersTable tbody td:nth-child(11){width:200px}
.orders-scope #ordersTable thead th:nth-child(12),
.orders-scope #ordersTable tbody td:nth-child(12){width:110px;white-space:nowrap;text-align:center}
.orders-scope #ordersTable thead th:nth-child(13),
.orders-scope #ordersTable tbody td:nth-child(13){width:190px}

.orders-scope #ordersTable td:nth-child(4),
.orders-scope #ordersTable td:nth-child(5),
.orders-scope #ordersTable td:nth-child(6){white-space:normal;overflow-wrap:anywhere}

/* Просроченные/приоритетные — темная адаптация */
.orders-scope tr.overdue td{background:#7f1d1d!important}
.orders-scope tr.overdue td:first-child{box-shadow:inset 3px 0 0 #ef4444}
.orders-scope .row-badge{display:inline-flex;align-items:center;gap:6px}
.orders-scope .status-badge{padding:5px 10px;border-radius:999px;border:1px solid var(--stroke,#475569);font-size:12px;font-weight:500}
.orders-scope .st-in_progress{background:#1e3a8a;border-color:#3b82f6;color:#93c5fd}
.orders-scope .st-done{background:#14532d;border-color:#22c55e;color:#86efac}
.orders-scope .st-canceled{background:#7f1d1d;border-color:#ef4444;color:#fca5a5}
.orders-scope .prio-badge{padding:5px 10px;border-radius:999px;border:1px solid var(--stroke,#475569);font-size:12px;font-weight:500}
.orders-scope .prio-1{background:#14532d;border-color:#22c55e;color:#86efac}
.orders-scope .prio-2{background:#78350f;border-color:#fbbf24;color:#fde047}
.orders-scope .prio-3{background:#7f1d1d;border-color:#ef4444;color:#fca5a5}
.orders-scope tr.prio-row-1 td{box-shadow:inset 3px 0 0 #22c55e}
.orders-scope tr.prio-row-2 td{box-shadow:inset 3px 0 0 #fbbf24}
.orders-scope tr.prio-row-3 td{box-shadow:inset 3px 0 0 #ef4444}
.orders-scope .select-inline{padding:6px 10px;border-radius:10px;border:1px solid #475569;background:#1e293b;color:#f8fafc;min-width:140px}
.orders-scope .select-inline:focus{outline:2px solid #6366f1;border-color:#6366f1}

/* Кнопки в строках */
.orders-scope .row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.orders-scope .btn{padding:8px 14px;border-radius:10px;cursor:pointer;font-size:13px;font-weight:500;transition:all 0.2s}
.orders-scope .btn.primary{background:#6366f1;color:#fff;border:1px solid #6366f1}
.orders-scope .btn.primary:hover{filter:brightness(1.15)}
.orders-scope .btn.ghost{background:transparent;border:1px solid transparent;color:#cbd5e1}
.orders-scope .btn.ghost:hover{background:#334155;border-color:#475569}
.orders-scope .btn.outline{background:transparent;border:1px solid #475569;color:#cbd5e1}
.orders-scope .btn.outline:hover{background:#1e293b;border-color:#64748b}
.orders-scope .btn.small{padding:6px 10px;font-size:12px}

/* Smart Order UI в списке — темная */
.orders-scope .smart-wrap{grid-column:span 2;background:#0f172a;border:1px solid #334155;border-radius:14px;padding:14px}
.orders-scope .smart-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;border-bottom:1px dashed #475569;padding-bottom:8px}
.orders-scope .smart-title{font-weight:700;color:#f8fafc}
.orders-scope .cat-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin:10px 0}
@media (max-width:1100px){.orders-scope .cat-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
.orders-scope .cat-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px;border:1px solid #475569;border-radius:12px;background:#1e293b;cursor:pointer;user-select:none;transition:.15s;color:#cbd5e1}
.orders-scope .cat-btn:hover{border-color:#6366f1;background:#334155}
.orders-scope .cat-btn.active{outline:2px solid #8b5cf6;background:#1e3a8a;color:#f8fafc}
.orders-scope .cat-ico{font-size:24px}
.orders-scope .svc-list{display:flex;flex-wrap:wrap;gap:10px;margin:10px 0}
.orders-scope .svc-chip{padding:10px 14px;border:1px solid #475569;border-radius:999px;background:#1e293b;cursor:pointer;color:#cbd5e1;transition:.15s}
.orders-scope .svc-chip:hover{background:#334155;border-color:#64748b}
.orders-scope .svc-chip.active{background:#1e3a8a;border-color:#6366f1;color:#f8fafc}
.orders-scope .param-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.orders-scope .param{display:flex;flex-direction:column;gap:8px}
.orders-scope .param label{color:#cbd5e1;font-size:13px;font-weight:500}
.orders-scope .param input,.orders-scope .param select{background:#1e293b;border:1px solid #475569;border-radius:10px;padding:10px;color:#f8fafc}
.orders-scope .param input:focus,.orders-scope .param select:focus{outline:2px solid #6366f1;border-color:#6366f1}
.orders-scope .param input::placeholder{color:#64748b}
.orders-scope .param .row{display:flex;gap:8px;align-items:center}
.orders-scope .finishing{margin-top:10px;border-top:1px dashed #475569;padding-top:10px}
.orders-scope .fin-list{display:flex;flex-wrap:wrap;gap:8px}
.orders-scope .fin-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid #475569;background:#1e293b;border-radius:10px;padding:8px 12px;cursor:pointer;color:#cbd5e1;transition:.15s}
.orders-scope .fin-chip:hover{background:#334155}
.orders-scope .fin-chip input{margin:0}
.orders-scope .hint{font-size:12px;color:#94a3b8}
.orders-scope .price-hint{display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap}
.orders-scope .price-hint .calc{background:#1e293b;border:1px dashed #475569;border-radius:10px;padding:8px 12px;color:#cbd5e1}
.orders-scope .badge-mini{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:999px;border:1px solid #475569;background:#1e293b;font-size:12px;color:#cbd5e1}
.orders-scope .debt-hint{margin-top:6px;font-size:12px;color:#94a3b8}

/* МОДАЛКА — темная тема */
.orders-scope .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.75);z-index:1000;padding:20px}
.orders-scope .modal.show{display:flex}
.orders-scope .modal .modal-content{width:100%;max-width:1100px;max-height:90vh;overflow:auto;background:#0f172a;color:#f8fafc;border-radius:16px;border:1px solid #334155;box-shadow:0 20px 60px rgba(0,0,0,.7)}
.orders-scope .modal .modal-header{position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #334155;background:#0f172a}
.orders-scope .modal h3{margin:0;color:#f8fafc}
.orders-scope .modal .form-grid{display:grid;grid-template-columns:200px 1fr;gap:12px 14px;padding:14px 16px}
.orders-scope .modal label{align-self:center;color:#cbd5e1;font-size:14px}
.orders-scope .modal input[type="text"],
.orders-scope .modal input[type="number"],
.orders-scope .modal input[type="date"],
.orders-scope .modal input[type="url"],
.orders-scope .modal select,
.orders-scope .modal textarea{background:#1e293b;color:#f8fafc;border:1px solid #475569;border-radius:10px;padding:10px 12px}
.orders-scope .modal input::placeholder,
.orders-scope .modal textarea::placeholder{color:#64748b}
.orders-scope .modal input:focus,
.orders-scope .modal select:focus,
.orders-scope .modal textarea:focus{outline:2px solid #6366f1;outline-offset:0;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.2)}
.orders-scope .modal .modal-actions{display:flex;gap:10px;align-items:center;padding:14px 16px;border-top:1px solid #334155;background:#0f172a;position:sticky;bottom:0;z-index:5}
.orders-scope .modal .btn{border-radius:10px}
.orders-scope .modal .btn.primary{background:#6366f1;color:#fff;border:1px solid #6366f1}
.orders-scope .modal .btn.primary:hover{filter:brightness(1.1)}
.orders-scope .modal .btn.outline{background:transparent;border:1px solid #475569;color:#cbd5e1}
.orders-scope .modal .btn.outline:hover{background:#1e293b;border-color:#64748b}
.orders-scope .modal .btn.ghost{background:transparent;border:1px solid transparent;color:#cbd5e1}
.orders-scope .modal .btn.ghost:hover{background:#334155}
.orders-scope .modal .debt-hint{color:#94a3b8}

/* Умный модуль внутри модалки — темные стили */
.orders-scope .modal .smart-wrap{background:#0b1120;border-color:#334155}
.orders-scope .modal .smart-head{border-bottom:1px dashed #475569;padding-bottom:8px}
.orders-scope .modal .badge-mini{background:#1e293b;border-color:#475569;color:#cbd5e1}
.orders-scope .modal .cat-btn{background:#1e293b;border-color:#475569;color:#cbd5e1}
.orders-scope .modal .cat-btn:hover{border-color:#6366f1;background:#334155}
.orders-scope .modal .cat-btn.active{outline:2px solid #8b5cf6;background:#1e3a8a;color:#f8fafc}
.orders-scope .modal .svc-chip{background:#1e293b;border-color:#475569;color:#cbd5e1}
.orders-scope .modal .svc-chip:hover{background:#334155}
.orders-scope .modal .svc-chip.active{background:#1e3a8a;border-color:#6366f1;color:#f8fafc}
.orders-scope .modal .param input,
.orders-scope .modal .param select{background:#1e293b;border-color:#475569;color:#f8fafc}
.orders-scope .modal .fin-chip{background:#1e293b;border-color:#475569;color:#cbd5e1}
.orders-scope .modal .fin-chip:hover{background:#334155}
.orders-scope .modal .price-hint .calc{background:#0b1120;border:1px dashed #475569;color:#cbd5e1}

/* Таблица позиций в модалке — темная */
.orders-scope .modal #oiTable{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #334155;border-radius:10px;overflow:hidden}
.orders-scope .modal #oiTable thead th{background:#1e293b;color:#cbd5e1;border-bottom:1px solid #334155;padding:10px}
.orders-scope .modal #oiTable td{border-bottom:1px solid #334155;padding:10px;color:#f8fafc}
.orders-scope .modal #oiTable input{background:#1e293b;border-color:#475569;color:#f8fafc}
.orders-scope .modal #oiTable tbody tr:hover{background:#334155}
</style>

<div class="orders-scope">
<div class="panel glass">
  <div class="panel-header">
    <h2>Заказы</h2>
    <?php if (can(['director','manager'])): ?>
      <button class="btn primary" data-open="#orderModal">+ Новый заказ</button>
    <?php endif; ?>
  </div>

  <form class="filters" method="get" style="display:flex;gap:10px;align-items:center;margin-bottom:16px">
    <input type="hidden" name="page" value="orders">
    <input type="text" name="q" placeholder="Поиск (#ID, описание, клиент, параметры)" value="<?=e($search)?>" style="flex:1">
    <select name="status">
      <option value="">Все статусы</option>
      <option value="in_progress" <?= $status==='in_progress'?'selected':'' ?>>В процессе</option>
      <option value="done" <?= $status==='done'?'selected':'' ?>>Завершен</option>
      <option value="canceled" <?= $status==='canceled'?'selected':'' ?>>Отменен</option>
    </select>
    <button class="btn">Фильтр</button>
  </form>

  <div class="sum-cards">
    <div class="sum-card">
      <div class="muted">Сумма заказов (видимых)</div>
      <div class="val"><?=number_format($totPrice,2,',',' ')?> ₽</div>
    </div>
    <div class="sum-card">
      <div class="muted">Оплачено</div>
      <div class="val"><?=number_format($totPaid,2,',',' ')?> ₽</div>
    </div>
    <div class="sum-card">
      <div class="muted">К оплате</div>
      <div class="val"><?=number_format($totDebt,2,',',' ')?> ₽</div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="ordersTable">
      <thead>
      <tr>
        <th>#</th><th>Дата</th><th>Клиент</th><th>Описание</th><th>Печать</th><th>Товары</th><th>Цена</th><th>Оплачено</th><th>Долг</th><th>Статус</th><th>Приоритет</th><th>Срок</th><th></th>
      </tr>
      </thead>
      <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="13" style="color:#64748b;text-align:center;padding:20px">Заказов нет</td></tr>
      <?php else: foreach ($orders as $o):
        $oi = $itemsMap[$o['id']] ?? [];
        $prio = (int)($o['priority'] ?? 1);
        $emoji = $prio===3?'😡':($prio===2?'😐':'🙂');
        $prioName = $prio===3?'Срочный':($prio===2?'Средний':'Несрочный');
        $statusBadge = 'st-'.$o['status'];
        $debt = max(0.0, (float)$o['price'] - (float)$o['paid_amount']);
        $isOverdue = !empty($o['due_date']) && $o['status']!=='done' && $o['due_date'] < date('Y-m-d');
      ?>
        <tr data-oid="<?=$o['id']?>" class="prio-row-<?=$prio?> <?=$isOverdue?'overdue':''?>">
          <td>#<?=$o['id']?></td>
          <td><?=e($o['order_date'])?></td>
          <td><?=e($o['client_name'] ?: '—')?></td>
          <td><?=e(mb_strimwidth($o['description'],0,40,'…','UTF-8'))?></td>
          <td><?=e(trim(($o['product_type']?:'').' '.($o['size']?:'').' '.($o['material']?:'')))?></td>
          <td><?php if ($oi) { foreach($oi as $x) echo e($x['name']).' × '.(int)$x['qty'].'; '; } else echo '—'; ?></td>
          <td><?=e(number_format($o['price'],2,',',' '))?> ₽</td>
          <td><?=e(number_format($o['paid_amount'],2,',',' '))?> ₽</td>
          <td><?=e(number_format($debt,2,',',' '))?> ₽</td>
          <td>
            <span class="status-badge <?=$statusBadge?>"><?= $o['status']==='in_progress'?'В процессе':($o['status']==='done'?'Завершен':($o['status']==='canceled'?'Отменен':$o['status'])) ?></span>
            <select class="select-inline js-status" style="margin-top:6px">
              <option value="in_progress" <?=$o['status']==='in_progress'?'selected':''?>>В процессе</option>
              <option value="done" <?=$o['status']==='done'?'selected':''?>>Завершен</option>
              <option value="canceled" <?=$o['status']==='canceled'?'selected':''?>>Отменен</option>
            </select>
          </td>
          <td>
            <span class="prio-badge prio-<?=$prio?>"><?=$emoji?> <?=$prioName?></span>
            <select class="select-inline js-priority" style="margin-top:6px">
              <option value="1" <?=$prio===1?'selected':''?>>🙂 Несрочный</option>
              <option value="2" <?=$prio===2?'selected':''?>>😐 Средний</option>
              <option value="3" <?=$prio===3?'selected':''?>>😡 Срочный</option>
            </select>
          </td>
          <td><?=e($o['due_date'] ?: '—')?></td>
          <td class="row-actions">
            <?php if (can(['director','manager'])):
              $data = $o; $data['items'] = $oi; ?>
              <button class="btn ghost small" data-edit='<?= e(json_encode($data, JSON_UNESCAPED_UNICODE)) ?>' data-open="#orderModal" title="Изменить">Изм.</button>
              <button class="btn outline small btn-print-check" data-oid="<?=$o['id']?>" title="Печать чека">Чек</button>
              <form method="post" onsubmit="return confirm('Удалить заказ #<?=$o['id']?>?')" style="display:inline">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$o['id']?>">
                <button class="btn outline small">Удалить</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (can(['director','manager'])): ?>
<div class="modal" id="orderModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="orderModalTitle">Новый заказ</h3>
      <button class="btn ghost" data-close>✕</button>
    </div>
    <form method="post" class="form-grid" id="orderForm">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="create" id="orderFormAction">
      <input type="hidden" name="id" id="orderId">

      <!-- УМНЫЙ МОДУЛЬ ФОРМИРОВАНИЯ ЗАКАЗА -->
      <div class="smart-wrap" id="smartWrap">
        <div class="smart-head">
          <div class="smart-title">Умный модуль: подбор услуг и параметров</div>
          <label class="badge-mini"><input type="checkbox" id="smartEnable" checked> Активен</label>
        </div>

        <!-- Категории -->
        <div class="cat-grid" id="svcCats"></div>

        <!-- Подуслуги -->
        <div class="svc-list" id="svcList"></div>

        <!-- Параметры -->
        <div class="param-grid" id="svcParams" style="margin-top:8px">
          <div class="param">
            <label>Размер</label>
            <select id="svcSizePreset"><option value="">Выберите...</option></select>
            <div class="row">
              <input type="number" id="svcW" placeholder="Ширина" min="1" step="1" style="flex:1">
              <input type="number" id="svcH" placeholder="Высота" min="1" step="1" style="flex:1">
              <select id="svcUnit" style="width:90px">
                <option value="мм">мм</option>
                <option value="см">см</option>
                <option value="м">м</option>
              </select>
            </div>
            <div class="hint">Можно выбрать пресет или задать свои ширину/высоту</div>
          </div>

          <div class="param">
            <label>Материал</label>
            <select id="svcMaterial"><option value="">—</option></select>
            <div class="row">
              <select id="svcColor">
                <option value="">Цвет</option>
                <option value="CMYK">CMYK</option>
                <option value="RGB">RGB</option>
                <option value="BW">Ч/Б</option>
              </select>
              <select id="svcSides">
                <option value="1">1 сторона</option>
                <option value="2">2 стороны</option>
              </select>
            </div>
          </div>

          <div class="param">
            <label>Тираж/Кол-во</label>
            <input type="number" id="svcQty" min="1" step="1" value="1">
            <div class="row">
              <input type="number" id="svcDpi" placeholder="DPI" min="72" step="1" value="300">
              <select id="svcPriority">
                <option value="1">🙂 Несрочно</option>
                <option value="2">😐 Средне</option>
                <option value="3">😡 Срочно</option>
              </select>
            </div>
          </div>

          <div class="param">
            <label>Файл/ссылка</label>
            <input type="url" id="fileLink" placeholder="Ссылка на макет/файлы">
            <div class="row">
              <label class="badge-mini"><input type="checkbox" id="needDesign"> Нужен дизайн</label>
              <label class="badge-mini"><input type="checkbox" id="prepress"> Предпечатная подготовка</label>
            </div>
            <div class="price-hint">
              <div class="calc" id="calcArea">Площадь: —</div>
              <div class="calc" id="calcPrice">Расчет: —</div>
              <button type="button" class="btn small outline" id="applyCalc">Применить цену</button>
            </div>
          </div>
        </div>

        <!-- Послепечатная и доп. опции -->
        <div class="finishing">
          <div class="hint" style="margin-bottom:8px">Отделка и доп. опции</div>
          <div class="fin-list">
            <label class="fin-chip"><input type="checkbox" id="finLam"> 🧴 Ламинация</label>
            <select id="lamType" class="fin-chip">
              <option value="">Тип ламинации</option>
              <option value="Матовая">Матовая</option>
              <option value="Глянцевая">Глянцевая</option>
              <option value="Soft Touch">Soft Touch</option>
              <option value="Пленка 80 мкм">Пленка 80 мкм</option>
              <option value="Пленка 125 мкм">Пленка 125 мкм</option>
            </select>
            <select id="lamSides" class="fin-chip">
              <option value="">Стороны</option>
              <option value="1-ст">1-ст</option>
              <option value="2-ст">2-ст</option>
            </select>

            <label class="fin-chip"><input type="checkbox" id="finCut"> ✂️ Резка/подрез</label>
            <label class="fin-chip"><input type="checkbox" id="finRound"> ◼️ Скругление углов</label>
            <label class="fin-chip"><input type="checkbox" id="finFolding"> ↔️ Фальцовка/биговка</label>

            <label class="fin-chip"><input type="checkbox" id="finHole"> 🕳 Перфорация/отверстия</label>
            <label class="fin-chip"><input type="checkbox" id="finGrommets"> 🪝 Люверсы</label>
            <select id="gromStep" class="fin-chip">
              <option value="">Шаг люверсов</option>
              <option value="через 20 см">через 20 см</option>
              <option value="через 30 см">через 30 см</option>
              <option value="по углам">по углам</option>
            </select>

            <label class="fin-chip"><input type="checkbox" id="finHem"> 🧵 Подгиб/шов</label>
            <label class="fin-chip"><input type="checkbox" id="finPocket"> 📎 Карманы/кант</label>
            <label class="fin-chip"><input type="checkbox" id="finMount"> 🛠 Монтаж/крепеж</label>

            <label class="fin-chip"><input type="checkbox" id="finBind"> 📚 Переплет</label>
            <select id="bindType" class="fin-chip">
              <option value="">Тип переплета</option>
              <option value="Пружина">Пружина</option>
              <option value="Скрепка (2 скобы)">Скрепка (2 скобы)</option>
              <option value="Термоклей">Термоклей</option>
            </select>

            <label class="fin-chip"><input type="checkbox" id="finUV"> 💡 УФ-печать/лак</label>
            <label class="fin-chip"><input type="checkbox" id="finFoil"> ✨ Фольгирование</label>
            <label class="fin-chip"><input type="checkbox" id="finEmboss"> 🛡 Тиснение/конгрев</label>

            <label class="fin-chip"><input type="checkbox" id="finFrame"> 🖼 Рама/подрамник</label>
          </div>
        </div>

        <div class="hint" style="margin-top:10px">Подборка параметров выше автоматически заполнит поля ниже и описание заказа. Все можно менять вручную.</div>
      </div>
      <!-- /УМНЫЙ МОДУЛЬ -->

      <!-- Базовые поля (связанные со смарт-модулем) -->
      <label>Дата заказа</label><input type="date" name="order_date" id="orderDate" value="<?=date('Y-m-d')?>">
      <label>Клиент</label>
      <select name="client_id" id="orderClient"><option value="">— не выбран —</option><?php foreach ($clients as $c): ?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach; ?></select>

      <label>Описание</label><textarea name="description" id="orderDesc" rows="3" placeholder="Доп. детали заказа"></textarea>

      <label>Тип печати</label>
      <select name="product_type" id="orderProduct">
        <option value="">—</option><option>Фото</option><option>Холст</option><option>Постер</option><option>Визитки</option><option>Буклет</option>
      </select>

      <label>Размер</label>
      <select name="size" id="orderSize">
        <option value="">—</option><option>10x15</option><option>15x21</option><option>A4</option><option>A3</option><option>30x40</option><option>50x70</option>
      </select>
      <label>Материал</label>
      <select name="material" id="orderMaterial">
        <option value="">—</option><option>Глянец</option><option>Матовая</option><option>Холст</option><option>Плотная бумага 200г</option>
      </select>

      <label>Цвет</label><select name="color_mode" id="orderColor"><option value="">—</option><option>CMYK</option><option>RGB</option><option>BW</option></select>
      <label>Тираж</label><input type="number" name="quantity" id="orderQty" value="1" min="1" step="1">

      <label>Ламинация</label><input type="checkbox" name="lamination" id="orderLam">
      <label>Рама</label><input type="checkbox" name="frame" id="orderFrame">

      <label>DPI</label><input type="number" name="dpi" id="orderDpi" value="300" min="72" step="1">

      <label>Цена</label>
      <div>
        <input type="number" step="0.01" name="price" id="orderPrice" value="0">
        <div class="debt-hint muted" id="debtHint">К оплате: 0 ₽</div>
      </div>
      <label>Оплачено</label><input type="number" step="0.01" name="paid_amount" id="orderPaid" value="0">

      <label>Статус</label><select name="status" id="orderStatus"><option value="in_progress">В процессе</option><option value="done">Завершен</option><option value="canceled">Отменен</option></select>
      <label>Срок выполнения</label><input type="date" name="due_date" id="orderDue">

      <label>Приоритет</label><select name="priority" id="orderPriority"><option value="1">🙂 Несрочный</option><option value="2">😐 Средний</option><option value="3">😡 Срочный</option></select>

      <?php if ($invItems): ?>
      <div class="modal-actions" style="grid-column: span 2; justify-content:flex-start; gap:10px">
        <select id="oiItem" style="max-width:420px">
          <option value="">— выбрать товар —</option>
          <?php foreach($invItems as $i): ?>
            <option value="<?=$i['id']?>"><?=e($i['name'])?> <?= $i['sku']?('· '.e($i['sku'])):'' ?> (ост: <?=$i['quantity']?>)</option>
          <?php endforeach; ?>
        </select>
        <input type="number" id="oiQty" value="1" min="1" style="max-width:120px">
        <button type="button" class="btn outline" id="oiAdd">Добавить позицию</button>
      </div>
      <div style="grid-column: span 2">
        <table class="table" id="oiTable"><thead><tr><th>Товар</th><th>Кол-во</th><th></th></tr></thead><tbody></tbody></table>
      </div>
      <?php endif; ?>

      <div class="modal-actions" style="gap:10px">
        <button class="btn outline" type="button" id="btnPrintOrder">Печать бланка заказа</button>
        <button class="btn primary" id="btnSaveOrder">Сохранить</button>
      </div>
    </form>
  </div>
</div>
</div>

<script>
// Обновление подсказки долга
function updateDebtHint(){
  const p = parseFloat(document.getElementById('orderPrice')?.value || 0);
  const pa = parseFloat(document.getElementById('orderPaid')?.value || 0);
  const debt = Math.max(0, p - pa);
  const hint = document.getElementById('debtHint');
  if (hint) hint.textContent = 'К оплате: ' + debt.toFixed(2) + ' ₽';
}
document.getElementById('orderPrice')?.addEventListener('input', updateDebtHint);
document.getElementById('orderPaid')?.addEventListener('input', updateDebtHint);

// Конфигурация каталога услуг типографии с пиктограммами (все эмодзи сохраняем)
const PRINT_CATALOG = [
  {
    id:'photo', name:'Фотопечать', icon:'🖼️',
    services:[
      {id:'photo_print', name:'Печать фото', sizes:['10x15','13x18','15x21','A4','A3'], materials:['Глянец','Матовая','Люстер'], color:['RGB','BW'], defaults:{dpi:300}},
      {id:'canvas', name:'Печать на холсте', sizes:['30x40','40x60','50x70','60x90'], materials:['Холст','Холст премиум'], extras:['frame']}
    ]
  },
  {
    id:'polygraphy', name:'Полиграфия', icon:'🧾',
    services:[
      {id:'business_cards', name:'Визитки', sizes:['90x50','85x55','EU 85x55'], materials:['Картон 300г','Мелованная 300г','Крафт 300г'], color:['CMYK','BW'], finishing:['lam','round']},
      {id:'flyers', name:'Листовки', sizes:['A6','A5','A4','A3'], materials:['Мелованная 130г','Мелованная 170г','Офсет 80г'], color:['CMYK','BW']},
      {id:'brochure', name:'Буклет/Брошюра', sizes:['A5','A4'], materials:['Мелованная 130г','Мелованная 170г'], finishing:['fold','bind']}
    ]
  },
  {
    id:'large', name:'Широкоформат', icon:'🖨️',
    services:[
      {id:'banner', name:'Баннер', sizes:['100x100','200x100','300x150'], materials:['Баннер 440г','Баннер 510г','Сетка виниловая'], finishing:['grommets','hem','pocket','cut']},
      {id:'poster', name:'Постер', sizes:['A2','A1','A0','50x70','70x100'], materials:['Постерная бумага 200г','Сатин 190г'], finishing:['lam']},
      {id:'sticker', name:'Наклейки', sizes:['A4','A3','100x100','Кастом'], materials:['Пленка глянцевая','Пленка матовая','Оракал'], finishing:['cut']}
    ]
  },
  {
    id:'interior', name:'Интерьерная', icon:'🧩',
    services:[
      {id:'foamboard', name:'Печать на пенокартоне', sizes:['A3','A2','A1','A0'], materials:['Пенокартон 5мм','Пенокартон 10мм'], finishing:['cut','mount']},
      {id:'plastic', name:'Печать на пластике', sizes:['A3','A2','A1'], materials:['ПВХ 3мм','ПВХ 5мм','Акрил 3мм'], finishing:['cut','mount']}
    ]
  },
  {
    id:'souvenirs', name:'Сувениры', icon:'🎁',
    services:[
      {id:'mug', name:'Кружки', sizes:['Стандарт'], materials:['Белая','Цветная'], color:['CMYK'], extras:[]},
      {id:'tshirt', name:'Футболки', sizes:['S','M','L','XL'], materials:['Белая хлопок','Черная хлопок'], color:['CMYK']}
    ]
  },
  {
    id:'postpress', name:'Отделка', icon:'🛠️',
    services:[
      {id:'lamination', name:'Ламинация', sizes:['A6','A5','A4','A3','A2'], materials:['Пленка 80 мкм','Пленка 125 мкм']},
      {id:'binding', name:'Переплет', sizes:['A4','A5'], materials:['Пружина','Термоклей','Скоба']}
    ]
  }
];

(function SmartOrder(){
  const wrap = document.getElementById('smartWrap');
  if (!wrap) return;

  // DOM refs
  const cats = document.getElementById('svcCats');
  const list = document.getElementById('svcList');
  const sizePreset = document.getElementById('svcSizePreset');
  const svcW = document.getElementById('svcW');
  const svcH = document.getElementById('svcH');
  const svcUnit = document.getElementById('svcUnit');
  const matSel = document.getElementById('svcMaterial');
  const colorSel = document.getElementById('svcColor');
  const sidesSel = document.getElementById('svcSides');
  const qtyInp = document.getElementById('svcQty');
  const dpiInp = document.getElementById('svcDpi');
  const prioSel = document.getElementById('svcPriority');
  const fileLink = document.getElementById('fileLink');
  const chkDesign = document.getElementById('needDesign');
  const chkPrepress = document.getElementById('prepress');

  // finishing
  const finLam = document.getElementById('finLam');
  const lamType = document.getElementById('lamType');
  const lamSides = document.getElementById('lamSides');
  const finCut = document.getElementById('finCut');
  const finRound = document.getElementById('finRound');
  const finFolding = document.getElementById('finFolding');
  const finHole = document.getElementById('finHole');
  const finGrommets = document.getElementById('finGrommets');
  const gromStep = document.getElementById('gromStep');
  const finHem = document.getElementById('finHem');
  const finPocket = document.getElementById('finPocket');
  const finMount = document.getElementById('finMount');
  const finBind = document.getElementById('finBind');
  const bindType = document.getElementById('bindType');
  const finUV = document.getElementById('finUV');
  const finFoil = document.getElementById('finFoil');
  const finEmboss = document.getElementById('finEmboss');
  const finFrame = document.getElementById('finFrame');

  // base form refs
  const f = document.getElementById('orderForm');
  const enable = document.getElementById('smartEnable');
  const fldProduct = document.getElementById('orderProduct');
  const fldSize = document.getElementById('orderSize');
  const fldMaterial = document.getElementById('orderMaterial');
  const fldColor = document.getElementById('orderColor');
  const fldQty = document.getElementById('orderQty');
  const fldLam = document.getElementById('orderLam');
  const fldFrame = document.getElementById('orderFrame');
  const fldDpi = document.getElementById('orderDpi');
  const fldPriority = document.getElementById('orderPriority');
  const fldPrice = document.getElementById('orderPrice');
  const fldDesc = document.getElementById('orderDesc');
  const fldDue = document.getElementById('orderDue');

  const calcArea = document.getElementById('calcArea');
  const calcPrice = document.getElementById('calcPrice');
  const applyCalcBtn = document.getElementById('applyCalc');

  let selectedCat = null;
  let selectedSvc = null;
  let autoDueSet = false; // чтобы не перетирать вручную установленный срок

  function renderCats(){
    cats.innerHTML = '';
    PRINT_CATALOG.forEach(c=>{
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'cat-btn';
      el.dataset.id = c.id;
      el.innerHTML = `<div class="cat-ico">${c.icon}</div><div>${c.name}</div>`;
      el.addEventListener('click', ()=>{
        selectedCat = c;
        document.querySelectorAll('.cat-btn').forEach(x=>x.classList.toggle('active', x===el));
        renderServices(c);
      });
      cats.appendChild(el);
    });
  }

  function renderServices(cat){
    list.innerHTML = '';
    (cat.services||[]).forEach(s=>{
      const chip = document.createElement('button');
      chip.type='button'; chip.className='svc-chip'; chip.textContent=s.name; chip.dataset.id=s.id;
      chip.addEventListener('click', ()=>{
        selectedSvc = s;
        document.querySelectorAll('.svc-chip').forEach(x=>x.classList.toggle('active', x===chip));
        applyServicePreset(s);
      });
      list.appendChild(chip);
    });
    // авто-выбор первой услуги
    if (cat.services?.length){ list.querySelector('.svc-chip')?.click(); }
  }

  function fillSelectOptions(sel, arr, emptyLabel='—'){
    const v = sel.value;
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = ''; opt0.textContent = emptyLabel;
    sel.appendChild(opt0);
    (arr||[]).forEach(x=>{
      const o = document.createElement('option');
      o.value = x; o.textContent = x;
      sel.appendChild(o);
    });
    if ([...sel.options].some(o=>o.value===v)) sel.value=v;
  }

  function applyServicePreset(svc){
    // Пресеты
    fillSelectOptions(sizePreset, svc.sizes || [], 'Размер (пресет)');
    fillSelectOptions(matSel, svc.materials || [], 'Материал');
    // цвет
    if (svc.color?.length){
      fillSelectOptions(colorSel, svc.color, 'Цвет');
    } else {
      fillSelectOptions(colorSel, ['CMYK','RGB','BW'], 'Цвет');
    }
    // дефолты
    dpiInp.value = svc.defaults?.dpi || 300;
    qtyInp.value = 1;
    sidesSel.value = '1';
    svcW.value = ''; svcH.value = ''; svcUnit.value='мм';

    // сброс отделки/флагов
    [finLam, finCut, finRound, finFolding, finHole, finGrommets, finHem, finPocket, finMount, finBind, finUV, finFoil, finEmboss, finFrame].forEach(x=>x.checked=false);
    lamType.value=''; lamSides.value=''; gromStep.value=''; bindType.value='';

    // заполним базовые поля формы
    fldProduct.value = svc.name;
    syncFieldsFromSmart();
    updateCalculations();
  }

  function syncFieldsFromSmart(){
    if (!enable.checked) return;
    // Размер
    let sizeStr = '';
    const p = sizePreset.value;
    if (p) sizeStr = p;
    if (svcW.value && svcH.value){
      sizeStr = `${svcW.value}x${svcH.value} ${svcUnit.value}`;
    }
    if (sizeStr){
      setSelectValueOrAppend(fldSize, sizeStr);
      fldSize.value = sizeStr;
    }
    // Материал
    if (matSel.value){
      setSelectValueOrAppend(fldMaterial, matSel.value);
      fldMaterial.value = matSel.value;
    }
    // Цвет
    if (colorSel.value){
      setSelectValueOrAppend(fldColor, colorSel.value);
      fldColor.value = colorSel.value;
    }
    // Тираж
    if (qtyInp.value) fldQty.value = qtyInp.value;
    // DPI
    if (dpiInp.value) fldDpi.value = dpiInp.value;
    // Приоритет
    if (prioSel.value) {
      fldPriority.value = prioSel.value;
      suggestDueByPriority(+prioSel.value);
    }
    // Ламинация/Рама
    fldLam.checked = finLam.checked || !!lamType.value || !!lamSides.value;
    fldFrame.checked = finFrame.checked;
    // Продукт-тип уже установлен при выборе услуги
    if (selectedSvc?.name) {
      setSelectValueOrAppend(fldProduct, selectedSvc.name);
      fldProduct.value = selectedSvc.name;
    }
  }

  function setSelectValueOrAppend(sel, value){
    if (![...sel.options].some(o=>o.value===value)){
      const o = document.createElement('option'); o.value=value; o.textContent=value; sel.appendChild(o);
    }
  }

  function mmToMeters(n, unit){
    const v = parseFloat(n||0);
    if (!v) return 0;
    switch(unit){
      case 'м': return v;
      case 'см': return v/100;
      default: return v/1000; // мм
    }
  }

  function materialCoeff(name){
    const map = {
      'Баннер 510г':1.2,'Баннер 440г':1.0,'Сетка виниловая':1.15,
      'Постерная бумага 200г':1.0,'Сатин 190г':1.1,
      'Пленка глянцевая':1.1,'Пленка матовая':1.15,'Оракал':1.3,
      'Пенокартон 5мм':1.2,'Пенокартон 10мм':1.35,
      'ПВХ 3мм':1.25,'ПВХ 5мм':1.35,'Акрил 3мм':1.6,
      'Холст':1.3,'Холст премиум':1.5,
      'Глянец':1.0,'Матовая':1.0,'Люстер':1.1
    };
    return map[name] || 1.0;
  }

  function updateCalculations(){
    // площадь
    let area = 0;
    if (svcW.value && svcH.value){
      const w = mmToMeters(svcW.value, svcUnit.value);
      const h = mmToMeters(svcH.value, svcUnit.value);
      area = +(w*h).toFixed(3);
    }
    calcArea.textContent = 'Площадь: ' + (area ? (area+' м²') : '—');

    // базовые цены (примерные)
    const BASE = {
      banner: 1150, poster: 600, sticker: 400, foamboard: 900, plastic: 1200, canvas: 950,
      photo_print: 300, business_cards: 2.5, flyers: 5, brochure: 120 // за единицу (лист/шт)
    };
    let quote = 0;
    const svcId = selectedSvc?.id || '';
    const qty = parseFloat(qtyInp.value||1);
    const mat = matSel.value;

    if (['banner','poster','sticker','foamboard','plastic','canvas'].includes(svcId)){
      const base = BASE[svcId] || 500;
      let k = materialCoeff(mat);
      let s = area * base * k;
      // Стороны
      if (sidesSel.value === '2') s *= 1.6;
      // Отделка
      if (finLam.checked || lamType.value){ s *= (lamSides.value==='2-ст'?1.25:1.15); }
      if (finGrommets.checked){ s += (area>0.5?200:100); }
      if (finHem.checked) s *= 1.05;
      if (finPocket.checked) s *= 1.07;
      if (finMount.checked) s += 300;
      if (finCut.checked) s *= 1.04;

      // Срочность
      if (prioSel.value==='3'){ s *= 1.15; } else if (prioSel.value==='2'){ s *= 1.05; }

      quote = s;
    } else if (['photo_print','business_cards','flyers','brochure'].includes(svcId)){
      const base = BASE[svcId] || 10;
      let s = base * qty;
      if (finLam.checked) s *= 1.1;
      if (finRound.checked) s += 0.2 * qty;
      if (finFolding.checked) s += 0.4 * qty;
      if (finBind.checked) s += 100 + 0.5 * qty;
      if (prioSel.value==='3'){ s *= 1.15; } else if (prioSel.value==='2'){ s *= 1.05; }
      quote = s;
    }

    quote = Math.max(0, Math.round(quote));
    calcPrice.textContent = 'Расчет: ' + (quote ? (quote.toLocaleString('ru-RU') + ' ₽') : '—');
    applyCalcBtn.onclick = ()=>{ if (quote) fldPrice.value = quote; updateDebtHint(); };
  }

  function buildSmartDescription(){
    const lines = [];
    if (selectedCat?.name || selectedSvc?.name){
      lines.push(`Услуга: ${selectedCat?.name||''}${selectedSvc?.name?(' • '+selectedSvc.name):''}`);
    }
    const sizeParts = [];
    if (sizePreset.value) sizeParts.push(sizePreset.value);
    if (svcW.value && svcH.value) sizeParts.push(`${svcW.value}×${svcH.value} ${svcUnit.value}`);
    const sizeText = sizeParts.join(' / ');
    if (sizeText) lines.push(`Размер: ${sizeText}`);

    if (matSel.value) lines.push(`Материал: ${matSel.value}`);
    if (colorSel.value) lines.push(`Цвет: ${colorSel.value}`);
    if (sidesSel.value) lines.push(`Стороны: ${sidesSel.value}`);
    if (qtyInp.value) lines.push(`Тираж: ${qtyInp.value} шт.`);
    if (dpiInp.value) lines.push(`DPI: ${dpiInp.value}`);

    const fin = [];
    if (finLam.checked || lamType.value || lamSides.value){
      fin.push(`Ламинация${lamType.value?(' '+lamType.value):''}${lamSides.value?(' '+lamSides.value):''}`);
    }
    if (finCut.checked) fin.push('Резка/подрез');
    if (finRound.checked) fin.push('Скругление углов');
    if (finFolding.checked) fin.push('Фальцовка/биговка');
    if (finHole.checked) fin.push('Перфорация');
    if (finGrommets.checked) fin.push('Люверсы' + (gromStep.value?(' ('+gromStep.value+')'):''));
    if (finHem.checked) fin.push('Подгиб/шов');
    if (finPocket.checked) fin.push('Карманы/кант');
    if (finMount.checked) fin.push('Монтаж/крепеж');
    if (finBind.checked) fin.push('Переплет' + (bindType.value?(' ('+bindType.value+')'):''));
    if (finUV.checked) fin.push('УФ-лак/печать');
    if (finFoil.checked) fin.push('Фольгирование');
    if (finEmboss.checked) fin.push('Тиснение/конгрев');
    if (finFrame.checked) fin.push('Рама/подрамник');
    if (fin.length) lines.push('Отделка: ' + fin.join(', '));

    const svc = [];
    if (chkDesign.checked) svc.push('Дизайн');
    if (chkPrepress.checked) svc.push('Предпечатная подготовка');
    if (fileLink.value) svc.push('Файлы: ' + fileLink.value);
    if (svc.length) lines.push(svc.join(' • '));

    return lines.join('\n');
  }

  function suggestDueByPriority(p){
    if (!fldDue) return;
    const today = new Date();
    const addDays = p===3?1:(p===2?3:7);
    if (!fldDue.value || autoDueSet){
      const d = new Date(today.getFullYear(),today.getMonth(),today.getDate()+addDays);
      fldDue.value = d.toISOString().slice(0,10);
      autoDueSet = true;
    }
  }

  // Синхронизации
  [sizePreset, svcW, svcH, svcUnit, matSel, colorSel, sidesSel, qtyInp, dpiInp, prioSel,
   finLam, lamType, lamSides, finCut, finRound, finFolding, finHole, finGrommets, gromStep,
   finHem, finPocket, finMount, finBind, bindType, finUV, finFoil, finEmboss, finFrame
  ].forEach(el=>{
    el.addEventListener('change', ()=>{
      syncFieldsFromSmart();
      updateCalculations();
    });
  });
  [svcW, svcH, qtyInp, dpiInp].forEach(el=> el.addEventListener('input', updateCalculations));
  [chkDesign, chkPrepress, fileLink].forEach(el=> el.addEventListener('input', ()=>{}));

  // Перед отправкой формы — запишем смарт-описание
  f.addEventListener('submit', ()=>{
    if (!enable.checked) return;
    const autoDesc = buildSmartDescription();
    if (autoDesc){
      const manualNote = (fldDesc.value||'').trim();
      fldDesc.value = autoDesc + (manualNote ? '\nПримечание: ' + manualNote : '');
    }
    syncFieldsFromSmart();
  });

  // При открытии модалки "Новый заказ" установим дефолт
  function initDefaults(){ cats.querySelector('.cat-btn')?.click(); }

  // При редактировании — подставим существующие значения в смарт
  window.SmartOrderSetFromExisting = function(data){
    try{
      if (data.product_type){
        for (const c of PRINT_CATALOG){
          const svc = (c.services||[]).find(s=> s.name===data.product_type);
          if (svc){
            const btn = cats.querySelector(`.cat-btn[data-id="${c.id}"]`); btn?.click();
            [...list.querySelectorAll('.svc-chip')].forEach(el=>{
              if (el.textContent===svc.name) el.click();
            });
            break;
          }
        }
      }
      if (data.size){
        sizePreset.value = [...sizePreset.options].some(o=>o.value===data.size) ? data.size : '';
        if (!sizePreset.value){
          const m = String(data.size).match(/(\d+(?:[\.,]\d+)?)\s*[xх]\s*(\d+(?:[\.,]\d+)?)(?:\s*(мм|см|м))?/i);
          if (m){ svcW.value=m[1].replace(',','.'); svcH.value=m[2].replace(',','.'); if (m[3]) svcUnit.value=m[3]; }
        }
      }
      if (data.material){ setSelectValueOrAppend(matSel, data.material); matSel.value=data.material; }
      if (data.color_mode){ colorSel.value=data.color_mode; }
      qtyInp.value = data.quantity||1;
      dpiInp.value = data.dpi||300;
      prioSel.value = (data.priority||1);
      finLam.checked = (data.lamination==1);
      finFrame.checked = (data.frame==1);

      syncFieldsFromSmart();
      updateCalculations();
    }catch(e){}
  };

  renderCats();
  initDefaults();
})();

// позиции
(function(){
  const t=document.querySelector('#oiTable tbody'); const add=document.getElementById('oiAdd'); const sel=document.getElementById('oiItem'); const qty=document.getElementById('oiQty');
  function addRow(id,name,count){ if(!t) return; const tr=document.createElement('tr'); tr.innerHTML=`<td>${name}<input type="hidden" name="item_id[]" value="${id}"></td>
    <td style="width:140px"><input type="number" name="item_qty[]" value="${count}" min="1" style="width:120px;background:#1e293b;color:#f8fafc;border:1px solid #475569;padding:8px;border-radius:10px"></td>
    <td style="width:80px"><button type="button" class="btn small outline oi-del">Убрать</button></td>`; t.appendChild(tr); }
  if(add) add.addEventListener('click',()=>{ const id=parseInt(sel.value||0,10); const name=sel.options[sel.selectedIndex]?.text||''; const q=parseInt(qty.value||1,10); if(!id||q<1) return; addRow(id,name,q); });
  if(t) t.addEventListener('click',(e)=>{ if(e.target.classList.contains('oi-del')) e.target.closest('tr').remove(); });

  // редактирование
  document.querySelectorAll('[data-edit]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const data=JSON.parse(btn.getAttribute('data-edit')); if(!t) return; t.innerHTML='';
      (data.items||[]).forEach(x=> addRow(x.id, (x.name||('ID '+x.id)), x.qty));
      const el=document.getElementById('orderForm');
      el.querySelector('#orderFormAction').value='update';
      document.getElementById('orderModalTitle').textContent='Изменение заказа #'+(data.id||'');
      el.querySelector('#orderId').value=data.id||'';
      el.querySelector('#orderDate').value=data.order_date||'';
      el.querySelector('#orderClient').value=data.client_id||'';
      el.querySelector('#orderDesc').value=data.description||'';
      el.querySelector('#orderProduct').value=data.product_type||'';
      el.querySelector('#orderSize').value=data.size||'';
      el.querySelector('#orderMaterial').value=data.material||'';
      el.querySelector('#orderColor').value=data.color_mode||'';
      el.querySelector('#orderQty').value=data.quantity||1;
      el.querySelector('#orderLam').checked=(data.lamination==1);
      el.querySelector('#orderFrame').checked=(data.frame==1);
      el.querySelector('#orderDpi').value=data.dpi||300;
      el.querySelector('#orderPrice').value=data.price||0;
      el.querySelector('#orderPaid').value=data.paid_amount||0;
      el.querySelector('#orderStatus').value=data.status||'in_progress';
      el.querySelector('#orderDue').value=data.due_date||'';
      document.getElementById('orderPriority').value = data.priority||1;

      // подставим в умный модуль
      window.SmartOrderSetFromExisting?.(data);

      // обновим подсказку "к оплате"
      updateDebtHint();
    });
  });

  // "Новый заказ" — сброс формы под создание
  document.querySelectorAll('[data-open="#orderModal"]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const act = document.getElementById('orderFormAction');
      if (act && !btn.hasAttribute('data-edit')) {
        act.value='create';
        document.getElementById('orderModalTitle').textContent='Новый заказ';
        document.getElementById('orderForm').reset();
        if(t) t.innerHTML='';
        // сброс долга
        updateDebtHint();
      }
    });
  });
})();

// быстрые статусы/приоритеты
(function(){
  const tbl=document.getElementById('ordersTable');
  tbl?.addEventListener('change', async (e)=>{
    const tr=e.target.closest('tr[data-oid]'); if(!tr) return;
    const id=tr.getAttribute('data-oid');
    const status=tr.querySelector('.js-status')?.value||'in_progress';
    const priority=tr.querySelector('.js-priority')?.value||'1';
    const fd = new URLSearchParams({ csrf: window.CSRF, id, status, priority, scope:'orders' });
    const r = await fetch('api/order_status.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: fd });
    const j = await r.json();
    if (!j.ok){ alert('Ошибка: '+(j.error||'unknown')); return; }
    // обновим бейджи/цвет
    const pr=parseInt(priority,10);
    tr.classList.remove('prio-row-1','prio-row-2','prio-row-3');
    tr.classList.add('prio-row-'+pr);
    const prBadge=tr.querySelector('.prio-badge'); if(prBadge){
      prBadge.className='prio-badge prio-'+pr;
      prBadge.textContent = (pr===3?'😡 Срочный':(pr===2?'😐 Средний':'🙂 Несрочный'));
    }
    const stBadge=tr.querySelector('.status-badge'); if(stBadge){
      stBadge.className='status-badge st-'+status;
      stBadge.textContent = status==='in_progress'?'В процессе':(status==='done'?'Завершен':(status==='canceled'?'Отменен':status));
    }
  });
})();

// печать чека
(function(){
  function printHTML(html){
    const iframe = document.createElement('iframe');
    iframe.style.position='fixed'; iframe.style.left='-9999px'; iframe.style.top='-9999px'; iframe.style.width='0'; iframe.style.height='0'; iframe.style.opacity='0';
    document.body.appendChild(iframe);
    iframe.srcdoc = html;
    iframe.onload = function(){ try{ iframe.contentWindow.focus(); iframe.contentWindow.print(); }catch(e){} setTimeout(()=>iframe.remove(), 4000); };
  }
  document.querySelectorAll('.btn-print-check').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const oid = btn.getAttribute('data-oid'); btn.disabled=true;
      try{
        const r = await fetch('api/check_from_order.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: new URLSearchParams({ csrf: window.CSRF, order_id: oid }) });
        const j = await r.json(); if (j.ok && j.html){ printHTML(j.html); } else alert('Не удалось сформировать чек: '+(j.error||'unknown'));
      }catch(e){ alert('Сеть/сервер: '+e); } finally{ btn.disabled=false; }
    });
  });
})();

// печать бланка заказа
(function(){
  const form = document.getElementById('orderForm');
  const COMPANY = <?= json_encode([
    'name'=>setting('org_name', setting('site_name','Компания')),
    'logo'=>setting('logo_path','/public/images/logo.png'),
    'inn'=>setting('org_inn',''),'kpp'=>setting('org_kpp',''),'addr'=>setting('org_address',''),'phone'=>setting('org_phone','')
  ], JSON_UNESCAPED_UNICODE) ?>;
  function money(n){ return new Intl.NumberFormat('ru-RU',{minimumFractionDigits:2, maximumFractionDigits:2}).format(+n||0); }
  function val(sel){ const el=form.querySelector(sel); if(!el) return ''; if(el.type==='checkbox') return el.checked?'Да':'Нет'; return el.value||''; }
  function selText(sel){ const el=form.querySelector(sel); if(!el) return ''; return el.tagName==='SELECT'?(el.selectedOptions[0]?.text||''):el.value||''; }
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function collectItems(){ const t=document.querySelector('#oiTable tbody'); if(!t) return []; const items=[]; t.querySelectorAll('tr').forEach(tr=>{ const name=(tr.cells[0]?.textContent||'').trim(); const qty=parseFloat(tr.querySelector('[name="item_qty[]"]')?.value||'0'); if(name) items.push({title:name, qty:qty}); }); return items; }
  function buildPrintHTML(){
    const date=val('#orderDate')||new Date().toISOString().slice(0,10);
    const client=document.getElementById('orderClient')?.selectedOptions[0]?.text||'—';
    const status=selText('#orderStatus')||'—'; const due=val('#orderDue')||'—';
    const product=selText('#orderProduct')||'—', size=selText('#orderSize')||'—', mat=selText('#orderMaterial')||'—', color=selText('#orderColor')||'—';
    const qty=val('#orderQty')||'—', lam=val('#orderLam'), frame=val('#orderFrame'), dpi=val('#orderDpi')||'—';
    const price=parseFloat(val('#orderPrice')||'0'), paid=parseFloat(val('#orderPaid')||'0');
    const desc=escapeHtml(val('#orderDesc')).replace(/\n/g,'<br>');
    const items=collectItems();
    const rows=(items.length?items.map((it,i)=>`<tr><td class="c">${i+1}</td><td>${escapeHtml(it.title)}</td><td class="c">${it.qty||''}</td></tr>`).join(''):'<tr><td class="c">—</td><td>Позиции не добавлены</td><td class="c">—</td></tr>');
    return `<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Бланк заказа</title>
    <style>@page{size:A4;margin:14mm} body{font-family:Inter,system-ui,Segoe UI,Arial;color:#101626;background:#fff}
    .wrap{border:2px solid #e1e6f6;border-radius:14px;padding:14px}.head{display:flex;gap:12px;align-items:center;margin-bottom:8px}
    .logo{width:64px;height:64px;border-radius:12px;border:1px solid #e7eaf5;padding:6px;object-fit:contain} h1{margin:0;font-size:20px}
    .mut{color:#5e6a86;font-size:12px}.title2{margin:4px 0 0;color:#845ef7;font-weight:700}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0}
    table{width:100%;border-collapse:collapse;margin-top:6px;border:1px solid #e7eaf5;border-radius:10px;overflow:hidden}
    th,td{padding:8px;border-bottom:1px solid #e7eaf5} thead th{background:#f6f7fb;text-align:left}
    .c{text-align:center}.r{text-align:right}.big{font-size:15px}
    .sum{margin-top:8px;display:grid;grid-template-columns:1fr 180px;gap:8px}
    .sum div{padding:6px 10px;border:1px solid #e7eaf5;border-radius:10px;background:#fafbff}
    .sig{display:flex;justify-content:space-between;margin-top:18px}.sig div{width:48%}.line{border-bottom:1px solid #8893b2;height:26px}</style></head><body>
    <div class="wrap">
      <div class="head">
        <img class="logo" src="${COMPANY.logo||''}" alt="logo" onerror="this.style.display='none'">
        <div><h1 class="big">${COMPANY.name||'Компания'}</h1><div class="title2">Бланк сформирован CRM от разработки компании ПРИНТСС</div><div class="mut">ИНН: ${COMPANY.inn||'—'} ${COMPANY.kpp?('· КПП: '+COMPANY.kpp):''} • ${COMPANY.addr||''} ${COMPANY.phone?(' • '+COMPANY.phone):''}</div></div>
      </div>
      <div class="grid big"><div><b>Дата:</b> ${date}</div><div><b>Статус:</b> ${escapeHtml(status)}</div><div><b>Клиент:</b> ${escapeHtml(client)}</div><div><b>Срок:</b> ${escapeHtml(due)}</div></div>
      <div class="grid"><div><b>Тип печати:</b> ${escapeHtml(product)}</div><div><b>Размер:</b> ${escapeHtml(size)}</div><div><b>Материал:</b> ${escapeHtml(mat)}</div><div><b>Цвет:</b> ${escapeHtml(color)}</div><div><b>Тираж:</b> ${escapeHtml(qty)}</div><div><b>Ламинация:</b> ${escapeHtml(lam)}</div><div><b>Рама:</b> ${escapeHtml(frame)}</div><div><b>DPI:</b> ${escapeHtml(dpi)}</div></div>
      <table><thead><tr><th style="width:48px">#</th><th>Позиция/товар</th><th style="width:90px" class="c">Кол-во</th></tr></thead><tbody>${rows}</tbody></table>
      <div class="sum big"><div class="r"><b>Итого:</b></div><div class="r"><b>${money(price)}</b> ₽</div><div class="r">Оплачено:</div><div class="r">${money(paid)} ₽</div><div class="r">К оплате:</div><div class="r">${money(price - paid)} ₽</div></div>
      <div style="margin-top:10px" class="big"><b>Описание:</b><br>${desc || '—'}</div>
      <div class="sig"><div>Исполнитель: <div class="line"></div></div><div>Подпись клиента: <div class="line"></div></div></div>
    </div><script>window.onload=function(){try{window.focus();window.print();}catch(e){}}<\/script></body></html>`;
  }
  function doPrintInIframe(){
    const html = buildPrintHTML();
    const iframe = document.createElement('iframe');
    iframe.style.position='fixed'; iframe.style.left='-9999px'; iframe.style.top='-9999px'; iframe.style.width='0'; iframe.style.height='0'; iframe.style.opacity='0';
    document.body.appendChild(iframe); iframe.srcdoc=html;
    iframe.onload=function(){ try{ iframe.contentWindow.focus(); iframe.contentWindow.print(); }catch(e){} setTimeout(()=>iframe.remove(),4000); };
  }
  document.getElementById('btnPrintOrder')?.addEventListener('click', (e)=>{ e.preventDefault(); doPrintInIframe(); });

  // горячие клавиши: Ctrl+Enter — сохранить, Ctrl+P — печать
  document.addEventListener('keydown',(e)=>{
    if (document.getElementById('orderModal')?.classList.contains('show')){
      if (e.ctrlKey && e.key==='Enter'){ e.preventDefault(); document.getElementById('btnSaveOrder')?.click(); }
      if (e.ctrlKey && (e.key==='p' || e.key==='P')){ e.preventDefault(); document.getElementById('btnPrintOrder')?.click(); }
    }
  });

  // модалка open/close
  document.addEventListener('click',(e)=>{
    const o=e.target.closest('[data-open]'); if(o){ const m=document.querySelector(o.getAttribute('data-open')); m?.classList.add('show'); }
    if(e.target.matches('[data-close]')) e.target.closest('.modal')?.classList.remove('show');
  });
})();
</script>
<?php endif; ?>
