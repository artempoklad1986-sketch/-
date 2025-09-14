<?php
/**
 * АкваСбор - Функции работы с датами v1.0
 * Совместимо с общей архитектурой
 */

// Устанавливаем часовой пояс
date_default_timezone_set('Europe/Moscow');

// === ОСНОВНЫЕ ФУНКЦИИ ДАТ ===

/**
 * Форматирует дату в человекочитаемый вид
 */
function formatDate($date, $format = 'full') {
    if (empty($date)) return '';

    $timestamp = is_numeric($date) ? $date : strtotime($date);

    switch ($format) {
        case 'short':
            return date('d.m.Y', $timestamp);
        case 'medium':
            return date('d.m.Y H:i', $timestamp);
        case 'full':
            return date('d F Y года в H:i', $timestamp);
        case 'relative':
            return getRelativeTime($timestamp);
        case 'iso':
            return date('c', $timestamp);
        default:
            return date($format, $timestamp);
    }
}

/**
 * Возвращает относительное время (назад/вперед)
 */
function getRelativeTime($timestamp) {
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 0) {
        $diff = abs($diff);
        $suffix = 'через';
        $prefix = '';
    } else {
        $suffix = '';
        $prefix = 'назад';
    }

    $units = [
        31536000 => ['год', 'года', 'лет'],
        2592000 => ['месяц', 'месяца', 'месяцев'], 
        86400 => ['день', 'дня', 'дней'],
        3600 => ['час', 'часа', 'часов'],
        60 => ['минуту', 'минуты', 'минут'],
        1 => ['секунду', 'секунды', 'секунд']
    ];

    foreach ($units as $unit => $names) {
        if ($diff >= $unit) {
            $amount = floor($diff / $unit);
            $name = getPluralForm($amount, $names);

            if ($suffix) {
                return "$suffix $amount $name";
            } else {
                return "$amount $name $prefix";
            }
        }
    }

    return 'только что';
}

/**
 * Возвращает правильную форму множественного числа
 */
function getPluralForm($number, $forms) {
    $number = abs($number) % 100;
    $n1 = $number % 10;

    if ($number > 10 && $number < 20) return $forms[2];
    if ($n1 > 1 && $n1 < 5) return $forms[1];
    if ($n1 == 1) return $forms[0];

    return $forms[2];
}

/**
 * Проверяет валидность даты
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Возвращает начало и конец дня
 */
function getDayBounds($date = null) {
    $date = $date ?: date('Y-m-d');
    return [
        'start' => $date . ' 00:00:00',
        'end' => $date . ' 23:59:59'
    ];
}

/**
 * Возвращает начало и конец недели
 */
function getWeekBounds($date = null) {
    $timestamp = $date ? strtotime($date) : time();
    $weekStart = strtotime('monday this week', $timestamp);
    $weekEnd = strtotime('sunday this week', $timestamp);

    return [
        'start' => date('Y-m-d 00:00:00', $weekStart),
        'end' => date('Y-m-d 23:59:59', $weekEnd)
    ];
}

/**
 * Возвращает начало и конец месяца
 */
function getMonthBounds($date = null) {
    $timestamp = $date ? strtotime($date) : time();
    $year = date('Y', $timestamp);
    $month = date('n', $timestamp);

    return [
        'start' => date('Y-m-d 00:00:00', mktime(0, 0, 0, $month, 1, $year)),
        'end' => date('Y-m-d 23:59:59', mktime(23, 59, 59, $month, date('t', $timestamp), $year))
    ];
}

/**
 * Возвращает начало и конец года
 */
function getYearBounds($date = null) {
    $year = $date ? date('Y', strtotime($date)) : date('Y');

    return [
        'start' => "$year-01-01 00:00:00",
        'end' => "$year-12-31 23:59:59"
    ];
}

/**
 * Возвращает массив дат между двумя датами
 */
function getDateRange($startDate, $endDate, $format = 'Y-m-d') {
    $dates = [];
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1D');

    while ($start <= $end) {
        $dates[] = $start->format($format);
        $start->add($interval);
    }

    return $dates;
}

/**
 * Добавляет/вычитает время от даты
 */
function modifyDate($date, $modification) {
    $datetime = new DateTime($date);
    $datetime->modify($modification);
    return $datetime->format('Y-m-d H:i:s');
}

/**
 * Возвращает разность между двумя датами
 */
