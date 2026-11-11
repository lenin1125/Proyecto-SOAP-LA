<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/models/Habitacion.php';
require_once __DIR__.'/models/Reserva.php';
require_once __DIR__.'/auth.php';

$GLOBALS['soap_cuerpo_solicitud'] = (string) file_get_contents('php://input');
$habitacionModel = new Habitacion();
$reservaModel = new Reserva();
$server = new nusoap_server();
$namespace = 'urn:HotelService';
$server->configureWSDL('HotelService', $namespace);

function ejecutarOperacionHotel(string $operacion, callable $accion): mixed
{
    if (! validarAutenticacionSOAP()) {
        http_response_code(401);

        return new soap_fault('SOAP-ENV:Client', '', '401 Unauthorized: usuario o token invalido.');
    }

    if (! autorizarOperacionSOAP($operacion)) {
        http_response_code(403);

        return new soap_fault('SOAP-ENV:Client', '', '403 Forbidden: permisos insuficientes para ejecutar este método.');
    }

    try {
        return $accion();
    } catch (Throwable $error) {
        error_log($error->getMessage());

        return new soap_fault('SOAP-ENV:Server', '', 'No se pudo completar la operacion del hotel.');
    }
}

function registrarHabitacion($numero, $tipo, $precio): mixed
{
    global $habitacionModel;

    return ejecutarOperacionHotel('registrarHabitacion', fn () => $habitacionModel->registrar($numero, $tipo, $precio));
}

function consultarHabitacion($id): mixed
{
    global $habitacionModel;

    return ejecutarOperacionHotel('consultarHabitacion', fn () => json_encode($habitacionModel->consultar($id), JSON_THROW_ON_ERROR));
}

function listarHabitaciones(): mixed
{
    global $habitacionModel;

    return ejecutarOperacionHotel('listarHabitaciones', fn () => json_encode($habitacionModel->listar(), JSON_THROW_ON_ERROR));
}

function actualizarHabitacion($id, $tipo, $precio, $estado): mixed
{
    global $habitacionModel;

    return ejecutarOperacionHotel('actualizarHabitacion', fn () => $habitacionModel->actualizar($id, $tipo, $precio, $estado));
}

function registrarReserva($habitacionId, $huespedId, $fechaEntrada, $fechaSalida): mixed
{
    global $reservaModel;

    return ejecutarOperacionHotel('registrarReserva', fn () => $reservaModel->registrar($habitacionId, $huespedId, $fechaEntrada, $fechaSalida));
}

function consultarReservas(): mixed
{
    global $reservaModel;

    return ejecutarOperacionHotel('consultarReservas', fn () => json_encode($reservaModel->consultar(), JSON_THROW_ON_ERROR));
}

$operaciones = [
    'registrarHabitacion' => ['numero' => 'xsd:string', 'tipo' => 'xsd:string', 'precio' => 'xsd:float'],
    'consultarHabitacion' => ['id' => 'xsd:int'],
    'listarHabitaciones' => [],
    'actualizarHabitacion' => ['id' => 'xsd:int', 'tipo' => 'xsd:string', 'precio' => 'xsd:float', 'estado' => 'xsd:string'],
    'registrarReserva' => ['habitacionId' => 'xsd:int', 'huespedId' => 'xsd:int', 'fechaEntrada' => 'xsd:string', 'fechaSalida' => 'xsd:string'],
    'consultarReservas' => [],
];
foreach ($operaciones as $nombre => $parametros) {
    $server->register($nombre, $parametros, ['return' => 'xsd:string'], $namespace, $namespace.'#'.$nombre, 'rpc', 'encoded', $nombre);
}

$cuerpo = $GLOBALS['soap_cuerpo_solicitud'];
$esWsdl = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (isset($_GET['wsdl']) || isset($_GET['WSDL'])) && $cuerpo === '';

/**
 * Responde un fallo SOAP con el codigo HTTP exacto.
 *
 * NuSOAP convierte cualquier fault en HTTP 500, asi que los rechazos de
 * autenticacion y permisos se responden antes de llamar a service().
 */
function responderFalloSOAP(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    header('Content-Type: text/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>'
        .'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault>'
        .'<faultcode>soap:Client</faultcode><faultstring>'.htmlspecialchars($mensaje, ENT_QUOTES | ENT_XML1, 'UTF-8').'</faultstring>'
        .'</soap:Fault></soap:Body></soap:Envelope>';
    exit;
}

if (! $esWsdl) {
    // 1. Credenciales: usuario y token validos.
    if (! validarAutenticacionSOAP()) {
        responderFalloSOAP(401, '401 Unauthorized: usuario o token invalido.');
    }

    // 2. Operacion: debe existir y el rol debe permitirla.
    $operacionSolicitada = obtenerOperacionSOAP($cuerpo);
    if ($operacionSolicitada === null) {
        responderFalloSOAP(400, '400 Bad Request: solicitud SOAP invalida.');
    }
    if (! autorizarOperacionSOAP($operacionSolicitada)) {
        responderFalloSOAP(403, '403 Forbidden: permisos insuficientes para ejecutar este método.');
    }
}

$server->service($cuerpo);
