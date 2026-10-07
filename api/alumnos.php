<?php
/**
 * API REST para la gestión de alumnos (CRUD)
 * Soporta operaciones GET, POST, PUT, DELETE
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Función auxiliar para responder JSON
function sendResponse(bool $success, string $message, $data = null, int $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener payload (soporta JSON en php://input y form-data en $_POST)
function getRequestData(): array {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    if (is_array($jsonData)) {
        return $jsonData;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    // Para PUT o DELETE enviados como form-url-encoded
    parse_str($rawInput, $parsed);
    return is_array($parsed) ? $parsed : [];
}

$inputData = getRequestData();

// Manejo de métodos sobreescritos vía _method o action
if ($method === 'POST') {
    if (isset($inputData['_method'])) {
        $method = strtoupper($inputData['_method']);
    } elseif (isset($inputData['action'])) {
        if ($inputData['action'] === 'update') $method = 'PUT';
        if ($inputData['action'] === 'delete') $method = 'DELETE';
    }
}

try {
    switch ($method) {
        case 'GET':
            // Obtener uno específico o todos los registros
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    sendResponse(false, 'Identificador de alumno inválido.', null, 400);
                }

                $stmt = $pdo->prepare('SELECT id, nombre, carrera FROM alumnos WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $alumno = $stmt->fetch();

                if (!$alumno) {
                    sendResponse(false, 'Alumno no encontrado.', null, 404);
                }

                sendResponse(true, 'Alumno obtenido con éxito.', $alumno, 200);
            } else {
                $stmt = $pdo->query('SELECT id, nombre, carrera FROM alumnos ORDER BY id DESC');
                $alumnos = $stmt->fetchAll();
                sendResponse(true, 'Lista de alumnos obtenida.', $alumnos, 200);
            }
            break;

        case 'POST':
            // Crear alumno
            $nombre = trim($inputData['nombre'] ?? '');
            $carrera = trim($inputData['carrera'] ?? '');

            // Validaciones en backend
            if ($nombre === '' || $carrera === '') {
                sendResponse(false, 'El nombre y la carrera son obligatorios.', null, 400);
            }

            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
                sendResponse(false, 'El nombre debe tener entre 3 y 100 caracteres.', null, 400);
            }

            if (mb_strlen($carrera) < 3 || mb_strlen($carrera) > 100) {
                sendResponse(false, 'La carrera debe tener entre 3 y 100 caracteres.', null, 400);
            }

            $stmt = $pdo->prepare('INSERT INTO alumnos (nombre, carrera) VALUES (:nombre, :carrera)');
            $stmt->execute([
                ':nombre'  => $nombre,
                ':carrera' => $carrera
            ]);

            $newId = (int)$pdo->lastInsertId();
            sendResponse(true, 'Alumno registrado con éxito.', [
                'id'      => $newId,
                'nombre'  => $nombre,
                'carrera' => $carrera
            ], 201);
            break;

        case 'PUT':
            // Actualizar alumno
            $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
            $nombre = trim($inputData['nombre'] ?? '');
            $carrera = trim($inputData['carrera'] ?? '');

            if (!$id) {
                sendResponse(false, 'Identificador de alumno no proporcionado o inválido.', null, 400);
            }

            if ($nombre === '' || $carrera === '') {
                sendResponse(false, 'El nombre y la carrera son obligatorios.', null, 400);
            }

            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
                sendResponse(false, 'El nombre debe tener entre 3 y 100 caracteres.', null, 400);
            }

            if (mb_strlen($carrera) < 3 || mb_strlen($carrera) > 100) {
                sendResponse(false, 'La carrera debe tener entre 3 y 100 caracteres.', null, 400);
            }

            // Verificar si el registro existe
            $checkStmt = $pdo->prepare('SELECT id FROM alumnos WHERE id = :id');
            $checkStmt->execute([':id' => $id]);
            if (!$checkStmt->fetch()) {
                sendResponse(false, 'El alumno especificado no existe.', null, 404);
            }

            $updateStmt = $pdo->prepare('UPDATE alumnos SET nombre = :nombre, carrera = :carrera WHERE id = :id');
            $updateStmt->execute([
                ':nombre'  => $nombre,
                ':carrera' => $carrera,
                ':id'      => $id
            ]);

            sendResponse(true, 'Alumno actualizado correctamente.', [
                'id'      => $id,
                'nombre'  => $nombre,
                'carrera' => $carrera
            ], 200);
            break;

        case 'DELETE':
            // Eliminar alumno (soporta id por query string o payload)
            $id = filter_var($_GET['id'] ?? ($inputData['id'] ?? null), FILTER_VALIDATE_INT);

            if (!$id) {
                sendResponse(false, 'ID de alumno no válido.', null, 400);
            }

            $checkStmt = $pdo->prepare('SELECT nombre FROM alumnos WHERE id = :id');
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch();

            if (!$existing) {
                sendResponse(false, 'El alumno que intentas eliminar no existe.', null, 404);
            }

            $delStmt = $pdo->prepare('DELETE FROM alumnos WHERE id = :id');
            $delStmt->execute([':id' => $id]);

            sendResponse(true, 'Alumno "' . $existing['nombre'] . '" eliminado con éxito.', ['id' => $id], 200);
            break;

        default:
            sendResponse(false, 'Método HTTP no permitido.', null, 405);
            break;
    }
} catch (PDOException $e) {
    sendResponse(false, 'Error en la base de datos: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Ocurrió un error inesperado: ' . $e->getMessage(), null, 500);
}
