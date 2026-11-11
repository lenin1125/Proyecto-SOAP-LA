<?php
// Declara la función global encargada de gestionar y entregar la conexión activa a MySQL
function getConexion() {

    // Define el servidor de base de datos local (localhost / 127.0.0.1)
    $host = 'localhost';

    // Define el nombre de la base de datos del proyecto ('hotel_db')
    $db   = 'hotel_db';

    // Define el usuario de acceso a la base de datos MySQL
    $user = 'root';

    // Define la contraseña del usuario de MySQL (vacía por defecto en entonos locales como Laragon)
    $pass = '123456';

    // Inicia el bloque de control para intentar establecer la conexión
    try {

        // Instancia la clase PDO pasando la cadena DSN de MySQL, charset utf8, credenciales y opciones de configuración
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [

            // Configura PDO para que cualquier error SQL lance automáticamente una excepción de tipo PDOException
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Establece que el modo de lectura por defecto retorne arreglos asociativos ['columna' => 'valor']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Retorna el objeto de conexión PDO activo hacia los modelos (Habitacion.php y Reserva.php)
        return $pdo;

    } catch (PDOException $e) {

        // En caso de falla de conexión (servidor apagado, credenciales erróneas), convierte la falla en una excepción SOAP estandarizada
        throw new SoapFault("Server", "Error de conexion a la BD: " . $e->getMessage());
    }
}