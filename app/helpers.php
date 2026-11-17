<?php
/**
 * Funciones compartidas por la API (api.php) y los módulos de app/api/.
 *
 * Contenido:
 *   1. Respuestas y lectura de datos
 *   2. Sesiones (propietario de reserva temporal y administrador)
 *   3. Validaciones reutilizables
 *   4. Consultas repetidas en varios módulos
 */

/* ------------------------------------------------------------------ */
/* 1. Respuestas y lectura de datos                                    */
/* ------------------------------------------------------------------ */

/** Envía una respuesta JSON y termina el script. */
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Devuelve el cuerpo JSON de la petición como arreglo (vacío si no es válido). */
function inputData(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    return is_array($data) ? $data : [];
}

/** Registra una línea en la tabla `actividad` (historial del panel). */
function registrarActividad(PDO $pdo, string $descripcion): void
{
    $pdo->prepare('INSERT INTO actividad (descripcion) VALUES (?)')
        ->execute([$descripcion]);
}

/* ------------------------------------------------------------------ */
/* 2. Sesiones                                                         */
/* ------------------------------------------------------------------ */

/**
 * Identifica de forma anónima al navegador que está reservando.
 * El portal envía un UUID en el encabezado X-Reservation-Owner; en la base
 * solo se guarda su hash SHA-256.
 */
function reservationOwner(): string
{
    $token = (string) ($_SERVER['HTTP_X_RESERVATION_OWNER'] ?? '');

    if (!preg_match('/^[a-f0-9-]{36}$/i', $token)) {
        respond(
            ['error' => 'No se pudo identificar esta selección. Actualizá la página e intentá nuevamente.'],
            400
        );
    }

    return hash('sha256', strtolower($token));
}

/** Corta la petición si no hay un administrador con sesión iniciada. */
function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        respond(['error' => 'Iniciá sesión como administrador.'], 401);
    }
}

/* ------------------------------------------------------------------ */
/* 3. Validaciones reutilizables                                       */
/* ------------------------------------------------------------------ */

/** Expresión regular de una hora válida en formato HH:MM. */
const REGEX_HORA = '/^([01]\d|2[0-3]):[0-5]\d$/';

/** Valores permitidos en el campo obra_social. */
const OBRAS_SOCIALES_VALIDAS = ['', 'IOSCOR', 'OSECAC', 'ODFE', 'PAMI', 'Otras', 'Ninguna'];

/**
 * Valida la obra social recibida y devuelve [obra_social, obra_social_otra].
 * Si el valor no es válido responde con error 422.
 */
function validarObraSocial(array $datos, string $mensajeFaltaNombre): array
{
    $obra = trim((string) ($datos['obra_social'] ?? ''));
    $otra = trim((string) ($datos['obra_social_otra'] ?? ''));

    if (!in_array($obra, OBRAS_SOCIALES_VALIDAS, true)) {
        respond(['error' => 'Elegí una obra social válida.'], 422);
    }
    if ($obra === 'Otras' && $otra === '') {
        respond(['error' => $mensajeFaltaNombre], 422);
    }
    if ($obra !== 'Otras') {
        $otra = '';
    }

    return [$obra, $otra];
}

/**
 * Valida nombre, apellido, DNI, teléfono y email de un paciente cargado
 * desde el panel (alta y edición usan las mismas reglas).
 */
function validarDatosPaciente(array $d): void
{
    foreach (['nombre', 'apellido', 'dni'] as $campo) {
        if (trim((string) ($d[$campo] ?? '')) === '') {
            respond(['error' => 'Nombre, apellido y DNI son obligatorios.'], 422);
        }
    }
    if (!preg_match('/^[0-9]{7,8}$/', (string) $d['dni'])) {
        respond(['error' => 'El DNI debe contener únicamente números (7 u 8 dígitos).'], 422);
    }

    $telefono = trim((string) ($d['telefono'] ?? ''));
    if ($telefono !== '' && !preg_match('/^[0-9]{8,15}$/', (string) $d['telefono'])) {
        respond(['error' => 'El teléfono debe contener únicamente números (entre 8 y 15 dígitos).'], 422);
    }

    $email = trim((string) ($d['email'] ?? ''));
    if ($email !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Ingresá un correo electrónico válido.'], 422);
    }
}

/**
 * Convierte la lista de horarios enviada por el panel en
 * [dia_semana => [[inicio, fin], ...]] y valida cada franja:
 * día entre 1 y 7, horas válidas, inicio < fin, máximo dos franjas por
 * día y sin superposición.
 *
 * @param string $mensajeRangoInvalido Texto del error 422 para una franja mal formada.
 */
