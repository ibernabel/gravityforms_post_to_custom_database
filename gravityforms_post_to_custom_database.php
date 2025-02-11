<?php

/**
 * Plugin Name: Gravity Forms - Post to Custom Database
 * Plugin URI: https://github.com/ibernabel
 * Description: Plugin para capturar datos de solicitudes de préstamos desde Gravity Forms y enviarlos a la base de datos del sistema de verificación.
 * Version: 2.3.1
 * Author: Idequel Bernabel
 * Author URI: https://github.com/ibernabel
 * Requires at least: 5.5
 * Tested up to: 6.7.1
 *
 * Text Domain: Gravity Forms - Post to Custom Database
 * Domain Path: /languages/
 */

defined('ABSPATH') or die('Direct access not allowed');

// Check if Gravity Forms is active
add_action('plugins_loaded', function () {
  if (!class_exists('GFForms')) {
    add_action('admin_notices', function () {
      echo '<div class="error"><p><strong>Error:</strong> Gravity Forms - Post to Custom Database requiere que Gravity Forms esté instalado y activado.</p></div>';
    });

    // 🚨 Detener la ejecución del plugin
    deactivate_plugins(plugin_basename(__FILE__)); // Desactiva el plugin
    return;
  }
});

class GravityFormsPostToDatabase
{
  private const FORMS_ID = [15, 28, 29];
  private const PROVINCIAS_PERMITIDAS = ['Distrito Nacional', 'Santo Domingo'];
  private const LOG_FILE = WP_CONTENT_DIR . '/debug-gf-database.log';

  private $db_config;
  private $wpdb;
  private $db_connection;
  private static $instance = null;

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;

    // Register activation hook
    register_activation_hook(__FILE__, [$this, 'activatePlugin']);

    // Add menu item for database connection test
    add_action('admin_menu', [$this, 'addAdminMenu']);

