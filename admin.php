<?php
require_login(); // авторизация

// Помощники
function table_exists(PDO $pdo, string $name): bool {
  try {
    return (bool)$pdo->query("SHOW TABLES LIKE ".$pdo->quote($name))->fetchColumn();
  } catch (Throwable $e) { return false; }
}
function fetch_one(PDO $pdo, string $sql, $default = 0) {
  try { $v = $pdo->query($sql)->fetchColumn(); return $v===false ? $default : $v; }
  catch (Throwable $e) { return $default; }
}

// День
$today = date('Y-m-d');
$income_today  = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE txn_date='$today' AND type='income'", 0);
$expense_today = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE txn_date='$today' AND type='expense'", 0);
$balance_today = $income_today - $expense_today;

// Месяц
$monthY = date('Y-m');
$income_month  = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'  AND DATE_FORMAT(txn_date,'%Y-%m')='$monthY'", 0);
$expense_month = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND DATE_FORMAT(txn_date,'%Y-%m')='$monthY'", 0);
$balance_month = $income_month - $expense_month;

// Год
$yearY = date('Y');
$income_year  = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'  AND YEAR(txn_date)='$yearY'", 0);
$expense_year = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND YEAR(txn_date)='$yearY'", 0);
$balance_year = $income_year - $expense_year;

