<?php
/**
 * Configuración y conector a la base de datos MySQL.
 * Soporta PDO (recomendado) con fallback automático a MySQLi
 * para compatibilidad total con cualquier entorno PHP-FPM / php-mysql.
 * Test 2
 */

define('DB_HOST', '192.168.122.160');
define('DB_USER', 'app_web');
define('DB_PASS', 'ClaveApp123');
define('DB_NAME', 'demo_servidores2');
define('DB_PORT', 3306);

interface DBInterface {
    public function query(string $sql): array;
    public function prepare(string $sql): DBStatementInterface;
    public function lastInsertId(): int;
}

interface DBStatementInterface {
    public function execute(array $params = []): bool;
    public function fetch();
    public function fetchAll(): array;
}

// Implementación con PDO
class PDOWrapper implements DBInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function query(string $sql): array {
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function prepare(string $sql): DBStatementInterface {
        return new PDOStatementWrapper($this->pdo->prepare($sql));
    }

    public function lastInsertId(): int {
        return (int)$this->pdo->lastInsertId();
    }
}

class PDOStatementWrapper implements DBStatementInterface {
    private PDOStatement $stmt;

    public function __construct(PDOStatement $stmt) {
        $this->stmt = $stmt;
    }

    public function execute(array $params = []): bool {
        return $this->stmt->execute($params);
    }

    public function fetch() {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll(): array {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Implementación de respaldo con MySQLi
class MySQLiWrapper implements DBInterface {
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    public function query(string $sql): array {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new Exception('Error MySQLi query: ' . $this->mysqli->error);
        }
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function prepare(string $sql): DBStatementInterface {
        // Convierte parámetros nominales :param a posicionales ?
        $paramMap = [];
        $transformedSql = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function($matches) use (&$paramMap) {
            $paramMap[] = ':' . $matches[1];
            return '?';
        }, $sql);

        $stmt = $this->mysqli->prepare($transformedSql);
        if (!$stmt) {
            throw new Exception('Error MySQLi prepare: ' . $this->mysqli->error);
        }

        return new MySQLiStatementWrapper($this->mysqli, $stmt, $paramMap);
    }

    public function lastInsertId(): int {
        return (int)$this->mysqli->insert_id;
    }
}

class MySQLiStatementWrapper implements DBStatementInterface {
    private mysqli $mysqli;
    private mysqli_stmt $stmt;
    private array $paramMap;
    private $lastResult = null;

    public function __construct(mysqli $mysqli, mysqli_stmt $stmt, array $paramMap) {
        $this->mysqli = $mysqli;
        $this->stmt = $stmt;
        $this->paramMap = $paramMap;
    }

    public function execute(array $params = []): bool {
        if (!empty($params) && !empty($this->paramMap)) {
            $types = '';
            $values = [];
            foreach ($this->paramMap as $name) {
                $val = $params[$name] ?? null;
                $values[] = $val;
                if (is_int($val)) $types .= 'i';
                elseif (is_float($val)) $types .= 'd';
                else $types .= 's';
            }
            if (!empty($types)) {
                $this->stmt->bind_param($types, ...$values);
            }
        }
        $ok = $this->stmt->execute();
        $this->lastResult = $this->stmt->get_result();
        return $ok;
    }

    public function fetch() {
        if ($this->lastResult instanceof mysqli_result) {
            return $this->lastResult->fetch_assoc();
        }
        return null;
    }

    public function fetchAll(): array {
        $data = [];
        if ($this->lastResult instanceof mysqli_result) {
            while ($row = $this->lastResult->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
}

function getDBConnection(): DBInterface {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    // 1. Intentar PDO si el driver pdo_mysql está disponible
    if (extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers(), true)) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $db = new PDOWrapper($pdo);
            return $db;
        } catch (PDOException $e) {
            // Intentar con MySQLi si falla
        }
    }

    // 2. Intentar MySQLi si está disponible
    if (class_exists('mysqli')) {
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if ($mysqli->connect_error) {
                throw new Exception('Error de conexión MySQLi: ' . $mysqli->connect_error);
            }
            $mysqli->set_charset('utf8mb4');
            $db = new MySQLiWrapper($mysqli);
            return $db;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró ningún driver MySQL disponible (PDO MySQL o MySQLi).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
