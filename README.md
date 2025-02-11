# Gravity Forms - Post to Custom Database

Plugin para WordPress que permite conectar formularios específicos de Gravity Forms con una base de datos externa personalizada. Ideal para sistemas que necesitan mantener los datos de formularios en una base de datos separada de WordPress.

## Descripción

Este plugin está diseñado específicamente para capturar datos de solicitudes desde formularios de Gravity Forms y enviarlos a una base de datos externa. Es especialmente útil cuando necesitas:

- Mantener los datos de formularios en una base de datos separada
- Procesar solicitudes con validaciones específicas
- Manejar datos sensibles en un ambiente controlado
- Integrar WordPress con sistemas externos

## Características

- Conexión segura con base de datos externa
- Validación de datos antes de la inserción
- Sistema de logging para debugging
- Página de prueba de conexión en el panel de administración
- Manejo de errores robusto
- Sanitización automática de datos
- Verificación de registros duplicados

## Requisitos

- WordPress 5.5 o superior
- PHP 7.4 o superior
- Gravity Forms instalado y activado
- Acceso a una base de datos MySQL externa
- Permisos para modificar wp-config.php

## Instalación

1. Descarga el plugin desde el repositorio
2. Sube la carpeta del plugin al directorio `/wp-content/plugins/`
3. Activa el plugin desde el panel de plugins en WordPress
4. Configura las credenciales de la base de datos (ver sección de configuración)

## Configuración

### Método 1: Usando wp-config.php (Recomendado)

Añade las siguientes constantes a tu archivo `wp-config.php`:

```php
define('LOANS_DB_HOST', 'tu_host');
define('LOANS_DB_USER', 'tu_usuario');
define('LOANS_DB_PASSWORD', 'tu_contraseña');
define('LOANS_DB_NAME', 'nombre_base_datos');
```

### Método 2: Usando opciones de WordPress

El plugin automáticamente creará las siguientes opciones en WordPress durante la activación:
- gf_db_host
- gf_db_user
- gf_db_password
- gf_db_name

## Adaptación de Campos del Formulario

El plugin está configurado para mapear campos específicos del formulario a la base de datos. Para adaptar los campos:

1. Identifica los IDs de los campos en tu formulario de Gravity Forms
2. Modifica el método `prepararDatos()` en la clase principal
3. Asegúrate que los nombres de las columnas en tu tabla `formulario` coincidan con las keys del array

Ejemplo de mapeo:
```php
'nombre_y_apellido' => $entry["21.3"],  // 21.3 es el ID del campo en Gravity Forms
'telefono' => $entry[23],               // 23 es el ID del campo
```

## Herramientas de Diagnóstico

### Página de Prueba de Conexión

Accede a la página de prueba desde: `Herramientas > GF Database Test`

Esta página muestra:
- Estado de la conexión
- Logs de MySQL
- Últimas 50 entradas del log de debug

### Sistema de Logs

El plugin mantiene un archivo de log en:
`wp-content/debug-gf-database.log`

Los logs incluyen:
- Errores de conexión
- Problemas de inserción de datos
- Validaciones fallidas
- Información de debugging

## Seguridad

- Todos los datos son sanitizados antes de la inserción
- Las consultas utilizan prepared statements
- Las credenciales de la base de datos están protegidas
- Se implementa validación de roles y capacidades

## Limitaciones

- Solo funciona con formularios específicos (IDs configurados en FORMS_ID)
- Requiere una estructura específica en la tabla de la base de datos
- Las provincias permitidas están hardcodeadas (modificar PROVINCIAS_PERMITIDAS)

## Contribución

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del repositorio
2. Crea una rama para tu feature
3. Envía un pull request

## Licencia

Este proyecto está licenciado bajo MIT License.

Copyright (c) 2025 Idequel Bernabel

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.