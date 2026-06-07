<?php
// ============================================================
// CQLite.php — Database Engine v1.0
// Обёртка над PDO SQLite для PrintCRM
// Shared hosting (Beget) совместимость
// PHP 8.2+
// ============================================================

declare(strict_types=1);

class CQLite
{
    private static ?CQLite $instance = null;
    private PDO $pdo;
    private string $dbPath;
    private int $queryCount = 0;
    private array $queryLog = [];
    private bool $debug;

    // ── Константы ──────────────────────────────────────────
    private const SCHEMA_VERSION = 1;
    private const BUSY_TIMEOUT   = 5000; // мс ожидания снятия блокировки
    private const CACHE_SIZE     = 4000; // страниц в кэше
    private const PAGE_SIZE      = 4096; // байт

    // ── Конструктор (приватный — Singleton) ─────────────────
    private function __construct(string $dbPath, bool $debug = false)
    {
        $this->dbPath = $dbPath;
        $this->debug  = $debug;
        $this->connect();
        $this->applyPragmas();
        $this->initSchema();
    }

    // ── Singleton ───────────────────────────────────────────
    public static function getInstance(
        string $dbPath = '',
        bool   $debug  = false
    ): self {
        if (self::$instance === null) {
            if (!$dbPath) {
                throw new \RuntimeException('CQLite: путь к БД не указан при первом вызове');
            }
            self::$instance = new self($dbPath, $debug);
        }
        return self::$instance;
    }

    // ── Сброс (для тестов / миграций) ───────────────────────
    public static function reset(): void
    {
        self::$instance = null;
    }

    // ── Подключение ─────────────────────────────────────────
    private function connect(): void
    {
        $dir = dirname($this->dbPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $this->pdo = new PDO(
                'sqlite:' . $this->dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 10,
                ]
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException('CQLite: ошибка подключения — ' . $e->getMessage());
        }
    }

    // ── PRAGMA настройки ────────────────────────────────────
    private function applyPragmas(): void
    {
        $pragmas = [
            'journal_mode = WAL',           // Write-Ahead Log — параллельные чтения
            'synchronous = NORMAL',         // Баланс надёжность/скорость
            'busy_timeout = ' . self::BUSY_TIMEOUT,
            'cache_size = '   . self::CACHE_SIZE,
            'page_size = '    . self::PAGE_SIZE,
            'foreign_keys = ON',            // Каскадные удаления
            'auto_vacuum = INCREMENTAL',    // Дефрагментация
            'temp_store = MEMORY',          // Временные таблицы в памяти
        ];

        foreach ($pragmas as $pragma) {
            $this->pdo->exec('PRAGMA ' . $pragma);
        }
    }

    // ════════════════════════════════════════════════════════
    //  SCHEMA — создание таблиц
    // ════════════════════════════════════════════════════════
    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_version (
                version     INTEGER NOT NULL DEFAULT 0,
                applied_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        $row = $this->pdo->query("SELECT version FROM schema_version ORDER BY rowid DESC LIMIT 1")
                         ->fetch();

        $current = $row ? (int)$row['version'] : 0;

        if ($current < self::SCHEMA_VERSION) {
            $this->runMigrations($current);
        }
    }

    // ── Миграции ────────────────────────────────────────────
    private function runMigrations(int $from): void
    {
        $this->beginTransaction();

        try {
            if ($from < 1) {
                $this->migration_v1();
            }
            // if ($from < 2) { $this->migration_v2(); }

            $this->pdo->exec(
                "INSERT INTO schema_version (version) VALUES (" . self::SCHEMA_VERSION . ")"
            );

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollback();
            throw new \RuntimeException('CQLite: ошибка миграции — ' . $e->getMessage());
        }
    }

