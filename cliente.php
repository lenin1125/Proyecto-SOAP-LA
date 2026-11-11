<?php
// Carga el autoloader de Composer para disponibilizar la librería NuSOAP (apunta a vendor/autoload.php)
require_once __DIR__ . '/vendor/autoload.php';

// Define la URL donde se encuentra expuesto el contrato dinámico WSDL (apunta a server.php?wsdl)
$wsdl = 'http://hotel.test/server.php?wsdl';

// Instancia el cliente NuSOAP indicando la URL del WSDL y especificando 'true' para procesar la especificación WSDL
$cliente = new nusoap_client($wsdl, true);

// Comprueba si ocurrió un error al intentar conectarse o descargar el WSDL inicial
if ($err = $cliente->getError()) {
    
    // Detiene la ejecución del script y muestra el mensaje de falla de conexión con el cliente
    die("<b>Error de cliente NuSOAP:</b> " . $err);
}

$username = getenv('SOAP_USERNAME');
$token = getenv('SOAP_TOKEN');
if (! $username || ! $token) {
    exit('Configure SOAP_USERNAME y SOAP_TOKEN con el usuario y token emitidos por POST /api/login de Laravel.');
}
$ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
$cliente->setHeaders('<wsse:Security xmlns:wsse="'.$ns.'"><wsse:UsernameToken><wsse:Username>'
    .htmlspecialchars($username, ENT_QUOTES | ENT_XML1, 'UTF-8').'</wsse:Username><wsse:Password>'
    .htmlspecialchars($token, ENT_QUOTES | ENT_XML1, 'UTF-8').'</wsse:Password></wsse:UsernameToken></wsse:Security>');

// Declara la función auxiliar encargada de interpretar, formatear e imprimir las respuestas devueltas por el servidor
function mostrarRespuesta($cliente, $resultado) {
    
    // Evalúa si la respuesta del servidor retornó una excepción o regla de negocio violada ($cliente->fault)
    if ($cliente->fault) {
        
        // Extrae el mensaje de error de la clave 'faultstring' o codifica la estructura a texto
        $msg = is_array($resultado) ? ($resultado['faultstring'] ?? json_encode($resultado)) : $resultado;
        
        // Imprime en pantalla la advertencia formateada en color naranja sin detener la ejecución
        echo "<span style='color:orange;'><b>[Aviso/Excepción SOAP]:</b> " . htmlspecialchars($msg) . "</span><br><br>";
        
    // Evalúa si ocurrió un fallo crítico a nivel de transporte HTTP o red
    } elseif ($err = $cliente->getError()) {
        
        // Muestra el mensaje de error de conexión en color rojo
        echo "<span style='color:red;'><b>[Error de Conexion]:</b> " . htmlspecialchars($err) . "</span><br><br>";
        
    // Procesa el flujo cuando la respuesta fue exitosa
    } else {
        
        // Intenta decodificar el string resultante a un arreglo asociativo PHP si viene en formato JSON
        $decoded = is_string($resultado) ? json_decode($resultado, true) : null;
        
        // Verifica si la cadena era un JSON válido y si se convirtió en un array
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            
            // Imprime el arreglo de datos tabulado y estructurado dentro de una caja HTML <pre>
            echo "<pre style='background:#f8f9fa; padding:10px; border:1px solid #ddd; border-radius:5px;'>" . print_r($decoded, true) . "</pre><br>";
            
        } else {
            
            // Imprime mensajes de confirmación de texto plano en color verde
            echo "<span style='color:green;'><b>[Éxito]:</b> " . htmlspecialchars(is_array($resultado) ? json_encode($resultado) : $resultado) . "</span><br><br>";
        }
    }
}

// Imprime el encabezado HTML del panel de pruebas del cliente
echo "<h2>--- DEMOSTRACION SISTEMATICA CON NUSOAP ---</h2>";

// Imprime la etiqueta descriptiva del método 1
echo "<b>1. Metodo registrarHabitacion (Habitacion 103):</b><br>";

// Ejecuta la llamada SOAP 'registrarHabitacion' pasando sus argumentos (apunta a server.php -> registrarHabitacion)
$r1 = $cliente->call('registrarHabitacion', ['numero' => '103', 'tipo' => 'DELUXE', 'precio' => 210.00]);

// Invoca a la función de renderizado para mostrar la respuesta del registro
mostrarRespuesta($cliente, $r1);

// Imprime la etiqueta descriptiva del método 2
echo "<b>2. Metodo consultarHabitacion (ID 1):</b><br>";

// Ejecuta la llamada SOAP 'consultarHabitacion' indicando el ID deseado (apunta a server.php -> consultarHabitacion)
$r2 = $cliente->call('consultarHabitacion', ['id' => 1]);

// Formatea y despliega los datos de la habitación recuperada
mostrarRespuesta($cliente, $r2);

// Imprime la etiqueta descriptiva del método 3
echo "<b>3. Metodo listarHabitaciones:</b><br>";

// Ejecuta la llamada SOAP 'listarHabitaciones' sin parámetros de entrada (apunta a server.php -> listarHabitaciones)
$r3 = $cliente->call('listarHabitaciones', []);

// Renderiza la lista de habitaciones obtenidas desde MySQL
mostrarRespuesta($cliente, $r3);

// Imprime la etiqueta descriptiva del método 4
echo "<b>4. Método actualizarHabitacion (ID 1 a SUITE VIP):</b><br>";

// Ejecuta la llamada SOAP 'actualizarHabitacion' con los campos a modificar (apunta a server.php -> actualizarHabitacion)
$r4 = $cliente->call('actualizarHabitacion', ['id' => 1, 'tipo' => 'SUITE VIP', 'precio' => 200.00, 'estado' => 'DISPONIBLE']);

// Muestra la confirmación de la actualización en la BD
mostrarRespuesta($cliente, $r4);

// Imprime la etiqueta descriptiva del método 5
echo "<b>5. Método registrarReserva (01 al 05 de Diciembre 2027):</b><br>";

// Ejecuta la llamada 'registrarReserva' con parámetros de fecha válidos (apunta a server.php -> registrarReserva)
$r5 = $cliente->call('registrarReserva', ['habitacionId' => 1, 'huespedId' => 1, 'fechaEntrada' => '2027-12-01', 'fechaSalida' => '2027-12-05']);

// Muestra el resultado de la reserva creada exitosamente
mostrarRespuesta($cliente, $r5);

// Imprime la etiqueta descriptiva del escenario de validación 5b
echo "<b>5b. Validación Intento de Traslape (03 al 08 de Diciembre):</b><br>";

// Ejecuta un intento de reserva enviando fechas que chocan con la reserva previa (apunta a server.php -> registrarReserva)
$r5b = $cliente->call('registrarReserva', ['habitacionId' => 1, 'huespedId' => 1, 'fechaEntrada' => '2027-12-03', 'fechaSalida' => '2027-12-08']);

// Atrapa y muestra el aviso de excepción SOAP devuelto por la regla de negocio
mostrarRespuesta($cliente, $r5b);

// Imprime la etiqueta descriptiva del método 6
echo "<b>6. Metodo consultarReservas:</b><br>";

// Ejecuta la llamada 'consultarReservas' para traer el histórico (apunta a server.php -> consultarReservas)
$r6 = $cliente->call('consultarReservas', []);

// Renderiza en pantalla el arreglo decodificado con el detalle de las reservas
mostrarRespuesta($cliente, $r6);