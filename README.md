<img width="1933" height="506" alt="image" src="https://github.com/user-attachments/assets/c68dc798-1b88-48f5-8d61-bdc5c8d1541c" />

# Prototipo Sistema de gestión de agua potable.

Universidad Mariano Gálvez de Guatemala, 2026.

## Equipo

| Integrante | Carnet | Correo |
|---|---|---|
| Marvin Alexander Cámbara Alonzo (Lider) | 0905-23-17848 | mcambaraa@miumg.edu.gt |
| Pablo Mauricio López Carrillo | 0905-23-14811 | plopezc16@miumg.edu.gt |
| Gustavo Adolfo Godoy Barrera | 0905-19-9068 | ggodoyb1@miumg.edu.gt |
| Nayeli Melissa Urrutia Orellana | 0905-23-5575 | Nurrutiao@miumg.edu.gt |
| Manuel Alexander Monzón Palma | 0905-23-4539 |  |

## Enlaces

- Repositorio: https://github.com/MCambara707/oficina-agua
- Tablero Jira: https://miumg-team-dreamteam.atlassian.net/jira/software/projects/AQ/boards/2/backlog

## Roles del sistema

| Rol | Puede hacer |
|---|---|
| **Administrador** | Todo: usuarios, clientes, contadores, tarifas, servicios, lecturas, recibos, pagos |
| **Secretaria** | Clientes, contadores, tarifas, servicios, lecturas, recibos, pagos, estado de cuenta |
| **Lector** | Registrar y consultar lecturas, imprimir el recibo que se genera |

Para el paso a paso de cada flujo, con capturas de pantalla, ver `docs/manual-usuario.md`.

## Flujo completo del sistema

El recorrido de punta a punta que cubre el sistema es:

```
Login por rol → registro de cliente/contador → registro de lectura →
cálculo automático de consumo → tarifa vigente aplicada → recibo generado
automáticamente (numeración formal) → recibo imprimible → registro de pago →
dashboard de estado de cuenta
```

Al guardar una lectura, el sistema calcula el consumo, busca la tarifa vigente para esa fecha, calcula el monto (incluyendo exceso sobre la capacidad contratada si aplica) y genera el recibo correspondiente en la misma operación — con un número formal tipo `REC-2026-4821-000001`, llevado por un correlativo interno para que nunca se repita.

## Requisitos

- PHP 8.3 o superior
- Composer 2
- Node.js 18+ y npm
- MariaDB (o MySQL compatible)

## Instalación local

1. Clona el repositorio:
   ```bash
   git clone https://github.com/MCambara707/oficina-agua.git
   cd oficina-agua
   ```

2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```

3. Copia el archivo de entorno y genera la clave de la aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configura la conexión a la base de datos en `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=oficina_agua
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Crea la base de datos y carga el esquema. **Este proyecto no usa migraciones de Laravel** — el esquema completo (tablas, llaves foráneas y CHECK) vive en un archivo `.sql` que el equipo mantiene aparte, fuera del repo. Pide la versión más reciente a coordinación y cárgala así:
   ```bash
   mysql -u root -p oficina_agua < ruta/al/archivo.sql
   ```
   El script crea la base de datos si no existe. Ojo: empieza con `DROP TABLE IF EXISTS`, así que borra los datos existentes cada vez que se ejecuta — vuelve a cargar los datos de prueba después.

6. Instala las dependencias de JavaScript y compila los assets:
   ```bash
   npm install
   npm run build
   ```
   Para desarrollo con recarga en caliente, usa `npm run dev` en otra terminal.

7. Enlaza el almacenamiento público (necesario para que las fotos de contadores se vean):
   ```bash
   php artisan storage:link
   ```

8. Levanta el servidor de desarrollo:
   ```bash
   php artisan serve
   ```
   La aplicación queda disponible en `http://localhost:8000`.

## Despliegue

Para la presentación, el sistema debe estar accesible por una **URL pública**, no solo en `localhost`. Está desplegado en una instancia EC2:

```
URL de producción: http://18.189.13.44
```

Los pasos de instalación son los mismos que en local — lo único que cambia es dónde corre. Tengan un plan B listo por si EC2 falla el día de la presentación (un video corto grabado del flujo, o correrlo en local como respaldo).

**Usuario de base de datos restringido:** el `.sql` final crea un usuario `oficina_app` con permisos limitados (sin `DELETE` en las tablas de negocio) pensado para producción. En desarrollo local seguimos usando `root` en el `.env` para no romper los botones de eliminar de los CRUDs. Si configuran producción con `oficina_app`, tengan presente esa limitación.

## Módulos