    try {
      $this->loadConfiguration();
      $this->verifyConfiguration();
      $this->testDatabaseConnection();
      $this->initializeHooks();
    } catch (Exception $e) {
      $this->logError("Initialization Error: " . $e->getMessage());
      add_action('admin_notices', function () use ($e) {
        echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
      });
    }
  }

  public function addAdminMenu()
  {
    add_submenu_page(
      'tools.php',
      'GF Database Test',
      'GF Database Test',
      'manage_options',
      'gf-database-test',
      [$this, 'databaseTestPage']
    );
  }

  public function databaseTestPage()
  {
    echo '<div class="wrap">';
    echo '<h2>Database Connection Test</h2>';

    try {
      $this->testDatabaseConnection();
      echo '<div class="notice notice-success"><p>Database connection successful!</p></div>';

      // Show MySQL error log
      $this->displayMySQLErrors();

      // Show our custom debug log
      $this->displayCustomLog();
    } catch (Exception $e) {
      echo '<div class="error"><p>Connection failed: ' . esc_html($e->getMessage()) . '</p></div>';
    }

    echo '</div>';
  }

  private function testDatabaseConnection()
  {
    try {
      $this->db_connection = new mysqli(
        $this->db_config['host'],
        $this->db_config['user'],
        $this->db_config['password'],
        $this->db_config['database']
      );

      if ($this->db_connection->connect_error) {
        throw new Exception("Database connection failed: " . $this->db_connection->connect_error);
      }

      // Test if we can query the formulario table
      $result = $this->db_connection->query("SHOW TABLES LIKE 'formulario'");
      if ($result->num_rows === 0) {
        throw new Exception("Table 'formulario' does not exist in the database.");
      }

      $this->logMessage("Database connection test successful");
      return true;
    } catch (Exception $e) {
      $this->logError("Database connection test failed: " . $e->getMessage());
      throw $e;
    }
  }

  private function displayMySQLErrors()
  {
    try {
      $query = "SHOW GLOBAL STATUS LIKE 'MySQL%'";
      $result = $this->db_connection->query($query);

      echo '<h3>MySQL Status</h3>';
      echo '<table class="widefat">';
      echo '<thead><tr><th>Variable</th><th>Value</th></tr></thead>';
      while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . esc_html($row['Variable_name']) . '</td>';
        echo '<td>' . esc_html($row['Value']) . '</td>';
        echo '</tr>';
      }
      echo '</table>';
    } catch (Exception $e) {
      echo '<div class="error"><p>Error retrieving MySQL status: ' . esc_html($e->getMessage()) . '</p></div>';
    }
  }

  private function displayCustomLog()
  {
    if (file_exists(self::LOG_FILE)) {
      echo '<h3>Recent Debug Log</h3>';
      echo '<div style="background: #fff; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: scroll;">';
      $logs = array_slice(file(self::LOG_FILE), -50); // Last 50 lines
      foreach ($logs as $log) {
        echo esc_html($log) . '<br>';
      }
      echo '</div>';
    }
  }

  private function logMessage($message)
  {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] INFO: {$message}\n";
    error_log($logMessage, 3, self::LOG_FILE);
  }

  private function logError($message)
  {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ERROR: {$message}\n";
    error_log($logMessage, 3, self::LOG_FILE);
  }

  public function activatePlugin()
  {
    // Only set options if they don't exist
    if (!get_option('gf_db_host')) {
      // Transfer values from constants if they exist
      $host = defined('LOANS_DB_HOST') ? constant('LOANS_DB_HOST') : '';
      $user = defined('LOANS_DB_USER') ? constant('LOANS_DB_USER') : '';
      $password = defined('LOANS_DB_PASSWORD') ? constant('LOANS_DB_PASSWORD') : '';
      $database = defined('LOANS_DB_NAME') ? constant('LOANS_DB_NAME') : '';

      // Store in WordPress options
      update_option('gf_db_host', $host);
      update_option('gf_db_user', $user);
      update_option('gf_db_password', $password);
      update_option('gf_db_name', $database);
    }


    add_action('admin_notices', function () {
      echo '<div class="notice notice-success"><p>Plugin activated successfully!</p></div>';
    });
  }

  private function loadConfiguration()
  {
    // Load from WordPress options
    $this->db_config = [
      'host'     => get_option('gf_db_host'),
      'user'     => get_option('gf_db_user'),
      'password' => get_option('gf_db_password'),
      'database' => get_option('gf_db_name')
    ];

    //$this->showDebugInformation();
  }

  private function showDebugInformation()
  {
    // Debug information
    add_action('admin_notices', function () {
      echo '<div class="notice notice-info is-dismissible">';
      echo '<p>Configuration Debug (Using WordPress Options):</p>';
      echo '<pre>';
      echo "1. WordPress Options Values:\n";
      echo "Host: " . $this->db_config['host'] . "\n";
      echo "User: " . $this->db_config['user'] . "\n";
      echo "Database: " . $this->db_config['database'] . "\n";

      echo "\n2. Verification:\n";
      echo "Host option exists: " . (get_option('gf_db_host') !== false ? 'Yes' : 'No') . "\n";
      echo "Host empty: " . (empty($this->db_config['host']) ? 'Yes' : 'No') . "\n";
      echo "Host length: " . strlen($this->db_config['host']) . "\n";
      echo '</pre>';
      echo '</div>';
    });
  }

  private function verifyConfiguration()
  {
    $missing = [];
    foreach ($this->db_config as $key => $value) {
      if (empty($value) && $value !== '0') {
        $missing[] = $key;
      }
    }

    if (!empty($missing)) {
      throw new Exception('Missing required database configuration: ' . implode(', ', $missing));
    }
  }


  private function initializeHooks()
  {
    add_action('gform_after_submission', [$this, 'procesarFormulario'], 10, 2);
  }

  public function procesarFormulario($entry, $form)
  {
    if (!in_array($entry['form_id'], self::FORMS_ID)) {
      return;
    }

    try {
      $this->logMessage("Processing form submission for form ID: " . $entry['form_id']);

      $datos = $this->prepararDatos($entry);
      $this->logMessage("Data prepared successfully: " . json_encode($datos));

      if (!in_array($datos['provincia_vivienda'], self::PROVINCIAS_PERMITIDAS)) {
        $datos = $this->rechazarPorProvincia($datos);
        $this->logMessage("Application rejected due to province: " . $datos['provincia_vivienda']);
      }

      $datos['ced_repetida'] = $this->verificarCedulaRepetida($datos['cedula']);

      // Log the SQL query before execution
      $this->logMessage("Attempting to insert data into database");
      $result = $this->insertarDatos($datos);

      if ($result) {
        $this->logMessage("Data inserted successfully. Insert ID: " . $this->wpdb->insert_id);
      } else {
        $this->logError("Database insert failed. MySQL Error: " . $this->wpdb->last_error);
        throw new Exception("Failed to insert data into database");
      }
    } catch (Exception $e) {
      $this->logError("Error processing form: " . $e->getMessage());
      $this->logError("MySQL Error: " . $this->wpdb->last_error);
      $this->logError("Last SQL Query: " . $this->wpdb->last_query);

      add_action('admin_notices', function () use ($e) {
        printf(
          '<div class="error"><p>%s</p></div>',
          esc_html("Error processing form submission: " . $e->getMessage())
        );
      });
    }
  }

  private function prepararDatos($entry)
  {
    return [
      'nombre_y_apellido' => $this->sanitizeAndFormat($entry["21.3"], 'ucwords'),
      'cedula' => $this->sanitizeAndFormat($entry[29]),
      'telefono_celular' => $this->sanitizeAndFormat($entry[23]),
      'telefono_casa' => $this->sanitizeAndFormat($entry[24]),
      'correo_electronico' => $this->sanitizeAndFormat($entry[30], 'strtolower'),
      'direccion_vivienda' => $this->sanitizeAndFormat($entry["31.1"], 'ucwords'),
      'provincia_vivienda' => $this->sanitizeAndFormat($entry[133]),
      'ubicacion' => $this->sanitizeAndFormat($entry[100]),
      'nombre_empresa' => $this->sanitizeAndFormat($entry[32], 'strtoupper'),
      'depto_trabajo' => $this->sanitizeAndFormat($entry[101], 'ucwords'),
      'direccion_empresa' => $this->sanitizeAndFormat($entry["33.1"], 'ucwords'),
      'telefono_empresa' => $this->sanitizeAndFormat($entry[26]),
      'ext_empresa' => $this->sanitizeAndFormat($entry[28]),
      'fecha_ingreso_trabajo' => $this->sanitizeAndFormat($entry[35]),
      'cargo' => $this->sanitizeAndFormat($entry[34], 'ucwords'),
      'sueldo' => $this->sanitizeAndFormat($entry[36]),
      'frecuencia_pago' => $this->sanitizeAndFormat($entry[37]),
      'banco_nomina' => $this->sanitizeAndFormat($entry[38]),
      'monto_prestamo' => $this->sanitizeAndFormat($entry[46]),
      'plazo' => $this->sanitizeAndFormat($entry[44] . $entry[55]),
      'cuotas_conveniente' => $this->sanitizeAndFormat($entry[45]),
      'frecuencia_prestamo' => $this->sanitizeAndFormat($entry[109]),
      'persona_dependientes' => $this->sanitizeAndFormat($entry[41]),
      'como_se_entero' => $this->sanitizeAndFormat($entry[48]),
      'comentario_cliente' => $this->sanitizeAndFormat($entry[50]),
      'estado_civil' => $this->sanitizeAndFormat($entry[40]),
      'casa' => $this->sanitizeAndFormat($entry[42]),
      'renta_casa' => $this->sanitizeAndFormat($entry[52] . $entry[53]),
      'uso_prestamo' => $this->sanitizeAndFormat($entry[99]),
      'nombre_referencia1' => $this->sanitizeAndFormat($entry["58.3"], 'ucwords'),
      'telefono_referencia1' => $this->sanitizeAndFormat($entry[63]),
      'parentesco_referencia1' => $this->sanitizeAndFormat($entry[64]),
      'nombre_referencia2' => $this->sanitizeAndFormat($entry["62.3"], 'ucwords'),
      'telefono_referencia2' => $this->sanitizeAndFormat($entry[59]),
      'parentesco_referencia2' => $this->sanitizeAndFormat($entry[61]),
      'tipo_solicitud' => $this->sanitizeAndFormat($entry[119]),
      'tipo_historial' => "",
      'institucion_historial' => $this->sanitizeAndFormat($entry[71]),
      'monto_historial' => $this->sanitizeAndFormat($entry[74]),
      'estatus' => $this->sanitizeAndFormat($entry[103]),
      'respuesta' => $this->sanitizeAndFormat($entry[122]),
      'fecha_nacimiento' => $this->sanitizeAndFormat($entry[130]),
      'genero' => $this->sanitizeAndFormat($entry[132]),
      'referidopor' => $this->sanitizeAndFormat($entry[134], 'ucwords'),
      'geolocalizacion' => $this->sanitizeAndFormat($entry[137] . $entry[147]),
      'registro_notas' => $this->sanitizeAndFormat($entry[138]),
      'asesor_designado' => "No Asignado",
      'asesor_selec' => "No Asignado",
      'solicitud_leida' => 0,
      'ced_repetida' => 0,
      'codigo_empresa' => "SP0001"
    ];
  }

  private function sanitizeAndFormat($value, $formatter = null)
  {
    // Type checking
    if ($value === null || $value === '') {
      return '';
    }
    $sanitized = sanitize_text_field($value);
    if ($formatter && function_exists($formatter)) {
      return $formatter($sanitized);
    }
    return $sanitized;
  }

  private function rechazarPorProvincia($datos)
  {
    $datos['estatus'] = "RECHAZADA";
    $datos['comentario'] = "Provincia: " . $datos['provincia_vivienda'];
    $datos['respuesta'] = "si";
    $datos['tipo_respuesta'] = "Respondida - Rechazada";
    $datos['solicitud_leida'] = 1;
    return $datos;
  }

  private function verificarCedulaRepetida($cedula)
  {
    // Preparar la consulta usando la conexión personalizada
    $stmt = $this->db_connection->prepare("SELECT cedula FROM formulario WHERE cedula = ?");

    if (!$stmt) {
      throw new Exception("Error preparing statement: " . $this->db_connection->error);
    }

    $stmt->bind_param('s', $cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;

    $stmt->close();

    return $count >= 1 ? 1 : 0;
  }

  private function insertarDatos($datos)
  {
    // Preparar los valores y campos
    $fields = implode(', ', array_keys($datos));
    $values = array_values($datos);
    $placeholders = implode(', ', array_fill(0, count($datos), '?'));

    // Preparar la consulta
    $query = "INSERT INTO formulario ($fields) VALUES ($placeholders)";

    // Preparar el statement
    $stmt = $this->db_connection->prepare($query);

    if (!$stmt) {
      throw new Exception("Error preparing statement: " . $this->db_connection->error);
    }

    // Crear types string para bind_param
    $types = str_repeat('s', count($values));

    // Hacer bind de los parámetros
    $stmt->bind_param($types, ...$values);

    // Ejecutar la consulta
    $result = $stmt->execute();

    if (!$result) {
      throw new Exception("Error executing statement: " . $stmt->error);
    }

    $stmt->close();

    return $result;
  }
}

// Initialize the plugin

add_action('plugins_loaded', function () {
  GravityFormsPostToDatabase::getInstance();
});

//new GravityFormsPostToDatabase();