    // ── Migration v1 — базовые таблицы ──────────────────────
    private function migration_v1(): void
    {
        // ── orders ──────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id          TEXT    PRIMARY KEY,
                num         TEXT    NOT NULL DEFAULT '',
                client      TEXT    NOT NULL DEFAULT '',
                phone       TEXT    NOT NULL DEFAULT '',
                manager     TEXT    NOT NULL DEFAULT '',
                service     TEXT    NOT NULL DEFAULT '',
                service_label TEXT  NOT NULL DEFAULT '',
                size        TEXT    NOT NULL DEFAULT '',
                status      TEXT    NOT NULL DEFAULT 'new'
                                    CHECK(status IN ('new','work','ready','done','cancel')),
                payment     TEXT    NOT NULL DEFAULT 'Наличные',
                total       REAL    NOT NULL DEFAULT 0,
                prepay      REAL    NOT NULL DEFAULT 0,
                biz_cat     TEXT    NOT NULL DEFAULT '',
                deadline    TEXT,
                comment     TEXT    NOT NULL DEFAULT '',
                files       TEXT    NOT NULL DEFAULT '[]',   -- JSON array
                extra       TEXT    NOT NULL DEFAULT '{}',   -- JSON прочие поля
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── finance ──────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS finance (
                id          TEXT    PRIMARY KEY,
                type        TEXT    NOT NULL CHECK(type IN ('income','expense')),
                category    TEXT    NOT NULL DEFAULT '',
                description TEXT    NOT NULL DEFAULT '',
                amount      REAL    NOT NULL DEFAULT 0,
                method      TEXT    NOT NULL DEFAULT 'Наличные',
                client      TEXT    NOT NULL DEFAULT '',
                order_id    TEXT    REFERENCES orders(id) ON DELETE SET NULL,
                date        TEXT    NOT NULL,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── clients ──────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id          TEXT    PRIMARY KEY,
                name        TEXT    NOT NULL DEFAULT '',
                type        TEXT    NOT NULL DEFAULT 'Физическое лицо',
                phone       TEXT    NOT NULL DEFAULT '',
                email       TEXT    NOT NULL DEFAULT '',
                address     TEXT    NOT NULL DEFAULT '',
                inn         TEXT    NOT NULL DEFAULT '',
                biz_cat     TEXT    NOT NULL DEFAULT '',
                discount    REAL    NOT NULL DEFAULT 0,
                notes       TEXT    NOT NULL DEFAULT '',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── warehouse ────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS warehouse (
                id          TEXT    PRIMARY KEY,
                name        TEXT    NOT NULL,
                category    TEXT    NOT NULL DEFAULT 'Прочее',
                unit        TEXT    NOT NULL DEFAULT 'шт',
                qty         REAL    NOT NULL DEFAULT 0,
                min_qty     REAL    NOT NULL DEFAULT 0,
                price       REAL    NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── warehouse_movements ──────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS warehouse_movements (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                item_id     TEXT    NOT NULL REFERENCES warehouse(id) ON DELETE CASCADE,
                type        TEXT    NOT NULL CHECK(type IN ('restock','deduct','correction')),
                qty         REAL    NOT NULL,
                comment     TEXT    NOT NULL DEFAULT '',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── staff ─────────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS staff (
                id          TEXT    PRIMARY KEY,
                name        TEXT    NOT NULL,
                pin_hash    TEXT    NOT NULL,
                role        TEXT    NOT NULL DEFAULT 'Менеджер',
                phone       TEXT    NOT NULL DEFAULT '',
                color       TEXT    NOT NULL DEFAULT '#7c3aed',
                is_active   INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── staff_log ────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS staff_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                staff_id    TEXT    REFERENCES staff(id) ON DELETE SET NULL,
                staff_name  TEXT    NOT NULL DEFAULT '',
                action      TEXT    NOT NULL DEFAULT '',
                details     TEXT    NOT NULL DEFAULT '',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── shifts ───────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS shifts (
                id          TEXT    PRIMARY KEY,
                staff_id    TEXT    REFERENCES staff(id) ON DELETE SET NULL,
                staff_name  TEXT    NOT NULL DEFAULT '',
                opened_at   TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                closed_at   TEXT,
                income      REAL    NOT NULL DEFAULT 0,
                expense     REAL    NOT NULL DEFAULT 0,
                orders_count INTEGER NOT NULL DEFAULT 0,
                notes       TEXT    NOT NULL DEFAULT '',
                status      TEXT    NOT NULL DEFAULT 'open'
                                    CHECK(status IN ('open','closed'))
            )
        ");

        // ── salary ───────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS salary (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                staff_id    TEXT    REFERENCES staff(id) ON DELETE SET NULL,
                staff_name  TEXT    NOT NULL DEFAULT '',
                type        TEXT    NOT NULL DEFAULT 'salary'
                                    CHECK(type IN ('salary','bonus','advance','penalty')),
                amount      REAL    NOT NULL DEFAULT 0,
                period      TEXT    NOT NULL DEFAULT '',
                comment     TEXT    NOT NULL DEFAULT '',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── notes ─────────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS notes (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                title       TEXT    NOT NULL DEFAULT '',
                body        TEXT    NOT NULL DEFAULT '',
                priority    TEXT    NOT NULL DEFAULT 'normal'
                                    CHECK(priority IN ('normal','info','important','urgent')),
                shift       TEXT    NOT NULL DEFAULT '',
                is_read     INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── cal_events ───────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cal_events (
                id          TEXT    PRIMARY KEY,
                title       TEXT    NOT NULL DEFAULT '',
                date        TEXT    NOT NULL,
                time        TEXT    NOT NULL DEFAULT '',
                type        TEXT    NOT NULL DEFAULT 'task'
                                    CHECK(type IN ('task','deadline','meeting','delivery')),
                color       TEXT    NOT NULL DEFAULT '#7c3aed',
                note        TEXT    NOT NULL DEFAULT '',
                order_id    TEXT    REFERENCES orders(id) ON DELETE CASCADE,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── settings ─────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key         TEXT    PRIMARY KEY,
                value       TEXT    NOT NULL DEFAULT '',
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── debts ─────────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS debts (
                id          TEXT    PRIMARY KEY,
                client      TEXT    NOT NULL DEFAULT '',
                phone       TEXT    NOT NULL DEFAULT '',
                amount      REAL    NOT NULL DEFAULT 0,
                description TEXT    NOT NULL DEFAULT '',
                order_id    TEXT    REFERENCES orders(id) ON DELETE SET NULL,
                status      TEXT    NOT NULL DEFAULT 'open'
                                    CHECK(status IN ('open','partial','closed')),
                paid        REAL    NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── notifications_log ────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                channel     TEXT    NOT NULL DEFAULT '',   -- telegram|vk|max|system
                event       TEXT    NOT NULL DEFAULT '',
                recipient   TEXT    NOT NULL DEFAULT '',
                payload     TEXT    NOT NULL DEFAULT '{}',
                status      TEXT    NOT NULL DEFAULT 'sent'
                                    CHECK(status IN ('sent','failed','pending')),
                error       TEXT    NOT NULL DEFAULT '',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── api_log ──────────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS api_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                method      TEXT    NOT NULL DEFAULT '',
                endpoint    TEXT    NOT NULL DEFAULT '',
                ip          TEXT    NOT NULL DEFAULT '',
                user_agent  TEXT    NOT NULL DEFAULT '',
                status_code INTEGER NOT NULL DEFAULT 200,
                duration_ms INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── integrations ─────────────────────────────────────
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS integrations (
                id          TEXT    PRIMARY KEY,
                name        TEXT    NOT NULL DEFAULT '',
                type        TEXT    NOT NULL DEFAULT '',   -- pos|messenger|marketplace|payment
                config      TEXT    NOT NULL DEFAULT '{}', -- JSON конфиг
                is_active   INTEGER NOT NULL DEFAULT 0,
                last_sync   TEXT,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            )
        ");

        // ── ИНДЕКСЫ ──────────────────────────────────────────
        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_orders_status     ON orders(status)',
            'CREATE INDEX IF NOT EXISTS idx_orders_date       ON orders(created_at)',
            'CREATE INDEX IF NOT EXISTS idx_orders_client     ON orders(client)',
            'CREATE INDEX IF NOT EXISTS idx_orders_phone      ON orders(phone)',
            'CREATE INDEX IF NOT EXISTS idx_finance_type      ON finance(type)',
            'CREATE INDEX IF NOT EXISTS idx_finance_date      ON finance(date)',
            'CREATE INDEX IF NOT EXISTS idx_clients_phone     ON clients(phone)',
            'CREATE INDEX IF NOT EXISTS idx_clients_name      ON clients(name)',
            'CREATE INDEX IF NOT EXISTS idx_warehouse_name    ON warehouse(name)',
            'CREATE INDEX IF NOT EXISTS idx_cal_events_date   ON cal_events(date)',
            'CREATE INDEX IF NOT EXISTS idx_debts_status      ON debts(status)',
            'CREATE INDEX IF NOT EXISTS idx_notif_channel     ON notifications_log(channel)',
            'CREATE INDEX IF NOT EXISTS idx_api_log_endpoint  ON api_log(endpoint)',
        ];

        foreach ($indexes as $idx) {
            $this->pdo->exec($idx);
        }
    }

    // ════════════════════════════════════════════════════════
    //  QUERY BUILDER — базовые операции
    // ════════════════════════════════════════════════════════

    /**
     * Выполнить SELECT — вернуть массив строк
     */
    public function select(
        string $table,
        array  $where   = [],
        array  $options = []
    ): array {
        [$sql, $params] = $this->buildSelect($table, $where, $options);
        return $this->query($sql, $params);
    }

    /**
     * Выполнить SELECT — вернуть одну строку или null
     */
    public function selectOne(
        string $table,
        array  $where   = [],
        array  $options = []
    ): ?array {
        $options['limit'] = 1;
        $result = $this->select($table, $where, $options);
        return $result[0] ?? null;
    }

    /**
     * INSERT
     */
    public function insert(string $table, array $data): string|int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('CQLite::insert — пустые данные');
        }

        $data = $this->prepareData($data);
        $cols = array_keys($data);
        $phs  = array_map(fn($c) => ':' . $c, $cols);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteTable($table),
            implode(', ', $cols),
            implode(', ', $phs)
        );

        $this->execute($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * UPDATE
     */
    public function update(string $table, array $data, array $where): int
    {
        if (empty($data))  throw new \InvalidArgumentException('CQLite::update — пустые данные');
        if (empty($where)) throw new \InvalidArgumentException('CQLite::update — пустой WHERE');

        $data  = $this->prepareData($data);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $setParts   = [];
        $bindParams = [];

        foreach ($data as $col => $val) {
            $setParts[]              = $col . ' = :set_' . $col;
            $bindParams['set_' . $col] = $val;
        }

        [$whereSql, $whereParams] = $this->buildWhere($where, 'w_');
        $params = array_merge($bindParams, $whereParams);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteTable($table),
            implode(', ', $setParts),
            $whereSql
        );

        $this->execute($sql, $params);
        return (int)$this->pdo->query('SELECT changes()')->fetchColumn();
    }

    /**
     * DELETE
     */
    public function delete(string $table, array $where): int
    {
        if (empty($where)) throw new \InvalidArgumentException('CQLite::delete — пустой WHERE');

        [$whereSql, $params] = $this->buildWhere($where);

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->quoteTable($table),
            $whereSql
        );

        $this->execute($sql, $params);
        return (int)$this->pdo->query('SELECT changes()')->fetchColumn();
    }

    /**
     * UPSERT (INSERT OR REPLACE)
     */
    public function upsert(string $table, array $data): void
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('CQLite::upsert — пустые данные');
        }

        $data = $this->prepareData($data);
        $cols = array_keys($data);
        $phs  = array_map(fn($c) => ':' . $c, $cols);

        $sql = sprintf(
            'INSERT OR REPLACE INTO %s (%s) VALUES (%s)',
            $this->quoteTable($table),
            implode(', ', $cols),
            implode(', ', $phs)
        );

        $this->execute($sql, $data);
    }

    /**
     * COUNT
     */
    public function count(string $table, array $where = []): int
    {
        [$whereSql, $params] = $this->buildWhere($where);

        $sql = 'SELECT COUNT(*) FROM ' . $this->quoteTable($table);
        if ($whereSql) $sql .= ' WHERE ' . $whereSql;

        return (int)$this->raw($sql, $params)->fetchColumn();
    }

    /**
     * SUM
     */
    public function sum(string $table, string $column, array $where = []): float
    {
        [$whereSql, $params] = $this->buildWhere($where);

        $sql = 'SELECT COALESCE(SUM(' . $column . '), 0) FROM ' . $this->quoteTable($table);
        if ($whereSql) $sql .= ' WHERE ' . $whereSql;

        return (float)$this->raw($sql, $params)->fetchColumn();
    }

    /**
     * Произвольный SELECT — вернуть массив
     */
    public function query(string $sql, array $params = []): array
    {
        return $this->raw($sql, $params)->fetchAll();
    }

    /**
     * Произвольный запрос (INSERT/UPDATE/DELETE)
     */
    public function execute(string $sql, array $params = []): void
    {
        $this->raw($sql, $params);
    }

    /**
     * Сырой запрос — вернуть PDOStatement
     */
    public function raw(string $sql, array $params = []): \PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'CQLite::raw SQL Error: ' . $e->getMessage() . ' | SQL: ' . $sql
            );
        }

        $this->queryCount++;

        if ($this->debug) {
            $this->queryLog[] = [
                'sql'    => $sql,
                'params' => $params,
                'ms'     => round((microtime(true) - $start) * 1000, 2),
            ];
        }

        return $stmt;
    }

    // ════════════════════════════════════════════════════════
    //  ТРАНЗАКЦИИ
    // ════════════════════════════════════════════════════════

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Транзакция с автоматическим rollback при исключении
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════════
    //  PAGINATION
    // ════════════════════════════════════════════════════════

    public function paginate(
        string $table,
        array  $where   = [],
        int    $page    = 1,
        int    $perPage = 20,
        array  $options = []
    ): array {
        $page    = max(1, $page);
        $perPage = min(200, max(1, $perPage));
        $total   = $this->count($table, $where);
        $pages   = (int)ceil($total / $perPage);

        $options['limit']  = $perPage;
        $options['offset'] = ($page - 1) * $perPage;

        return [
            'data'     => $this->select($table, $where, $options),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $pages,
        ];
    }

    // ════════════════════════════════════════════════════════
    //  SETTINGS — ключ/значение
    // ════════════════════════════════════════════════════════

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $row = $this->selectOne('settings', ['key' => $key]);
        if (!$row) return $default;

        $val = $row['value'];
        $decoded = json_decode($val, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $encoded = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $this->upsert('settings', [
            'key'        => $key,
            'value'      => $encoded,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getSettings(array $keys = []): array
    {
        $rows = empty($keys)
            ? $this->select('settings')
            : $this->query(
                'SELECT * FROM settings WHERE key IN (' .
                implode(',', array_fill(0, count($keys), '?')) . ')',
                $keys
              );

        $result = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row['value'], true);
            $result[$row['key']] = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : $row['value'];
        }
        return $result;
    }

    public function setSettings(array $map): void
    {
        $this->transaction(function () use ($map) {
            foreach ($map as $key => $value) {
                $this->setSetting($key, $value);
            }
        });
    }

    // ════════════════════════════════════════════════════════
    //  УТИЛИТЫ
    // ════════════════════════════════════════════════════════

    /**
     * Генерация уникального ID
     */
    public static function uid(string $prefix = ''): string
    {
        return $prefix . date('Ymd') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);
    }

    /**
     * Размер базы данных
     */
    public function getDbSize(): array
    {
        $size = file_exists($this->dbPath) ? filesize($this->dbPath) : 0;
        return [
            'bytes' => $size,
            'kb'    => round($size / 1024, 1),
            'mb'    => round($size / 1048576, 2),
        ];
    }

    /**
     * Оптимизация базы
     */
    public function optimize(): void
    {
        $this->pdo->exec('PRAGMA incremental_vacuum');
        $this->pdo->exec('PRAGMA optimize');
        $this->pdo->exec('ANALYZE');
    }

    /**
     * Статистика запросов (debug)
     */
    public function getQueryLog(): array
    {
        return [
            'count'   => $this->queryCount,
            'queries' => $this->queryLog,
        ];
    }

    /**
     * Проверка связи
     */
    public function ping(): bool
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Список таблиц
     */
    public function getTables(): array
    {
        return array_column(
            $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"),
            'name'
        );
    }

    // ════════════════════════════════════════════════════════
    //  ПРИВАТНЫЕ ХЕЛПЕРЫ
    // ════════════════════════════════════════════════════════

    private function buildSelect(string $table, array $where, array $options): array
    {
        $cols    = $options['columns'] ?? ['*'];
        $colsSql = is_array($cols) ? implode(', ', $cols) : $cols;

        $sql    = 'SELECT ' . $colsSql . ' FROM ' . $this->quoteTable($table);
        $params = [];

        [$whereSql, $whereParams] = $this->buildWhere($where);
        if ($whereSql) {
            $sql    .= ' WHERE ' . $whereSql;
            $params  = $whereParams;
        }

        if (!empty($options['order'])) {
            $sql .= ' ORDER BY ' . $options['order'];
        }

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . (int)$options['limit'];
        }

        if (!empty($options['offset'])) {
            $sql .= ' OFFSET ' . (int)$options['offset'];
        }

        return [$sql, $params];
    }

    private function buildWhere(array $where, string $prefix = ''): array
    {
        if (empty($where)) return ['', []];

        $parts  = [];
        $params = [];

        foreach ($where as $col => $val) {
            // Поддержка операторов: ['amount >' => 100]
            if (preg_match('/^(\w+)\s*(>=|<=|!=|>|<|LIKE|IN)$/i', $col, $m)) {
                $field    = $m[1];
                $operator = strtoupper($m[2]);

                if ($operator === 'IN' && is_array($val)) {
                    $phs      = [];
                    foreach ($val as $i => $v) {
                        $ph        = $prefix . $field . '_in' . $i;
                        $phs[]     = ':' . $ph;
                        $params[$ph] = $v;
                    }
                    $parts[] = $field . ' IN (' . implode(',', $phs) . ')';
                } else {
                    $ph              = $prefix . $field;
                    $parts[]         = $field . ' ' . $operator . ' :' . $ph;
                    $params[$ph]     = $val;
                }
            } elseif ($val === null) {
                $parts[] = $col . ' IS NULL';
            } else {
                $ph            = $prefix . preg_replace('/\W/', '_', $col);
                $parts[]       = $col . ' = :' . $ph;
                $params[$ph]   = $val;
            }
        }

        return [implode(' AND ', $parts), $params];
    }

    private function prepareData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $val) {
            $result[$key] = is_array($val) || is_object($val)
                ? json_encode($val, JSON_UNESCAPED_UNICODE)
                : $val;
        }
        return $result;
    }

    private function quoteTable(string $table): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('CQLite: недопустимое имя таблицы: ' . $table);
        }
        return '"' . $table . '"';
    }
}