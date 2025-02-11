# Gravity Forms - Publicar en Base de Datos Personalizada

Un plugin de WordPress que captura datos de solicitudes de préstamo desde Gravity Forms y los envía a una base de datos de verificación.

## Descripción

Este plugin se integra con Gravity Forms para procesar solicitudes de préstamo y almacenarlas en una tabla de base de datos personalizada. Incluye características como:

- Captura automática de datos desde formularios de Gravity Forms especificados
- Integración con una base de datos personalizada
- Filtrado de solicitudes por provincia
- Validación de identificación duplicada
- Saneamiento y formato de datos
- Mapeo configurable de campos del formulario

## Requisitos Previos

- WordPress 5.5 o superior
- Plugin Gravity Forms instalado y activado
- Base de datos MySQL/MariaDB para almacenar las solicitudes
- PHP 7.4 o superior

## Instalación

1. Descarga los archivos del plugin
2. Sube la carpeta del plugin al directorio `/wp-content/plugins/`
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. Configura los ajustes de conexión a la base de datos

## Configuración

### Conexión a la Base de Datos

Puedes configurar la conexión a la base de datos de dos maneras:

1. Usando variables de entorno (archivo .env):

```
LOANS_DB_HOST=tu_host_de_base_de_datos
LOANS_DB_USER=tu_usuario_de_base_de_datos
LOANS_DB_PASSWORD=tu_contraseña_de_base_de_datos
LOANS_DB_NAME=tu_nombre_de_base_de_datos
```

2. Usando constantes en wp-config.php:

```php
define('LOANS_DB_HOST', 'tu_host_de_base_de_datos');
define('LOANS_DB_USER', 'tu_usuario_de_base_de_datos');
define('LOANS_DB_PASSWORD', 'tu_contraseña_de_base_de_datos');
define('LOANS_DB_NAME', 'tu_nombre_de_base_de_datos');
```

### Mapeo de Campos del Formulario

El plugin asigna los campos de Gravity Forms a columnas de la base de datos. Debes ajustar los ID de los campos en el método `prepararDatos()` para que coincidan con la estructura de tu formulario:

```php
'nombre_y_apellido' => $entry["21.3"],  // ID del campo para nombre
'cedula' => $entry[29],                 // ID del campo para número de identificación
'telefono_celular' => $entry[23],       // ID del campo para teléfono móvil
// ... y así sucesivamente
```

### Consideraciones Importantes

1. **IDs de Formulario**: Actualiza la constante `FORMS_ID` para que coincida con tus formularios de Gravity Forms:

```php
private const FORMS_ID = [15, 28];  // Agrega los ID de tus formularios aquí
```

2. **Filtrado por Provincia**: Modifica la constante `PROVINCIAS_PERMITIDAS` para que coincida con las provincias permitidas:

```php
private const PROVINCIAS_PERMITIDAS = ['Distrito Nacional', 'Santo Domingo'];
```

3. **Tabla de Base de Datos**: Asegúrate de que tu base de datos tenga una tabla llamada 'formulario' con las columnas correspondientes a todos los campos en el método `prepararDatos()`.

## Uso

Una vez configurado, el plugin procesa automáticamente las solicitudes enviadas desde los formularios de Gravity Forms especificados. Realizará lo siguiente:

1. Capturar los datos de envío del formulario
2. Validar la provincia del solicitante
3. Verificar identificaciones duplicadas
4. Formatear y sanear los datos
5. Almacenar la información en la base de datos personalizada

## Solución de Problemas

El plugin registra errores en el log de errores de WordPress. Algunos problemas comunes incluyen:

- Falta de configuración de la base de datos
- IDs de campos de formulario incorrectos
- Fallos en la conexión con la base de datos
- Estructura de tabla incorrecta o faltante

Revisa el log de depuración de WordPress para mensajes de error detallados:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Consideraciones de Seguridad

1. Protege tus credenciales de base de datos
2. Usa contraseñas seguras
3. Restringe los permisos del usuario de la base de datos
4. Mantén actualizado WordPress y todos los plugins
5. Realiza copias de seguridad de tu base de datos regularmente

## Licencia

Este plugin está licenciado bajo la GPL v2 o posterior.

## Soporte

Para soporte, por favor crea un issue en el repositorio de GitHub: [https://github.com/ibernabel](https://github.com/ibernabel)

## Contribuciones

¡Las contribuciones son bienvenidas! No dudes en enviar un Pull Request.

Plugin personalizado para wordpress que recibe los parametros o entradas enviadas desde un formulario de Gravity Forms y las inserta en una tabla de Base de Datos de MySQL.