function getDateDiff($date1, $date2, $format = 'days') {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    $diff = $d1->diff($d2);

    switch ($format) {
        case 'years':
            return $diff->y;
        case 'months':
            return ($diff->y * 12) + $diff->m;
        case 'days':
            return $diff->days;
        case 'hours':
            return ($diff->days * 24) + $diff->h;
        case 'minutes':
            return (($diff->days * 24 + $diff->h) * 60) + $diff->i;
        case 'seconds':
            return ((($diff->days * 24 + $diff->h) * 60) + $diff->i) * 60 + $diff->s;
        default:
            return $diff;
    }
}

// === СПЕЦИАЛЬНЫЕ ФУНКЦИИ ДЛЯ МАГАЗИНА ===

/**
 * Проверяет рабочие часы магазина
 */
function isStoreOpen($time = null) {
    $settings = getSiteSettings();
    $workingHours = $settings['working_hours'] ?? '9:00-21:00';

    if ($workingHours === '24/7') return true;

    $currentTime = $time ?: date('H:i');
    $currentDay = date('N'); // 1-7 (пн-вс)

    // Простая проверка для демонстрации
    if (preg_match('/(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})/', $workingHours, $matches)) {
        $openTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
        $closeTime = sprintf('%02d:%02d', $matches[3], $matches[4]);

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    return true;
}

/**
 * Рассчитывает предполагаемую дату доставки
 */
function getDeliveryDate($deliveryMethod, $orderDate = null) {
    $orderDate = $orderDate ?: date('Y-m-d H:i:s');
    $orderTimestamp = strtotime($orderDate);

    $deliveryMethods = getDeliveryMethods();
    $method = $deliveryMethods[$deliveryMethod] ?? null;

    if (!$method) {
        return formatDate(modifyDate($orderDate, '+3 days'), 'short');
    }

    // Парсим время доставки
    $timeString = $method['time'];
    $days = 1;

    if (preg_match('/(\d+)-(\d+)\s*дн/', $timeString, $matches)) {
        $days = intval($matches[2]); // Берем максимальное время
    } elseif (preg_match('/(\d+)\s*дн/', $timeString, $matches)) {
        $days = intval($matches[1]);
    }

    // Учитываем рабочие дни
    $deliveryDate = $orderTimestamp;
    $addedDays = 0;

    while ($addedDays < $days) {
        $deliveryDate += 86400; // +1 день
        $dayOfWeek = date('N', $deliveryDate);

        // Пропускаем выходные для большинства служб доставки
        if ($deliveryMethod !== 'post' && ($dayOfWeek == 6 || $dayOfWeek == 7)) {
            continue;
        }

        $addedDays++;
    }

    return formatDate($deliveryDate, 'short');
}

/**
 * Возвращает информацию о времени работы
 */
function getWorkingHoursInfo() {
    $settings = getSiteSettings();
    $workingHours = $settings['working_hours'] ?? 'Ежедневно 9:00-21:00';

    $isOpen = isStoreOpen();
    $status = $isOpen ? 'Открыт' : 'Закрыт';
    $statusClass = $isOpen ? 'text-success' : 'text-danger';

    return [
        'hours' => $workingHours,
        'is_open' => $isOpen,
        'status' => $status,
        'status_class' => $statusClass
    ];
}

/**
 * Генерирует расписание работы для отображения
 */
function getWeekSchedule() {
    $days = [
        1 => 'Понедельник',
        2 => 'Вторник', 
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье'
    ];

    $schedule = [];
    foreach ($days as $num => $name) {
        $schedule[] = [
            'day' => $name,
            'hours' => '9:00 — 21:00',
            'is_today' => date('N') == $num,
            'is_weekend' => in_array($num, [6, 7])
        ];
    }

    return $schedule;
}

// === АНАЛИТИЧЕСКИЕ ФУНКЦИИ ДАТ ===

/**
 * Группирует заказы по датам для аналитики
 */
function groupOrdersByDate($orders, $groupBy = 'day') {
    $grouped = [];

    foreach ($orders as $order) {
        $date = $order['created_at'];
        $timestamp = strtotime($date);

        switch ($groupBy) {
            case 'hour':
                $key = date('Y-m-d H', $timestamp);
                $label = date('H:i', $timestamp);
                break;
            case 'day':
                $key = date('Y-m-d', $timestamp);
                $label = date('d.m.Y', $timestamp);
                break;
            case 'week':
                $key = date('Y-W', $timestamp);
                $label = 'Неделя ' . date('W, Y', $timestamp);
                break;
            case 'month':
                $key = date('Y-m', $timestamp);
                $label = date('F Y', $timestamp);
                break;
            case 'year':
                $key = date('Y', $timestamp);
                $label = date('Y', $timestamp);
                break;
            default:
                $key = date('Y-m-d', $timestamp);
                $label = date('d.m.Y', $timestamp);
        }

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'key' => $key,
                'label' => $label,
                'orders' => [],
                'count' => 0,
                'total_amount' => 0
            ];
        }

        $grouped[$key]['orders'][] = $order;
        $grouped[$key]['count']++;
        $grouped[$key]['total_amount'] += $order['total_amount'];
    }

    // Сортируем по ключу
    ksort($grouped);

    return array_values($grouped);
}