- **Clientes** — alta, edición y baja lógica de clientes (DPI único obligatorio).
- **Contadores** — un contador por cliente, con tarifa contratada, servicio asociado (opcional), dirección de servicio, foto opcional.
- **Servicios** — catálogo informativo para clasificar contadores; no interviene en el cálculo del recibo.
- **Tarifas** — precios por m³ según tipo (1/2 paja, 1 paja, 2 pajas), con vigencia por fecha. Al activar una tarifa nueva de un tipo, la anterior del mismo tipo se cierra automáticamente.
- **Lecturas** — registro mensual de consumo por contador. Al guardar, genera automáticamente el recibo correspondiente (tarifa vigente, exceso y numeración formal incluidos).
- **Recibos** — historial con búsqueda y filtros (Administrador/Secretaria) e impresión, con cálculo de mora si está atrasado. El Lector solo puede imprimir el recibo puntual que generó.
- **Pagos** — registro de pagos contra un recibo pendiente.
- **Dashboard de estado de cuenta** — resumen por cliente: al día, pendiente o con mora.
- **Usuarios** *(solo Administrador)* — alta, edición y activación/desactivación de cuentas.

## Seguridad y auditoría

- Toda consulta pasa por el ORM de Laravel (Eloquent) — sin SQL concatenado a mano, protegido contra inyección SQL.
- Las contraseñas se guardan hasheadas con bcrypt (`Hash::make`, cast `'hashed'` en el modelo `User`).
- Las vistas Blade escapan variables por defecto (`{{ }}`), protegiendo contra XSS.
- Triggers en la base de datos registran automáticamente cada `INSERT`/`UPDATE`/`DELETE` sobre `tarifas`, `pagos` y `recibos` en una tabla `auditoria` — útil para rastrear quién cambió qué, incluso si el cambio no vino de la aplicación.
- En producción, la app puede conectarse con un usuario de base de datos de permisos limitados (`oficina_app`, sin `DELETE` en tablas de negocio) en vez de `root`.

## Problemas comunes

- **"Las credenciales ingresadas son incorrectas o el usuario está inactivo"** — si el usuario se insertó directo con SQL (sin pasar por Eloquent), la contraseña probablemente quedó en texto plano en vez de hasheada con bcrypt. Corrígelo desde `php artisan tinker`:
  ```php
  \App\Models\User::where('email', 'correo@ejemplo.com')->update(['password' => 'tu_contraseña']);
  ```
  Al pasar por Eloquent, el modelo la hashea automáticamente. También revisa que `activo = 1` en esa fila.

- **"403 - No tiene permisos para acceder a esta sección"** — revisa que el `rol_id` del usuario apunte a una fila de `roles` cuyo `nombre` coincida exactamente (mayúsculas incluidas, sin espacios extra) con el rol que espera la ruta, y que esa fila tenga `activo = 1`.

- **Las fotos de contadores no se ven** — te faltó correr `php artisan storage:link`.

## Flujo de trabajo (Git + Jira)

Cada tarea del tablero de Jira tiene un código (`AQ-XX`). Para que Jira registre automáticamente el avance en la tarjeta:

1. **Rama**, siempre partiendo de `main` actualizado:
   ```bash
   git checkout main
   git pull
   git checkout -b feature/AQ-XX-nombre-de-la-tarea
   ```
2. **Commits**, con el código al inicio del mensaje:
   ```bash
   git commit -m "AQ-XX: descripción corta"
   ```
3. **Pull Request**, con el título en el mismo formato:
   ```
   AQ-XX: Descripción corta del trabajo realizado
   ```

## Estructura relevante

```
app/Models/              Cliente, Contador, Tarifa, Servicio, Lectura, Recibo, Pago, MetodoPago, User, Role
app/Http/Controllers/    Un controller por módulo
app/Http/Middleware/     VerificarRol.php — control de acceso por rol
app/Services/            Redondeo.php, GeneradorNumeroRecibo.php
resources/views/         Vistas AdminLTE, organizadas por módulo
routes/web.php           Rutas agrupadas por rol (middleware rol:...)
docs/manual-usuario.md   Manual de usuario paso a paso, con capturas
```
## Gestión del proyecto

El proyecto se gestiona mediante Jira para organizar las tareas, el seguimiento del trabajo y el avance del equipo.

**Tablero del proyecto en Jira:**
https://miumg-team-dreamteam.atlassian.net/jira/software/projects/AQ/boards/2/backlog

## Roles del sistema

| Rol | Puede hacer |
|---|---|
| **Administrador** | Todo: usuarios, clientes, contadores, tarifas, lecturas, pagos |
| **Secretaria** | Clientes, contadores, tarifas, lecturas, estado de cuenta, pagos |
| **Lector** | Registrar y consultar lecturas |

Para el paso a paso de cada flujo, con capturas de pantalla, ver `docs/manual-usuario.md`.

## Flujo completo del sistema

El recorrido de punta a punta que cubre el sistema es:

```
Login por rol → registro de cliente/contador → registro de lectura →
cálculo automático de consumo → tarifa vigente aplicada → recibo imprimible →
registro de pago → dashboard de estado de cuenta
```

## Requisitos

- PHP 8.3 o superior
- Composer 2
- Node.js 18+ y npm
- MariaDB (o MySQL compatible)

