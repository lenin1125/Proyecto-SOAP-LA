<?php
// Comprueba si existe el archivo de conexión dentro de la carpeta config (apunta a config/conexion.php)[cite: 3]
if (file_exists(__DIR__ . '/../config/conexion.php')) {
    
    // Incluye la configuración de la base de datos desde config/conexion.php[cite: 3]
    require_once __DIR__ . '/../config/conexion.php';

} else {
    
    // Carga el archivo de conexión en la raíz como método alternativo de respaldo[cite: 3]
    require_once __DIR__ . '/../conexion.php';
}

// Declara la clase modelo 'Habitacion' para la gestión de datos de la entidad habitación[cite: 3]
class Habitacion {

    // Método para registrar una nueva habitación recibiendo número, tipo y precio[cite: 3]
    public function registrar($numero, $tipo, $precio) {
        
        // Obtiene la instancia de la conexión PDO invocando la función getConexion() de conexion.php[cite: 3]
        $db = getConexion();
        
        // Prepara la consulta SQL para verificar si el número de habitación ya existe en MySQL[cite: 3]
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM habitacion WHERE numero = ?");
        
        // Ejecuta la consulta pasando el número de habitación como parámetro seguro[cite: 3]
        $stmtCheck->execute([$numero]);
        
        // Evalúa si el conteo es mayor a cero, lo que indica que el número está duplicado[cite: 3]
        if ($stmtCheck->fetchColumn() > 0) {
            
            // Lanza una excepción que server.php interceptará para retornar una falla SOAP[cite: 3]
            throw new Exception("El número de habitacion '$numero' ya esta registrado.");
        }

        // Prepara la sentencia SQL de inserción asignando el estado 'DISPONIBLE' por defecto[cite: 3]
        $stmt = $db->prepare("INSERT INTO habitacion (numero, tipo, precio, estado) VALUES (?, ?, ?, 'DISPONIBLE')");
        
        // Ejecuta la inserción con parámetros preparados para prevenir inyecciones SQL[cite: 3]
        $stmt->execute([$numero, $tipo, $precio]);
        
        // Devuelve el mensaje de confirmación incluyendo el ID autogenerado mediante lastInsertId()[cite: 3]
        return "Habitacion $numero registrada exitosamente con el ID: " . $db->lastInsertId();
    }

    // Método para consultar una habitación específica filtrada por su clave primaria ID[cite: 3]
    public function consultar($id) {
        
        // Obtiene la conexión PDO desde conexion.php[cite: 3]
        $db = getConexion();
        
        // Prepara la instrucción SQL de selección filtrando por el ID recibido[cite: 3]
        $stmt = $db->prepare("SELECT * FROM habitacion WHERE id = ?");
        
        // Ejecuta la consulta binding el parámetro ID[cite: 3]
        $stmt->execute([$id]);
        
        // Extrae el registro encontrado en un arreglo asociativo[cite: 3]
        $habitacion = $stmt->fetch();

        // Evalúa si la base de datos no devolvió ningún registro para ese ID[cite: 3]
        if (!$habitacion) {
            
            // Lanza una excepción indicando que la habitación solicitada no existe[cite: 3]
            throw new Exception("No se encontro ninguna habitacion con el ID $id.");
        }
        
        // Retorna el arreglo asociativo con los datos de la habitación recuperada[cite: 3]
        return $habitacion;
    }

    // Método para obtener la lista completa de todas las habitaciones guardadas[cite: 3]
    public function listar() {
        
        // Obtiene la conexión activa con la base de datos[cite: 3]
        $db = getConexion();
        
        // Ejecuta la consulta SQL para seleccionar todos los registros de la tabla habitacion[cite: 3]
        $stmt = $db->query("SELECT * FROM habitacion");
        
        // Retorna un arreglo matricial con la totalidad de los registros encontrados[cite: 3]
        return $stmt->fetchAll();
    }

    // Método para modificar los datos de una habitación existente[cite: 3]
    public function actualizar($id, $tipo, $precio, $estado) {
        
        // Obtiene la conexión activa de PDO[cite: 3]
        $db = getConexion();
        
        // Llama al método interno consultar() para validar que el ID exista antes de editar[cite: 3]
        $this->consultar($id);

        // Prepara la sentencia UPDATE para cambiar los valores de tipo, precio y estado[cite: 3]
        $stmt = $db->prepare("UPDATE habitacion SET tipo = ?, precio = ?, estado = ? WHERE id = ?");
        
        // Ejecuta la actualización asignando los datos actualizados a la consulta[cite: 3]
        $stmt->execute([$tipo, $precio, $estado, $id]);
        
        // Devuelve el mensaje de confirmación de la actualización realizada[cite: 3]
        return "Habitacion ID $id actualizada correctamente.";
    }
}