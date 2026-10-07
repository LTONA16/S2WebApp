<?php
/**
 * Configuración y conector a la base de datos MySQL.
 * Soporta conexión nativa PDO con fallback dinámico de host y MySQLi.
 */

// Intentar primero conexión local si está en el mismo servidor, o la IP fija
define('DB_HOST', '127.0.0.1');
define('DB_HOST_FALLBACK', '192.168.122.160');
define('DB_USER', 'app_web');
define('DB_PASS', 'ClaveApp123');
define('DB_NAME', 'demo_servidores2');
define('DB_PORT', 3306);

// Clase envoltorio para MySQLi en caso de que PDO MySQL no esté instalado
class MySQLiDBWrapper {
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    public function query(string $sql) {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new Exception('Error MySQLi query: ' . $this->mysqli->error);
        }
        return new MySQLiResultWrapper($result);
    }

    public function prepare(string $sql) {
        $paramMap = [];
        $transformedSql = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function($matches) use (&$paramMap) {
            $paramMap[] = ':' . $matches[1];
            return '?';
        }, $sql);

        $stmt = $this->mysqli->prepare($transformedSql);
        if (!$stmt) {
            throw new Exception('Error MySQLi prepare: ' . $this->mysqli->error);
        }

        return new MySQLiStmtWrapper($this->mysqli, $stmt, $paramMap);
    }

    public function lastInsertId(): int {
        return (int)$this->mysqli->insert_id;
    }
}

class MySQLiResultWrapper {
    private mysqli_result $result;

    public function __construct(mysqli_result $result) {
        $this->result = $result;
    }

    public function fetch() {
        return $this->result->fetch_assoc();
    }

    public function fetchAll(): array {
        $data = [];
        while ($row = $this->result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}

class MySQLiStmtWrapper {
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

function getDBConnection() {
    static $connection = null;

    if ($connection !== null) {
        return $connection;
    }

    $hosts = [DB_HOST, DB_HOST_FALLBACK];
    $pdoErrors = [];

    // 1. Intentar con PDO nativo
    if (extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers(), true)) {
        foreach ($hosts as $host) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, DB_PORT, DB_NAME);
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                $connection = $pdo;
                return $connection;
            } catch (PDOException $e) {
                $pdoErrors[] = "Host $host: " . $e->getMessage();
            }
        }
    }

    // 2. Intentar con MySQLi
    if (class_exists('mysqli')) {
        foreach ($hosts as $host) {
            try {
                $mysqli = @new mysqli($host, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                if (!$mysqli->connect_error) {
                    $mysqli->set_charset('utf8mb4');
                    $connection = new MySQLiDBWrapper($mysqli);
                    return $connection;
                }
            } catch (Throwable $e) {
                // Siguiente intento
            }
        }
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo conectar a la base de datos MySQL.',
        'details' => $pdoErrors
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
