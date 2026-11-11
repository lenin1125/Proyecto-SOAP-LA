<?php
// Comprueba si existe el archivo de conexión dentro de la carpeta config (apunta a config/conexion.php)[cite: 4]
if (file_exists(__DIR__ . '/../config/conexion.php')) {
    
    // Incluye la configuración de la base de datos desde config/conexion.php[cite: 4]
    require_once __DIR__ . '/../config/conexion.php';

} else {
    
    // Carga el archivo de conexión en la raíz como método alternativo de respaldo[cite: 4]
    require_once __DIR__ . '/../conexion.php';
}

// Carga la clase modelo Habitacion para reutilizar su método de validación (apunta a models/Habitacion.php)[cite: 4]
require_once __DIR__ . '/Habitacion.php';

// Declara la clase modelo 'Reserva' para gestionar las operaciones de reservas[cite: 4]
class Reserva {

    // Método para registrar una nueva reserva validando existencia de habitación, coherencia de fechas y traslapes[cite: 4]
    public function registrar($habitacionId, $huespedId, $fechaEntrada, $fechaSalida) {
        
        // Obtiene la instancia PDO invocando la función getConexion() de conexion.php[cite: 4]
        $db = getConexion();
        
        // Instancia el modelo Habitacion para verificar que la habitación a reservar realmente exista en la BD[cite: 4]
        $habitacion = new Habitacion();
        
        // Ejecuta el método consultar; si el ID de habitación no existe, este método lanzará una excepción inmediatamente[cite: 4]
        $habitacion->consultar($habitacionId);

        // Convierte las cadenas de texto de las fechas a marcas de tiempo Unix y verifica que la fecha de salida sea posterior[cite: 4]
        if (strtotime($fechaSalida) <= strtotime($fechaEntrada)) {
            
            // Lanza una excepción si la fecha de salida es menor o igual a la de entrada[cite: 4]
            throw new Exception("La fecha de salida debe ser strictly posterior a la fecha de entrada.");
        }

        // Define la consulta SQL para detectar colisiones o traslapes de fechas en reservas activas para la misma habitación[cite: 4]
        $sqlCruce = "SELECT COUNT(*) FROM reserva 
                     WHERE habitacion_id = ? 
                       AND estado = 'ACTIVA' 
                       AND (fecha_entrada < ? AND fecha_salida > ?)";
        
        // Prepara la consulta de verificación de traslape contra MySQL[cite: 4]
        $stmtCruce = $db->prepare($sqlCruce);
        
        // Ejecuta la verificación vinculando el ID de habitación, la fecha de salida y la fecha de entrada[cite: 4]
        $stmtCruce->execute([$habitacionId, $fechaSalida, $fechaEntrada]);

        // Evalúa si el número de reservas encontradas que se cruzan es mayor a cero[cite: 4]
        if ($stmtCruce->fetchColumn() > 0) {
            
            // Lanza una excepción notificando que la habitación no está disponible para ese rango de fechas[cite: 4]
            throw new Exception("La habitacion ID $habitacionId ya tiene una reserva activa que se cruza con las fechas seleccionadas.");
        }

        // Prepara la sentencia SQL para insertar la nueva reserva con el estado 'ACTIVA' por defecto[cite: 4]
        $stmt = $db->prepare("INSERT INTO reserva (habitacion_id, huesped_id, fecha_entrada, fecha_salida, estado) VALUES (?, ?, ?, ?, 'ACTIVA')");
        
        // Ejecuta la inserción en la tabla 'reserva' asignando los parámetros correspondientes[cite: 4]
        $stmt->execute([$habitacionId, $huespedId, $fechaEntrada, $fechaSalida]);

        // Retorna la confirmación del registro exitoso incluyendo el ID de reserva generado mediante lastInsertId()[cite: 4]
        return "Reserva realizada con exito. ID de Reserva: " . $db->lastInsertId();
    }

    // Método para consultar y obtener el detalle completo de todas las reservas registradas[cite: 4]
    public function consultar() {
        
        // Obtiene la conexión activa PDO con la base de datos[cite: 4]
        $db = getConexion();
        
        // Define la consulta SQL relacional con JOINs para unir las tablas 'reserva', 'habitacion' y 'huesped'[cite: 4]
        $sql = "SELECT r.id, r.fecha_entrada, r.fecha_salida, r.estado, 
                       h.numero AS numero_habitacion, h.tipo AS tipo_habitacion,
                       g.nombre AS nombre_huesped, g.documento
                FROM reserva r
                INNER JOIN habitacion h ON r.habitacion_id = h.id
                INNER JOIN huesped g ON r.huesped_id = g.id";
        
        // Ejecuta la consulta SQL directamente en la base de datos[cite: 4]
        $stmt = $db->query($sql);
        
        // Retorna un arreglo asociativo con todos los datos combinados de las reservas, habitaciones y huéspedes[cite: 4]
        return $stmt->fetchAll();
    }
}