## Instalación local

1. Clona el repositorio:
   ```bash
   git clone https://github.com/MCambara707/oficina-agua.git
   cd oficina-agua
   ```

2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```

3. Copia el archivo de entorno y genera la clave de la aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configura la conexión a la base de datos en `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=oficina_agua
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Crea la base de datos y carga el esquema. **Este proyecto no usa migraciones de Laravel** — el esquema completo (tablas, llaves foráneas y CHECK) vive en un archivo `.sql` que el equipo mantiene aparte, fuera del repo. Pide la versión más reciente a coordinación y cárgala así:
   ```bash
   mysql -u root -p oficina_agua < ruta/al/archivo.sql
   ```
   El script crea la base de datos si no existe. Ojo: empieza con `DROP TABLE IF EXISTS`, así que borra los datos existentes cada vez que se ejecuta — vuelve a cargar los datos de prueba después.

6. Instala las dependencias de JavaScript y compila los assets:
   ```bash
   npm install
   npm run build
   ```
   Para desarrollo con recarga en caliente, usa `npm run dev` en otra terminal.

7. Enlaza el almacenamiento público (necesario para que las fotos de contadores se vean):
   ```bash
   php artisan storage:link
   ```

8. Levanta el servidor de desarrollo:
   ```bash
   php artisan serve
   ```
   La aplicación queda disponible en `http://127.0.0.1:8000`.

## Despliegue

Para la presentación, el sistema debe estar accesible por una **URL pública**, no solo en `localhost`. Está desplegado en una instancia EC2:

```
URL de producción: http://18.189.13.44
```

Los pasos de instalación son los mismos que en local — lo único que cambia es dónde corre. Tengan un plan B listo por si EC2 falla el día de la presentación (un video corto grabado del flujo, o correrlo en local como respaldo).

## Módulos

- **Clientes** — alta, edición y baja lógica de clientes (DPI único obligatorio).
- **Contadores** — un contador por cliente, con tarifa contratada, dirección de servicio, foto opcional.
- **Tarifas** — precios por m³ según tipo (1/2 paja, 1 paja, 2 pajas), con vigencia por fecha. Al activar una tarifa nueva de un tipo, la anterior del mismo tipo se cierra automáticamente.
- **Lecturas** — registro mensual de consumo por contador, con la tarifa vigente calculada automáticamente.
- **Recibos** — listado e impresión de recibos existentes, con cálculo de mora si está atrasado. (Ver nota de alcance arriba: la generación automática desde una lectura aún no existe.)
- **Pagos** — registro de pagos contra un recibo pendiente.
- **Dashboard de estado de cuenta** — resumen por cliente: al día, pendiente o con mora.
- **Usuarios** *(solo Administrador)* — alta, edición y activación/desactivación de cuentas.

## Problemas comunes

- **"Las credenciales ingresadas son incorrectas o el usuario está inactivo"** — si el usuario se insertó directo con SQL (sin pasar por Eloquent), la contraseña probablemente quedó en texto plano en vez de hasheada con bcrypt. Corrígelo desde `php artisan tinker`:
  ```php
  \App\Models\User::where('email', 'correo@ejemplo.com')->update(['password' => 'tu_contraseña']);
  ```
  Al pasar por Eloquent, el modelo la hashea automáticamente. También revisa que `activo = 1` en esa fila.

- **"403 - No tiene permisos para acceder a esta sección"** — revisa que el `rol_id` del usuario apunte a una fila de `roles` cuyo `nombre` coincida exactamente (mayúsculas incluidas, sin espacios extra) con el rol que espera la ruta, y que esa fila tenga `activo = 1`.

- **Las fotos de contadores no se ven** — te faltó correr `php artisan storage:link`.

## Flujo de trabajo (Git + Jira)

Cada tarea del tablero de Jira tiene un código (`AQ-XX`). Para que Jira registre automáticamente el avance en la tarjeta:

1. **Rama**, siempre partiendo de `main` actualizado:
   ```bash
   git checkout main
   git pull
   git checkout -b feature/AQ-XX-nombre-de-la-tarea
   ```
2. **Commits**, con el código al inicio del mensaje:
   ```bash
   git commit -m "AQ-XX: descripción corta"
   ```
3. **Pull Request**, con el título en el mismo formato:
   ```
   AQ-XX: Descripción corta del trabajo realizado
   ```

## Estructura relevante

```
app/Models/              Cliente, Contador, Tarifa, Lectura, Recibo, Pago, MetodoPago, User, Role
app/Http/Controllers/    Un controller por módulo
app/Http/Middleware/     VerificarRol.php — control de acceso por rol
app/Services/            Redondeo.php — redondeo de montos calculados
resources/views/         Vistas AdminLTE, organizadas por módulo
routes/web.php           Rutas agrupadas por rol (middleware rol:...)
docs/manual-usuario.md   Manual de usuario paso a paso, con capturas
```