function parsearHorarios(array $horarios, string $mensajeRangoInvalido): array
{
    $dias = [];

    foreach ($horarios as $h) {
        $dia = (int) ($h['dia'] ?? 0);
        $inicio = trim((string) ($h['hora_inicio'] ?? ''));
        $fin = trim((string) ($h['hora_fin'] ?? ''));

        if (
            $dia < 1 || $dia > 7
            || !preg_match(REGEX_HORA, $inicio)
            || !preg_match(REGEX_HORA, $fin)
            || $inicio >= $fin
        ) {
            respond(['error' => $mensajeRangoInvalido], 422);
        }

        $dias[$dia][] = [$inicio, $fin];
    }

    foreach ($dias as $dia => $franjas) {
        if (count($franjas) > 2) {
            respond(['error' => 'Se permiten como máximo dos franjas por día.'], 422);
        }

        usort($franjas, fn($a, $b) => strcmp($a[0], $b[0]));

        for ($i = 1; $i < count($franjas); $i++) {
            if ($franjas[$i][0] < $franjas[$i - 1][1]) {
                respond(['error' => 'Los horarios del mismo día no pueden superponerse.'], 422);
            }
        }

        $dias[$dia] = $franjas;
    }

    return $dias;
}

/** Normaliza una lista de IDs recibida del panel (enteros, sin repetidos ni ceros). */
function normalizarIds(mixed $valor): array
{
    return array_values(array_unique(array_filter(array_map('intval', (array) $valor))));
}

/* ------------------------------------------------------------------ */
/* 4. Consultas repetidas en varios módulos                            */
/* ------------------------------------------------------------------ */

/**
 * Busca el par profesional/especialidad activo a partir de sus nombres.
 * Devuelve ['profesional_id' => ..., 'especialidad_id' => ...] o null.
 */
function buscarProfesionalYEspecialidad(PDO $pdo, string $profesional, string $especialidad): ?array
{
    $q = $pdo->prepare(
        'SELECT p.id AS profesional_id, e.id AS especialidad_id
           FROM profesionales p
           JOIN profesional_especialidades pe ON pe.profesional_id = p.id AND pe.activo = 1
           JOIN especialidades e ON e.id = pe.especialidad_id AND e.activa = 1
          WHERE p.nombre = ? AND e.nombre = ? AND p.activo = 1
          LIMIT 1'
    );
    $q->execute([$profesional, $especialidad]);
    $par = $q->fetch();

    return $par ?: null;
}

/**
 * Duración (en minutos) de la franja de agenda que contiene el horario pedido,
 * o 0 si ese horario no pertenece a la agenda habilitada del profesional.
 */
function duracionDeFranja(PDO $pdo, int $profesionalId, int $especialidadId, int $diaSemana, string $hora): int
{
    $q = $pdo->prepare(
        'SELECT duracion_minutos
           FROM horarios_profesionales
          WHERE profesional_id = ? AND especialidad_id = ? AND dia_semana = ? AND activo = 1
            AND hora_inicio <= ?
            AND ADDTIME(?, SEC_TO_TIME(duracion_minutos * 60)) <= hora_fin
          ORDER BY duracion_minutos
          LIMIT 1'
    );
    $q->execute([$profesionalId, $especialidadId, $diaSemana, $hora, $hora]);

    return (int) $q->fetchColumn();
}

/**
 * Bloquea la fila del profesional (SELECT ... FOR UPDATE). Sirve para que
 * dos solicitudes simultáneas sobre la misma agenda se ejecuten una a la vez.
 * Debe llamarse dentro de una transacción.
 */
function bloquearProfesional(PDO $pdo, int $profesionalId): bool
{
    $q = $pdo->prepare('SELECT id FROM profesionales WHERE id = ? AND activo = 1 FOR UPDATE');
    $q->execute([$profesionalId]);

    return (bool) $q->fetchColumn();
}

/** Registra (o actualiza) la notificación "turno cancelado" para el panel. */
function notificarTurnoCancelado(PDO $pdo, int $turnoId): void
{
    $q = $pdo->prepare(
        "SELECT CONCAT(pa.nombre, ' ', pa.apellido) AS paciente,
                e.nombre AS especialidad,
                DATE_FORMAT(t.fecha, '%d/%m/%Y') AS fecha,
                TIME_FORMAT(t.hora, '%H:%i') AS hora
           FROM turnos t
           JOIN pacientes pa ON pa.id = t.paciente_id
           JOIN especialidades e ON e.id = t.especialidad_id
          WHERE t.id = ?"
    );
    $q->execute([$turnoId]);
    $evento = $q->fetch();

    if (!$evento) {
        return;
    }

    $mensaje = 'Turno cancelado por ' . $evento['paciente'] . ' · ' . $evento['especialidad']
        . ' · ' . $evento['fecha'] . ' ' . $evento['hora'];

    $pdo->prepare(
        "INSERT INTO notificaciones (turno_id, tipo, mensaje)
         VALUES (?, 'turno_cancelado', ?)
         ON DUPLICATE KEY UPDATE mensaje = VALUES(mensaje), leida = 0, creado_en = CURRENT_TIMESTAMP"
    )->execute([$turnoId, $mensaje]);
}