// 30 дней: доход, расход, чистый поток и количество заказов
$rows = $pdo->query("
  SELECT d.day,
         COALESCE(inc.sum,0) inc,
         COALESCE(exp.sum,0) exp,
         COALESCE(o.cnt,0) ocnt
  FROM (
    SELECT CURDATE()-INTERVAL seq DAY AS day FROM (
      SELECT 0 seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
      UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13
      UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
      UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27
      UNION SELECT 28 UNION SELECT 29
    ) s
  ) d
  LEFT JOIN (SELECT txn_date, SUM(amount) sum FROM transactions WHERE type='income' GROUP BY txn_date) inc
    ON inc.txn_date = d.day
  LEFT JOIN (SELECT txn_date, SUM(amount) sum FROM transactions WHERE type='expense' GROUP BY txn_date) exp
    ON exp.txn_date = d.day
  LEFT JOIN (SELECT order_date, COUNT(*) cnt FROM orders GROUP BY order_date) o
    ON o.order_date = d.day
  ORDER BY d.day ASC
")->fetchAll(PDO::FETCH_ASSOC);
$labels = array_map(fn($r)=>date('d.m', strtotime($r['day'])), $rows);
$inc    = array_map(fn($r)=>(float)$r['inc'], $rows);
$exp    = array_map(fn($r)=>(float)$r['exp'], $rows);
$net    = [];
$ocnt   = array_map(fn($r)=>(int)$r['ocnt'], $rows);
foreach ($rows as $r) $net[] = (float)$r['inc'] - (float)$r['exp'];

// 12 месяцев: кредит/дебет/сальдо
$monthsRows = $pdo->query("
  SELECT DATE_FORMAT(d.d,'%Y-%m') ym,
         DATE_FORMAT(d.d,'%b %y') lab,
         COALESCE(i.sum,0) inc,
         COALESCE(e.sum,0) exp
  FROM (
    SELECT DATE_FORMAT(DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL seq MONTH),'%Y-%m-01') d
    FROM (SELECT 0 seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
          UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) s
  ) d
  LEFT JOIN (
    SELECT DATE_FORMAT(txn_date,'%Y-%m-01') m, SUM(amount) sum
    FROM transactions WHERE type='income' GROUP BY DATE_FORMAT(txn_date,'%Y-%m-01')
  ) i ON i.m = d.d
  LEFT JOIN (
    SELECT DATE_FORMAT(txn_date,'%Y-%m-01') m, SUM(amount) sum
    FROM transactions WHERE type='expense' GROUP BY DATE_FORMAT(txn_date,'%Y-%m-01')
  ) e ON e.m = d.d
  ORDER BY d.d ASC
")->fetchAll(PDO::FETCH_ASSOC);
$labels12 = array_map(fn($r)=>$r['lab'], $monthsRows);
$inc12    = array_map(fn($r)=>(float)$r['inc'], $monthsRows);
$exp12    = array_map(fn($r)=>(float)$r['exp'], $monthsRows);
$net12    = [];
foreach ($monthsRows as $r) $net12[] = (float)$r['inc'] - (float)$r['exp'];

// Категории (месяц) — всегда выдаем “Прочее”
$cat_labels=[]; $cat_income=[]; $cat_expense=[];
try {
  $cat = $pdo->query("
    SELECT COALESCE(NULLIF(TRIM(category),''),'Прочее') cat,
           SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) inc,
           SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) exp
    FROM transactions
    WHERE DATE_FORMAT(txn_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
    GROUP BY COALESCE(NULLIF(TRIM(category),''),'Прочее')
    ORDER BY (SUM(CASE WHEN type='income' THEN amount ELSE 0 END)+SUM(CASE WHEN type='expense' THEN amount ELSE 0 END)) DESC
    LIMIT 8
  ")->fetchAll(PDO::FETCH_ASSOC);
  $cat_labels = array_column($cat,'cat');
  $cat_income = array_map('floatval', array_column($cat,'inc'));
  $cat_expense= array_map('floatval', array_column($cat,'exp'));
} catch (Throwable $e) {}
if (!$cat_labels) { $cat_labels=['Прочее']; $cat_income=[0]; $cat_expense=[0]; }

// Обязательства (плановые + остатки по заказам) и тексты “за что”
$map = [];
$hasObl = false;
try {
  $hasObl = table_exists($pdo,'obligations');
  if ($hasObl) {
    $oblPlan = $pdo->query("SELECT due_date, SUM(amount) sum FROM obligations WHERE status='planned' GROUP BY due_date")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($oblPlan as $r){ $d=$r['due_date']; $map[$d]=($map[$d]??0)+(float)$r['sum']; }
  }
} catch (Throwable $e) {}

try {
  // сначала пробуем с paid_amount
  $oblOrders = $pdo->query("SELECT due_date, SUM(price - paid_amount) sum FROM orders WHERE status<>'canceled' AND (price-paid_amount)>0 AND due_date IS NOT NULL GROUP BY due_date")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($oblOrders as $r){ $d=$r['due_date']; $map[$d]=($map[$d]??0)+(float)$r['sum']; }
} catch (Throwable $e) {
  // fallback: если нет paid_amount — суммируем полную цену
  try {
    $oblOrders = $pdo->query("SELECT due_date, SUM(price) sum FROM orders WHERE status<>'canceled' AND due_date IS NOT NULL GROUP BY due_date")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($oblOrders as $r){ $d=$r['due_date']; $map[$d]=($map[$d]??0)+(float)$r['sum']; }
  } catch (Throwable $e2) {}
}
ksort($map);
$obl_labels = array_map(fn($d)=>date('d.m', strtotime($d)), array_keys($map));
$obl_values = array_values($map);

// Тексты “за что”
$detail = [];
if ($hasObl) {
  try {
    $list1 = $pdo->query("SELECT DATE_FORMAT(due_date,'%d.%m') d, title reason, amount sum FROM obligations WHERE status='planned' AND due_date>=CURDATE() ORDER BY due_date ASC, id ASC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($list1 as $r) $detail[] = $r['d'].' — '.$r['reason'].' — '.number_format((float)$r['sum'],2,',',' ').' ₽';
  } catch (Throwable $e) {}
}
try {
  $list2 = $pdo->query("SELECT DATE_FORMAT(due_date,'%d.%m') d, CONCAT('Заказ #',id,' — ', LEFT(COALESCE(NULLIF(TRIM(description),''), 'Без описания'), 40)) reason, (price - paid_amount) sum FROM orders WHERE status<>'canceled' AND (price-paid_amount)>0 AND due_date IS NOT NULL AND due_date>=CURDATE() ORDER BY due_date ASC, id ASC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($list2 as $r) $detail[] = $r['d'].' — '.$r['reason'].' — '.number_format((float)$r['sum'],2,',',' ').' ₽';
} catch (Throwable $e) {
  // пропускаем
}
if (!$detail) $detail=['Ближайших платежей нет'];

$done_today = (int)fetch_one($pdo, "SELECT COUNT(*) FROM orders WHERE status='done' AND order_date=CURDATE()", 0);
$done_month = (int)fetch_one($pdo, "SELECT COUNT(*) FROM orders WHERE status='done' AND DATE_FORMAT(order_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);

// ——— CRM/финансы “как в amo/bitrix”: конверсии, средний чек, дебиторка/кредиторка, менеджеры, клиенты, чеки, DSO ———

// Вычисляем дебиторку (AR) по заказам
$hasReceipts = table_exists($pdo, 'receipts');
$ar_total = 0.0; $ar_overdue = 0.0; $ar_7 = 0.0; $ar_30 = 0.0;
try {
  if ($hasReceipts) {
    // если есть таблица receipts: берём оплачено по каждому заказу
    $arRows = $pdo->query("
      SELECT o.id, o.price, o.due_date, COALESCE(pay.paid,0) paid
      FROM orders o
      LEFT JOIN (SELECT order_id, SUM(amount) paid FROM receipts GROUP BY order_id) pay ON pay.order_id=o.id
      WHERE o.status<>'canceled'
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($arRows as $r) {
      $due = $r['due_date'] ? date_create($r['due_date']) : null;
      $unpaid = max(0.0, (float)$r['price'] - (float)$r['paid']);
      if ($unpaid <= 0) continue;
      $ar_total += $unpaid;
      if ($due) {
        $days = (int)date_diff(date_create(date('Y-m-d')), $due)->format('%r%a'); // отрицательные если просрочено
        if ($days < 0) $ar_overdue += $unpaid;
        if ($days >= 0 && $days <= 7)  $ar_7  += $unpaid;
        if ($days >= 0 && $days <= 30) $ar_30 += $unpaid;
      }
    }
  } else {
    // fallback: используем paid_amount и due_date
    $arRows = $pdo->query("
      SELECT id, price, paid_amount, due_date
      FROM orders WHERE status<>'canceled'
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($arRows as $r) {
      $unpaid = max(0.0, (float)$r['price'] - (float)($r['paid_amount'] ?? 0));
      if ($unpaid <= 0) continue;
      $ar_total += $unpaid;
      if (!empty($r['due_date'])) {
        $due = date_create($r['due_date']);
        $days = (int)date_diff(date_create(date('Y-m-d')), $due)->format('%r%a');
        if ($days < 0) $ar_overdue += $unpaid;
        if ($days >= 0 && $days <= 7)  $ar_7  += $unpaid;
        if ($days >= 0 && $days <= 30) $ar_30 += $unpaid;
      }
    }
  }
} catch (Throwable $e) {}

// Кредиторка (AP) из obligations
$ap_total = 0.0; $ap_overdue = 0.0; $ap_7 = 0.0; $ap_30 = 0.0;
if ($hasObl) {
  try {
    $apRows = $pdo->query("SELECT amount, due_date FROM obligations WHERE status='planned'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($apRows as $r) {
      $amt = (float)$r['amount'];
      $ap_total += $amt;
      if (!empty($r['due_date'])) {
        $due = date_create($r['due_date']);
        $days = (int)date_diff(date_create(date('Y-m-d')), $due)->format('%r%a');
        if ($days < 0) $ap_overdue += $amt;
        if ($days >= 0 && $days <= 7)  $ap_7 += $amt;
        if ($days >= 0 && $days <= 30) $ap_30 += $amt;
      }
    }
  } catch (Throwable $e) {}
}

// Средний чек и выручка (как в CRM)
$avg_check_month = 0.0;
$revenue_month   = $income_month; // по транзакциям
try {
  $avg_check_month = (float)fetch_one($pdo, "
    SELECT COALESCE(AVG(price),0) FROM orders
    WHERE status IN ('done','paid') AND DATE_FORMAT(order_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
  ", 0);
} catch (Throwable $e) {}

// Лиды и конверсия (ищем доступную таблицу лидов)
$leadTable = null;
foreach (['leads','crm_leads','amo_leads','bitrix_leads'] as $t) if (table_exists($pdo,$t)) { $leadTable = $t; break; }
$leads_new_month = 0; $leads_won_month = 0; $conversion_month = 0.0;
try {
  if ($leadTable) {
    $leads_new_month = (int)fetch_one($pdo, "SELECT COUNT(*) FROM `$leadTable` WHERE DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
    $leads_won_month = (int)fetch_one($pdo, "SELECT COUNT(*) FROM `$leadTable` WHERE status IN ('won','success') AND DATE_FORMAT(updated_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
  } else {
    // fallback: по заказам
    $leads_new_month = (int)fetch_one($pdo, "SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
    $leads_won_month = (int)fetch_one($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('done','paid') AND DATE_FORMAT(order_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
  }
  $conversion_month = $leads_new_month ? round($leads_won_month*100/$leads_new_month, 1) : 0.0;
} catch (Throwable $e) {}

// Топ клиентов (год)
$clientsTop = ['labels'=>[], 'values'=>[]];
if (table_exists($pdo, 'clients')) {
  try {
    // пробуем по оплатам из receipts, иначе — по цене завершённых заказов
    if ($hasReceipts && (new ReflectionFunction('table_exists')) && table_exists($pdo,'orders')) {
      $top = $pdo->query("
        SELECT COALESCE(c.name, CONCAT('Клиент #',o.client_id)) label, SUM(r.amount) val
        FROM receipts r
        JOIN orders o ON o.id = r.order_id
        LEFT JOIN clients c ON c.id = o.client_id
        WHERE YEAR(r.receipt_date)=YEAR(CURDATE())
        GROUP BY COALESCE(c.name, o.client_id)
        ORDER BY SUM(r.amount) DESC
        LIMIT 7
      ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $top = $pdo->query("
        SELECT COALESCE(c.name, CONCAT('Клиент #',o.client_id)) label, SUM(o.price) val
        FROM orders o
        LEFT JOIN clients c ON c.id = o.client_id
        WHERE o.status IN ('done','paid') AND YEAR(o.order_date)=YEAR(CURDATE())
        GROUP BY COALESCE(c.name, o.client_id)
        ORDER BY SUM(o.price) DESC
        LIMIT 7
      ")->fetchAll(PDO::FETCH_ASSOC);
    }
    $clientsTop['labels'] = array_column($top,'label');
    $clientsTop['values'] = array_map('floatval', array_column($top,'val'));
  } catch (Throwable $e) {}
}

// Производительность менеджеров (месяц)
$manPerf = ['labels'=>[], 'values'=>[]];
$hasUsers = table_exists($pdo,'users');
try {
  if ($hasReceipts && table_exists($pdo,'orders')) {
    // по реальным оплатам
    $sql = "
      SELECT COALESCE(u.name, CONCAT('Менеджер #',o.manager_id)) label, SUM(r.amount) val
      FROM receipts r
      JOIN orders o ON o.id=r.order_id
      LEFT JOIN users u ON u.id = o.manager_id
      WHERE DATE_FORMAT(r.receipt_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
      GROUP BY COALESCE(u.name, o.manager_id)
      ORDER BY SUM(r.amount) DESC
      LIMIT 8
    ";
  } else {
    // по сумме завершённых заказов
    $sql = "
      SELECT COALESCE(u.name, CONCAT('Менеджер #',o.manager_id)) label, SUM(o.price) val
      FROM orders o
      LEFT JOIN users u ON u.id = o.manager_id
      WHERE o.status IN ('done','paid') AND DATE_FORMAT(o.order_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
      GROUP BY COALESCE(u.name, o.manager_id)
      ORDER BY SUM(o.price) DESC
      LIMIT 8
    ";
  }
  $mp = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $manPerf['labels'] = array_column($mp,'label');
  $manPerf['values'] = array_map('floatval', array_column($mp,'val'));
} catch (Throwable $e) {}

// DSO (среднее число дней до оплаты) — по закрытым заказам года
$dso = 0.0;
try {
  if ($hasReceipts) {
    $rowsDSO = $pdo->query("
      SELECT o.id, o.order_date, MAX(r.receipt_date) last_pay
      FROM orders o
      JOIN receipts r ON r.order_id=o.id
      WHERE o.status IN ('done','paid') AND YEAR(o.order_date)=YEAR(CURDATE())
      GROUP BY o.id, o.order_date
    ")->fetchAll(PDO::FETCH_ASSOC);
    $totalDays = 0; $cnt = 0;
    foreach ($rowsDSO as $r) {
      if (!$r['order_date'] || !$r['last_pay']) continue;
      $d = (int)date_diff(date_create($r['order_date']), date_create($r['last_pay']))->format('%a');
      $totalDays += $d; $cnt++;
    }
    if ($cnt>0) $dso = round($totalDays/$cnt, 1);
  } else {
    // без чеков — по дате исполнения как приближение
    $rowsDSO = $pdo->query("
      SELECT order_date, due_date FROM orders
      WHERE status IN ('done','paid') AND YEAR(order_date)=YEAR(CURDATE())
        AND due_date IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    $totalDays = 0; $cnt = 0;
    foreach ($rowsDSO as $r) {
      $d = (int)date_diff(date_create($r['order_date']), date_create($r['due_date']))->format('%a');
      $totalDays += max(0,$d); $cnt++;
    }
    if ($cnt>0) $dso = round($totalDays/$cnt, 1);
  }
} catch (Throwable $e) {}

// Статусы по воронке (orders)
$pipeline = ['labels'=>[], 'values'=>[]];
try {
  $p = $pdo->query("SELECT status, COUNT(*) cnt FROM orders GROUP BY status ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
  $pipeline['labels'] = array_map(fn($r)=>$r['status'] ?: 'unknown', $p);
  $pipeline['values'] = array_map(fn($r)=>(int)$r['cnt'], $p);
} catch (Throwable $e) {}

// Статистика чеков (если есть)
$checks = ['today'=>['sum'=>0,'cnt'=>0], 'month'=>['sum'=>0,'cnt'=>0], 'year'=>['sum'=>0,'cnt'=>0]];
if ($hasReceipts) {
  try {
    $checks['today']['sum'] = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM receipts WHERE receipt_date=CURDATE()", 0);
    $checks['today']['cnt'] = (int)fetch_one($pdo, "SELECT COUNT(*) FROM receipts WHERE receipt_date=CURDATE()", 0);
    $checks['month']['sum'] = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM receipts WHERE DATE_FORMAT(receipt_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
    $checks['month']['cnt'] = (int)fetch_one($pdo, "SELECT COUNT(*) FROM receipts WHERE DATE_FORMAT(receipt_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", 0);
    $checks['year']['sum']  = (float)fetch_one($pdo, "SELECT COALESCE(SUM(amount),0) FROM receipts WHERE YEAR(receipt_date)=YEAR(CURDATE())", 0);
    $checks['year']['cnt']  = (int)fetch_one($pdo, "SELECT COUNT(*) FROM receipts WHERE YEAR(receipt_date)=YEAR(CURDATE())", 0);
  } catch (Throwable $e) {}
}
?>
<script>
// Базовые данные для существующих графиков
window.dashboard3D = {
  categories:{ labels: <?=json_encode($cat_labels, JSON_UNESCAPED_UNICODE)?>, income: <?=json_encode($cat_income)?>, expense: <?=json_encode($cat_expense)?> },
  cashflow:  { labels: <?=json_encode($labels)?>, net: <?=json_encode($net)?>, inc: <?=json_encode($inc)?>, exp: <?=json_encode($exp)?> },
  ordersCnt: { labels: <?=json_encode($labels)?>, values: <?=json_encode($ocnt)?> },
  obligations:{ labels: <?=json_encode($obl_labels)?>, values: <?=json_encode($obl_values)?> },
  today:{ income: <?=json_encode($income_today)?>, expense: <?=json_encode($expense_today)?>, balance: <?=json_encode($balance_today)?> },
  counters:{ doneToday: <?=$done_today?>, doneMonth: <?=$done_month?> }
};
// Расширение умного дашборда (KPI, кредит/дебет/сальдо, CRM-логика)
window.smartDash = {
  kpi: {
    day:   { debit: <?=json_encode($income_today)?>, credit: <?=json_encode($expense_today)?>, saldo: <?=json_encode($balance_today)?> },
    month: { debit: <?=json_encode($income_month)?>,  credit: <?=json_encode($expense_month)?>,  saldo: <?=json_encode($balance_month)?> },
    year:  { debit: <?=json_encode($income_year)?>,   credit: <?=json_encode($expense_year)?>,   saldo: <?=json_encode($balance_year)?> }
  },
  monthly: { labels: <?=json_encode($labels12, JSON_UNESCAPED_UNICODE)?>, inc: <?=json_encode($inc12)?>, exp: <?=json_encode($exp12)?>, net: <?=json_encode($net12)?> },
  ar: { total: <?=json_encode($ar_total)?>, overdue: <?=json_encode($ar_overdue)?>, due7: <?=json_encode($ar_7)?>, due30: <?=json_encode($ar_30)?> },
  ap: { total: <?=json_encode($ap_total)?>, overdue: <?=json_encode($ap_overdue)?>, due7: <?=json_encode($ap_7)?>, due30: <?=json_encode($ap_30)?> },
  crm: { leadsNewMonth: <?=$leads_new_month?>, leadsWonMonth: <?=$leads_won_month?>, conversionMonth: <?=json_encode($conversion_month)?>, avgCheckMonth: <?=json_encode($avg_check_month)?>, revenueMonth: <?=json_encode($revenue_month)?> },
  pipeline: { labels: <?=json_encode($pipeline['labels'], JSON_UNESCAPED_UNICODE)?>, values: <?=json_encode($pipeline['values'])?> },
  topClients: { labels: <?=json_encode($clientsTop['labels'], JSON_UNESCAPED_UNICODE)?>, values: <?=json_encode($clientsTop['values'])?> },
  managers: { labels: <?=json_encode($manPerf['labels'], JSON_UNESCAPED_UNICODE)?>, values: <?=json_encode($manPerf['values'])?> },
  checks: <?=json_encode($checks, JSON_UNESCAPED_UNICODE)?>,
  dso: <?=json_encode($dso)?>
};
window.penguinLines = <?=json_encode(array_slice($detail,0,12), JSON_UNESCAPED_UNICODE)?>;

// Подключаем Chart.js при необходимости и рисуем новые графики
(function(){
  function ready(fn){ if(document.readyState!='loading'){fn()} else document.addEventListener('DOMContentLoaded',fn); }
  function ensureChartJs(cb){
    if (window.Chart) return cb();
    var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/chart.js'; s.onload=cb; document.head.appendChild(s);
  }
  function makeMoneyFmt(v){ try{return new Intl.NumberFormat('ru-RU',{style:'currency',currency:'RUB',maximumFractionDigits:0}).format(v)}catch(e){return (Math.round(v)).toLocaleString('ru-RU')+' ₽'} }
  ready(function(){
    ensureChartJs(function(){
      // KPI: День/Месяц/Год — пончики доход/расход + центр сальдо
      [['kpiDayChart','day'],['kpiMonthChart','month'],['kpiYearChart','year']].forEach(function(pair){
        var id=pair[0], p=pair[1], el=document.getElementById(id); if(!el||!window.smartDash) return;
        var d=smartDash.kpi[p];
        var chart=new Chart(el.getContext('2d'), {
          type:'doughnut',
          data:{ labels:['Дебет','Кредит'], datasets:[{ data:[d.debit, d.credit], backgroundColor:['#30bf78','#ff6b6b'] }] },
          options:{ responsive:true, cutout:'70%', plugins:{ legend:{ position:'bottom' }, tooltip:{ callbacks:{ label:(ctx)=> ctx.label+': '+makeMoneyFmt(ctx.parsed) } } } }
        });
        // центр
        var box=el.parentElement; if(box){ var center=box.querySelector('.kpi-center'); if(center){ center.textContent = (d.saldo>=0?'+':'') + makeMoneyFmt(d.saldo); center.className='kpi-center '+(d.saldo>=0?'good':'bad'); } }
      });

      // 12 месяцев: столбцы доход/расход + линия сальдо
      var mctx = document.getElementById('cd12Chart');
      if (mctx){
        var m = smartDash.monthly;
        new Chart(mctx.getContext('2d'), {
          type:'bar',
          data:{
            labels:m.labels,
            datasets:[
              { type:'bar', label:'Дебет',  data:m.inc, backgroundColor:'#30bf78', stack:'cf' },
              { type:'bar', label:'Кредит', data:m.exp, backgroundColor:'#ff6b6b', stack:'cf' },
              { type:'line',label:'Сальдо', data:m.net, borderColor:'#3b82f6', backgroundColor:'#3b82f6', yAxisID:'y1' }
            ]
          },
          options:{
            responsive:true,
            scales:{ y:{ beginAtZero:true }, y1:{ beginAtZero:true, position:'right', grid:{ drawOnChartArea:false } } },
            plugins:{ legend:{ position:'bottom' }, tooltip:{ callbacks:{ label:(ctx)=> ctx.dataset.label+': '+makeMoneyFmt(ctx.parsed.y ?? ctx.parsed) } } }
          }
        });
      }

      // Дебиторка/Кредиторка
      var arap = document.getElementById('arApChart');
      if (arap){
        var a=smartDash.ar, p=smartDash.ap;
        new Chart(arap.getContext('2d'), {
          type:'bar',
          data:{
            labels:['Всего','Просрочка','7 дней','30 дней'],
            datasets:[
              { label:'Дебиторка', data:[a.total,a.overdue,a.due7,a.due30], backgroundColor:'#f59e0b' },
              { label:'Кредиторка', data:[p.total,p.overdue,p.due7,p.due30], backgroundColor:'#8b5cf6' }
            ]
          },
          options:{ responsive:true, plugins:{ legend:{ position:'bottom' }, tooltip:{ callbacks:{ label:(ctx)=> ctx.dataset.label+': '+makeMoneyFmt(ctx.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
        });
      }

      // Воронка по статусам
      var funnel = document.getElementById('funnelChart');
      if (funnel && smartDash.pipeline.labels.length){
        new Chart(funnel.getContext('2d'), {
          type:'bar',
          data:{ labels: smartDash.pipeline.labels, datasets:[{ label:'Количество', data: smartDash.pipeline.values, backgroundColor:'#16a34a' }] },
          options:{ indexAxis:'y', responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ beginAtZero:true } } }
        });
      }

      // Топ клиентов
      var tc = document.getElementById('topClientsChart');
      if (tc && smartDash.topClients.labels.length){
        new Chart(tc.getContext('2d'), {
          type:'bar',
          data:{ labels: smartDash.topClients.labels, datasets:[{ label:'Выручка (год)', data: smartDash.topClients.values, backgroundColor:'#0ea5e9' }] },
          options:{ responsive:true, plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label:(ctx)=> makeMoneyFmt(ctx.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
        });
      }

      // Менеджеры
      var mp = document.getElementById('managersChart');
      if (mp && smartDash.managers.labels.length){
        new Chart(mp.getContext('2d'), {
          type:'bar',
          data:{ labels: smartDash.managers.labels, datasets:[{ label:'Выручка (месяц)', data: smartDash.managers.values, backgroundColor:'#06b6d4' }] },
          options:{ responsive:true, plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label:(ctx)=> makeMoneyFmt(ctx.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
        });
      }

      // DSO — пончик
      var dso = document.getElementById('dsoChart');
      if (dso){
        var val = smartDash.dso || 0;
        new Chart(dso.getContext('2d'), {
          type:'doughnut',
          data:{ labels:['Средние дни до оплаты'], datasets:[{ data:[val, Math.max(0, 60 - val)], backgroundColor:['#10b981','#e5e7eb'] }] },
          options:{ cutout:'75%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label:(ctx)=> (ctx.dataIndex===0?'DSO: ':'Порог: ')+Math.round(ctx.parsed)+' дн.' } } } }
        });
        var center = dso.parentElement?.querySelector('.kpi-center');
        if (center){ center.textContent = (val||0).toFixed(1)+' дн.'; center.className='kpi-center'; }
      }
    });

    // Пингвин: окно ближайших платежей
    var pa=document.getElementById('penguinAlert'), ov=document.getElementById('penguinOverlay'), tw=document.getElementById('typewriter'), btnClose=document.getElementById('pwClose'), pgClose=document.querySelector('.penguin-close');
    function openW(){ ov.style.display='block'; let lines=(window.penguinLines||[]).slice(); tw.innerHTML=''; let i=0; function type(){ if(i>=lines.length)return; let p=document.createElement('div'); p.className='tw-line'; p.textContent=lines[i++]; tw.appendChild(p); setTimeout(type,120); } type(); }
    function closeW(){ ov.style.display='none'; }
    pa && pa.addEventListener('click', function(e){ if(e.target.classList.contains('penguin-close')) return; openW(); });
    btnClose && btnClose.addEventListener('click', closeW);
    pgClose && pgClose.addEventListener('click', function(e){ document.getElementById('penguinAlert').style.display='none'; e.stopPropagation(); });
  });
})();
</script>

<noscript><div class="alert warn">Для графиков нужен включенный JavaScript.</div></noscript>

<!-- Существующие карточки -->
<div class="grid dashboard-grid">
  <div class="card stat glass"><div class="stat-title">Доход за день</div><div class="stat-value good"><?=number_format($income_today,2,',',' ')?> ₽</div></div>
  <div class="card stat glass"><div class="stat-title">Расход за день</div><div class="stat-value bad"><?=number_format($expense_today,2,',',' ')?> ₽</div></div>
  <div class="card stat glass"><div class="stat-title">Движение за день</div><div class="stat-value"><?=number_format($balance_today,2,',',' ')?> ₽</div></div>
</div>

<ul class="chips">
  <li class="chip"><a href="#cat">Категории</a></li>
  <li class="chip"><a href="#flow">Движение</a></li>
  <li class="chip"><a href="#ord">Заказы/день</a></li>
</ul>

<div class="grid3">
  <div class="panel glass" id="cat"><div class="panel-title">Категории доходов / расходов (месяц)</div><canvas id="catChart" height="200"></canvas></div>
  <div class="panel glass" id="flow"><div class="panel-title">Движение средств (30 дней)</div><canvas id="flowChart" height="200"></canvas></div>
  <div class="panel glass" id="ord"><div class="panel-title">Количество заказов за день (30 дней)</div><canvas id="ordersChart" height="200"></canvas></div>
</div>

<div class="panel glass" id="obl">
  <div class="panel-title">Обязательства по датам</div>
  <canvas id="oblChart" height="200"></canvas>
</div>

<!-- Пингвин -->
<div class="penguin-alert" id="penguinAlert" title="Открыть ближайшие платежи">
  <svg class="penguin" width="64" height="64" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="g" cx="50%" cy="45%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#dfe6f1"/></radialGradient></defs><ellipse cx="64" cy="70" rx="40" ry="46" fill="#11131a"/><ellipse cx="64" cy="78" rx="28" ry="34" fill="url(#g)"/><circle cx="48" cy="56" r="10" fill="#fff"/><circle cx="80" cy="56" r="10" fill="#fff"/><circle cx="50" cy="58" r="5" fill="#11131a"/><circle cx="78" cy="58" r="5" fill="#11131a"/><path d="M38 46 L56 52" stroke="#ff4d4f" stroke-width="4" stroke-linecap="round"/><path d="M90 46 L72 52" stroke="#ff4d4f" stroke-width="4" stroke-linecap="round"/><path d="M58 70 L70 70 L64 80 Z" fill="#ff8c00"/><ellipse cx="50" cy="108" rx="10" ry="6" fill="#ff8c00"/><ellipse cx="78" cy="108" rx="10" ry="6" fill="#ff8c00"/></svg>
  <div class="bubble">Ближайшие платежи — нажмите</div>
  <button class="penguin-close" title="Закрыть">×</button>
</div>

<div class="overlay" id="penguinOverlay" style="display:none;">
  <div class="penguin-window glass">
    <div class="pw-head"><div class="pw-title">Обязательства — ближайшие платежи</div><button class="btn ghost small" id="pwClose">✕</button></div>
    <div class="pw-body">
      <div class="pw-left">
        <svg class="penguin big" width="96" height="96" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="g2" cx="50%" cy="45%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#dfe6f1"/></radialGradient></defs><ellipse cx="64" cy="70" rx="40" ry="46" fill="#11131a"/><ellipse cx="64" cy="78" rx="28" ry="34" fill="url(#g2)"/><circle cx="48" cy="56" r="10" fill="#fff"/><circle cx="80" cy="56" r="10" fill="#fff"/><circle cx="50" cy="58" r="5" fill="#11131a"/><circle cx="78" cy="58" r="5" fill="#11131a"/><path d="M38 46 L56 52" stroke="#ff4d4f" stroke-width="4" stroke-linecap="round"/><path d="M90 46 L72 52" stroke="#ff4d4f" stroke-width="4" stroke-linecap="round"/><path d="M58 70 L70 70 L64 80 Z" fill="#ff8c00"/><ellipse cx="50" cy="108" rx="10" ry="6" fill="#ff8c00"/><ellipse cx="78" cy="108" rx="10" ry="6" fill="#ff8c00"/></svg>
      </div>
      <div class="pw-right"><div class="typewriter" id="typewriter"></div></div>
    </div>
  </div>
</div>

<!-- НИЖЕ — новые “умные” модули KPI и CRM (без изменения существующих стилей/структуры) -->

<ul class="chips">
  <li class="chip"><a href="#kpi">KPI День/Месяц/Год</a></li>
  <li class="chip"><a href="#cd12">Кредит/Дебет/Сальдо 12 мес.</a></li>
  <li class="chip"><a href="#arap">Дебиторка / Кредиторка</a></li>
  <li class="chip"><a href="#funnel">Воронка CRM</a></li>
  <li class="chip"><a href="#clients">Топ клиентов</a></li>
  <li class="chip"><a href="#managers">Менеджеры</a></li>
  <li class="chip"><a href="#ops">Операционные KPI</a></li>
</ul>

<div class="grid3" id="kpi">
  <div class="panel glass">
    <div class="panel-title">KPI: День</div>
    <div class="kpi-wrapper" style="position:relative;text-align:center;">
      <canvas id="kpiDayChart" height="180"></canvas>
      <div class="kpi-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-weight:700;"></div>
    </div>
    <div class="muted" style="text-align:center;margin-top:.5rem;">Сальдо дня</div>
  </div>
  <div class="panel glass">
    <div class="panel-title">KPI: Месяц</div>
    <div class="kpi-wrapper" style="position:relative;text-align:center;">
      <canvas id="kpiMonthChart" height="180"></canvas>
      <div class="kpi-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-weight:700;"></div>
    </div>
    <div class="muted" style="text-align:center;margin-top:.5rem;">Сальдо месяца</div>
  </div>
  <div class="panel glass">
    <div class="panel-title">KPI: Год</div>
    <div class="kpi-wrapper" style="position:relative;text-align:center;">
      <canvas id="kpiYearChart" height="180"></canvas>
      <div class="kpi-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-weight:700;"></div>
    </div>
    <div class="muted" style="text-align:center;margin-top:.5rem;">Сальдо года</div>
  </div>
</div>

<div class="panel glass" id="cd12">
  <div class="panel-title">Кредит / Дебет / Сальдо по месяцам (12 мес.)</div>
  <canvas id="cd12Chart" height="220"></canvas>
</div>

<div class="grid3" id="arap">
  <div class="panel glass">
    <div class="panel-title">Дебиторка vs Кредиторка</div>
    <canvas id="arApChart" height="200"></canvas>
  </div>
  <div class="panel glass">
    <div class="panel-title">DSO — средние дни до оплаты</div>
    <div class="kpi-wrapper" style="position:relative;text-align:center;">
      <canvas id="dsoChart" height="180"></canvas>
      <div class="kpi-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-weight:700;"></div>
    </div>
    <div class="muted" style="text-align:center;margin-top:.5rem;">Чем меньше — тем лучше</div>
  </div>
  <div class="panel glass">
    <div class="panel-title">Платежная нагрузка</div>
    <div class="grid dashboard-grid">
      <div class="card stat glass"><div class="stat-title">Дебиторка всего</div><div class="stat-value"><?=number_format($ar_total,0,',',' ')?> ₽</div></div>
      <div class="card stat glass"><div class="stat-title">Кредиторка всего</div><div class="stat-value"><?=number_format($ap_total,0,',',' ')?> ₽</div></div>
      <div class="card stat glass"><div class="stat-title">Просрочка (AR/AP)</div><div class="stat-value"><?=number_format($ar_overdue,0,',',' ')?> / <?=number_format($ap_overdue,0,',',' ')?> ₽</div></div>
    </div>
  </div>
</div>

<div class="grid3" id="funnel">
  <div class="panel glass">
    <div class="panel-title">Воронка по статусам заказов</div>
    <canvas id="funnelChart" height="220"></canvas>
  </div>
  <div class="panel glass" id="clients">
    <div class="panel-title">Топ клиентов по выручке (год)</div>
    <canvas id="topClientsChart" height="220"></canvas>
  </div>
  <div class="panel glass" id="managers">
    <div class="panel-title">Производительность менеджеров (месяц)</div>
    <canvas id="managersChart" height="220"></canvas>
  </div>
</div>

<div class="grid3" id="ops">
  <div class="panel glass">
    <div class="panel-title">Операционные KPI (месяц)</div>
    <div class="grid dashboard-grid">
      <div class="card stat glass"><div class="stat-title">Новые лиды</div><div class="stat-value"><?=number_format($leads_new_month,0,',',' ')?></div></div>
      <div class="card stat glass"><div class="stat-title">Сделок закрыто</div><div class="stat-value"><?=number_format($leads_won_month,0,',',' ')?></div></div>
      <div class="card stat glass"><div class="stat-title">Конверсия</div><div class="stat-value"><?=number_format($conversion_month,1,',',' ')?>%</div></div>
    </div>
  </div>
  <div class="panel glass">
    <div class="panel-title">Финансовые KPI (месяц)</div>
    <div class="grid dashboard-grid">
      <div class="card stat glass"><div class="stat-title">Выручка (MTD)</div><div class="stat-value"><?=number_format($revenue_month,0,',',' ')?> ₽</div></div>
      <div class="card stat glass"><div class="stat-title">Средний чек</div><div class="stat-value"><?=number_format($avg_check_month,0,',',' ')?> ₽</div></div>
      <div class="card stat glass"><div class="stat-title">Сделок завершено</div><div class="stat-value"><?=number_format($done_month,0,',',' ')?></div></div>
    </div>
  </div>
  <div class="panel glass">
    <div class="panel-title">Чеки / Платежи</div>
    <div class="grid dashboard-grid">
      <div class="card stat glass">
        <div class="stat-title">Сегодня</div>
        <div class="stat-value"><?=number_format($checks['today']['sum'] ?? 0,0,',',' ')?> ₽</div>
        <div class="muted"><?=number_format($checks['today']['cnt'] ?? 0,0,',',' ')?> платеж(ей)</div>
      </div>
      <div class="card stat glass">
        <div class="stat-title">В этом месяце</div>
        <div class="stat-value"><?=number_format($checks['month']['sum'] ?? 0,0,',',' ')?> ₽</div>
        <div class="muted"><?=number_format($checks['month']['cnt'] ?? 0,0,',',' ')?> платеж(ей)</div>
      </div>
      <div class="card stat glass">
        <div class="stat-title">В этом году</div>
        <div class="stat-value"><?=number_format($checks['year']['sum'] ?? 0,0,',',' ')?> ₽</div>
        <div class="muted"><?=number_format($checks['year']['cnt'] ?? 0,0,',',' ')?> платеж(ей)</div>
      </div>
    </div>
  </div>
</div>
