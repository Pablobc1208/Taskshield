# Taskshield — Intranet de incidencias de soporte

Intranet para el departamento de soporte informático de una empresa. Permite registrar incidencias, verlas en un panel, editarlas, eliminarlas y asignarlas a un técnico. Está hecha con PHP y PDO sobre MySQL/MariaDB.

Autores: Daniel Ríos Zea y Pablo Barrera Corbacho (2.º DAW).

## 1. Arquitectura del proyecto

```
Taskshield/
├── config.php               Credenciales de la base de datos (NO está en el repositorio)
├── conexion.php             Crea la conexión PDO ($pdo) que usan todas las páginas
├── index.php                Panel principal: tabla con las incidencias activas
├── nueva_incidencia.php     Formulario de alta de una incidencia
├── editar_incidencia.php    Formulario de modificación (estado y resto de datos)
├── eliminar_incidencia.php  Confirmación y borrado de una incidencia
├── asignar_incidencia.php   Asignación a un técnico (transacción)
├── style.css                Estilos comunes
└── doc/
    ├── README.md            Este documento
    ├── schema.sql           Estructura de las tablas y datos de prueba
    ├── casos_prueba.md      Casos de prueba ejecutados
    └── capturas/            Capturas de los casos de prueba
```

Cómo se relacionan los archivos:

- **`config.php`** define las constantes de conexión (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `DB_DSN`). Está en `.gitignore` para no subir credenciales al repositorio.
- **`conexion.php`** carga `config.php` y crea el objeto `$pdo` dentro de un `try-catch`. Activa las excepciones (`PDO::ERRMODE_EXCEPTION`) y desactiva la emulación de sentencias preparadas (`PDO::ATTR_EMULATE_PREPARES => false`). Si la conexión falla, guarda el error real en el log del servidor y muestra un mensaje genérico.
- **Cada página** (`index.php`, `nueva_incidencia.php`, etc.) empieza con `require_once 'conexion.php'`. Primero va la lógica PHP (recoger datos, consultar) y después el HTML que la muestra.

Base de datos `intranet_soporte`, con dos tablas:

- **`incidencias`**: título, descripción, prioridad, email del cliente, estado (`Pendiente`, `En Proceso`, `Resuelta`) y `tecnico_id` (clave foránea a `tecnicos`).
- **`tecnicos`**: nombre, email, especialidad y `tareas_activas`. Una restricción impide que un técnico tenga más de 5 tareas activas.

Medidas de seguridad e integridad:

- Todas las consultas usan sentencias preparadas (`prepare()` + `execute()`) con marcadores con nombre.
- Todos los datos de entrada se recogen con `filter_input()`: `FILTER_VALIDATE_EMAIL` para el correo, `FILTER_VALIDATE_INT` para los ID y `FILTER_SANITIZE_SPECIAL_CHARS` para los textos.
- Las operaciones de edición y borrado comprueban las filas afectadas con `rowCount()`.
- La asignación a un técnico se hace en una transacción (`beginTransaction()`, `commit()`, `rollBack()`).

## 2. Cómo ejecutarlo paso a paso

Estas instrucciones son para Windows con XAMPP y no requieren conocimientos previos.

### Paso 1. Instalar XAMPP

1. Descarga XAMPP desde <https://www.apachefriends.org> e instálalo con las opciones por defecto (se instala en `C:\xampp`).
2. Abre **XAMPP Control Panel**.
3. Pulsa **Start** en la fila **Apache** y en la fila **MySQL**. Los dos nombres deben quedar con fondo verde.

### Paso 2. Copiar el proyecto

El proyecto tiene que estar dentro de `C:\xampp\htdocs`.

- **Con Git**: abre una terminal y ejecuta:

  ```bash
  cd C:\xampp\htdocs
  git clone https://github.com/Pablobc1208/Taskshield.git
  ```

- **Sin Git**: en la página del repositorio pulsa **Code > Download ZIP**, descomprímelo y copia la carpeta dentro de `C:\xampp\htdocs` con el nombre `Taskshield`.

Al terminar debe existir el archivo `C:\xampp\htdocs\Taskshield\index.php`.

### Paso 3. Crear el archivo `config.php`

Este archivo no viene en el repositorio, así que hay que crearlo a mano.

1. Abre el Bloc de notas.
2. Copia este contenido:

   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'intranet_soporte');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_CHARSET', 'utf8mb4');
   define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
   ```

3. Guárdalo como `C:\xampp\htdocs\Taskshield\config.php`. En el cuadro de guardar, elige **Tipo: Todos los archivos** para que no se guarde como `config.php.txt`.

Los valores anteriores son los de una instalación de XAMPP por defecto (usuario `root` sin contraseña). Si tu MySQL tiene otro usuario o contraseña, cámbialos en `DB_USER` y `DB_PASS`.

### Paso 4. Crear la base de datos

1. Abre en el navegador <http://localhost/phpmyadmin>.
2. Pulsa la pestaña **Importar** de la barra superior.
3. Pulsa **Seleccionar archivo** y elige `C:\xampp\htdocs\Taskshield\doc\schema.sql`.
4. Pulsa el botón **Importar** del final de la página.
5. Debe aparecer un mensaje en verde. En el panel izquierdo aparecerá la base de datos `intranet_soporte` con las tablas `incidencias` (14 filas) y `tecnicos` (4 filas).

El script borra y vuelve a crear la base de datos, así que se puede importar de nuevo en cualquier momento para volver a los datos iniciales.

### Paso 5. Abrir la aplicación

Abre en el navegador <http://localhost/Taskshield/index.php>.

Debe aparecer el panel **Incidencias activas** con una tabla de 12 incidencias.

### Si algo falla

| Lo que ves | Causa | Solución |
|---|---|---|
| "No se ha podido establecer la conexión. Inténtalo más tarde." | MySQL está parado, no se ha importado `schema.sql` o los datos de `config.php` no son correctos | Revisa los pasos 1, 3 y 4 |
| Error que menciona `config.php` | Falta el archivo o se ha guardado como `config.php.txt` | Repite el paso 3 |
| "Not Found" / "Objeto no localizado" | La carpeta no está en `htdocs` o tiene otro nombre | Revisa el paso 2 |
| El navegador no carga nada | Apache está parado | Revisa el paso 1 |

## 3. Cómo probar cada función

Todo se hace desde el panel principal (<http://localhost/Taskshield/index.php>).

| Función | Cómo probarla | Resultado esperado |
|---|---|---|
| Ver incidencias | Abrir el panel | Tabla con las incidencias que no están resueltas |
| Alta | Pulsar **Nueva incidencia**, rellenar los cuatro campos y pulsar **Registrar incidencia** | Se abre la pantalla de edición de la incidencia recién creada |
| Edición | Pulsar **Editar** en una fila, cambiar el estado y pulsar **Guardar cambios** | Mensaje "Incidencia actualizada correctamente." |
| Borrado | Pulsar **Eliminar** en una fila y después **Sí, eliminar** | Mensaje "Incidencia eliminada correctamente." |
| Asignación | Pulsar **Asignar** en una incidencia pendiente, elegir a **Marta Sánchez Vega** y pulsar **Asignar** | Mensaje "Incidencia asignada correctamente."; la incidencia pasa a `En Proceso` y Marta suma una tarea activa |

Los casos de error (email inválido, ID inexistente y fallo de la transacción) están documentados con capturas en [casos_prueba.md](casos_prueba.md).