/**
 * Получает статистику за период
 */
function getPeriodStats($startDate, $endDate, $orders = null) {
    if (!$orders) {
        $orders = getOrders();
    }

    $periodOrders = array_filter($orders, function($order) use ($startDate, $endDate) {
        $orderDate = date('Y-m-d', strtotime($order['created_at']));
        return $orderDate >= $startDate && $orderDate <= $endDate;
    });

    $totalRevenue = array_sum(array_column($periodOrders, 'total_amount'));
    $totalOrders = count($periodOrders);
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

    return [
        'period_start' => $startDate,
        'period_end' => $endDate,
        'total_orders' => $totalOrders,
        'total_revenue' => $totalRevenue,
        'avg_order_value' => $avgOrderValue,
        'orders' => $periodOrders
    ];
}

/**
 * Сравнивает статистику двух периодов
 */
function comparePeriods($period1Start, $period1End, $period2Start, $period2End) {
    $stats1 = getPeriodStats($period1Start, $period1End);
    $stats2 = getPeriodStats($period2Start, $period2End);

    $revenueChange = $stats2['total_revenue'] > 0 
        ? (($stats1['total_revenue'] - $stats2['total_revenue']) / $stats2['total_revenue']) * 100
        : 0;

    $ordersChange = $stats2['total_orders'] > 0
        ? (($stats1['total_orders'] - $stats2['total_orders']) / $stats2['total_orders']) * 100
        : 0;

    $avgChange = $stats2['avg_order_value'] > 0
        ? (($stats1['avg_order_value'] - $stats2['avg_order_value']) / $stats2['avg_order_value']) * 100
        : 0;

    return [
        'current_period' => $stats1,
        'previous_period' => $stats2,
        'revenue_change' => round($revenueChange, 1),
        'orders_change' => round($ordersChange, 1),
        'avg_order_change' => round($avgChange, 1)
    ];
}

// === ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ===

/**
 * Конвертирует русские месяцы в английские для strtotime
 */
function convertRussianDate($date) {
    $months = [
        'января' => 'January', 'февраля' => 'February', 'марта' => 'March',
        'апреля' => 'April', 'мая' => 'May', 'июня' => 'June',
        'июля' => 'July', 'августа' => 'August', 'сентября' => 'September',
        'октября' => 'October', 'ноября' => 'November', 'декабря' => 'December'
    ];

    return strtr($date, $months);
}

/**
 * Возвращает локализованные названия месяцев
 */
function getMonthNames() {
    return [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
    ];
}

/**
 * Возвращает локализованные названия дней недели
 */
function getDayNames() {
    return [
        1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг',
        5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'
    ];
}

/**
 * Форматирует период в человекочитаемом виде
 */
function formatPeriod($startDate, $endDate) {
    $start = strtotime($startDate);
    $end = strtotime($endDate);

    if (date('Y-m-d', $start) === date('Y-m-d', $end)) {
        return formatDate($start, 'short');
    }

    if (date('Y-m', $start) === date('Y-m', $end)) {
        return date('d', $start) . ' - ' . formatDate($end, 'short');
    }

    if (date('Y', $start) === date('Y', $end)) {
        return date('d.m', $start) . ' - ' . formatDate($end, 'short');
    }

    return formatDate($start, 'short') . ' - ' . formatDate($end, 'short');
}

// Настройка локали для правильного отображения дат
setlocale(LC_TIME, 'ru_RU.UTF-8', 'rus_RUS.UTF-8', 'Russian_Russia.UTF-8');
?>