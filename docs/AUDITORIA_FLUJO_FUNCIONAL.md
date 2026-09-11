# Auditoría técnica y funcional — Oficina del Agua

Rama: `feature/AQ-38-flujo-funcional`. Trabajo realizado sobre los cambios que ya existían en esta rama, sin commit ni push.

## 1. Resultado general

**Flujo con importe positivo: OK en las pruebas funcionales. Preparación operativa: parcialmente OK**, pendiente de registrar las tarifas reales y de definir el tratamiento de recibos sin importe.

Se inspeccionaron modelos, controladores, rutas, formularios, recibo imprimible, menú AdminLTE, restricciones y triggers de MariaDB. Se ejecutaron 65 pruebas con 361 aserciones en SQLite en memoria, además de consultas del esquema real y siete comprobaciones HTTP de lectura contra MariaDB. No se registraron clientes, contadores, lecturas, recibos ni pagos de prueba en la base operativa.

La única modificación de esquema fue la autorizada: `contadores.lectura_inicial DECIMAL(12,3) NULL`, con restricción no negativa. También se habilitó **Efectivo**, único método de pago autorizado por el usuario.

## 2. Archivos modificados en esta auditoría

El siguiente listado distingue nuestras modificaciones de los cambios que ya estaban presentes al comenzar. Las rutas son relativas a `C:\laragon\www\oficina-agua`.

| Archivo | Modificación realizada |
| --- | --- |
| `app/Http/Controllers/AltaServicioController.php` | Lectura inicial nullable; validación no negativa y de precisión; comprobación de asignaciones activas dentro de la transacción; fotografías con compensación ante fallo; retirada del SET de auditoría innecesario para clientes/contadores. |
| `app/Http/Controllers/ContadorController.php` | Base inicial en creación/edición; bloqueo del contador y consulta de historial dentro de la transacción; impide cambiar la base o trasladar el contador a otro cliente cuando tiene lecturas; valida nuevas asignaciones activas; elimina la foto vigente después del commit y protege contra borrado con historial. |
| `app/Http/Controllers/LecturaController.php` | Base leída de BD; rechazo de base desconocida; bloqueo del contador antes de consultar historial, validar período y calcular consumo; selección de tarifa vigente bajo bloqueo; lectura y recibo atómicos; límites de precisión/importes; auditoría dentro de cada intento transaccional. |
| `app/Http/Controllers/PagoController.php` | Importe y fecha calculados por backend; mantiene bloqueo, estado pendiente y transacción; revalida método activo; rechazo explícito de total Q0.00; excepciones SQL separadas de errores de negocio; auditoría tras iniciar la transacción. |
| `app/Http/Controllers/ReciboController.php` | Consulta histórica paginada de todos los estados; búsqueda por número, DPI, nombre o contador; filtros combinables por período y estado. Se conservó la reconstrucción existente de mora pagada. |
| `app/Http/Controllers/TarifaController.php` | Protege campos históricos con bloqueo; impide cambiar tipo contratado si tiene contadores; preserva capacidades importadas al desactivar/cerrar; nuevas versiones no pueden invalidar emisiones anteriores; validación de solapamientos y eliminación en uso. |
| `app/Http/Controllers/ServicioController.php` | Comprobación explícita de contadores relacionados y bloqueo antes de eliminar; conserva clasificaciones utilizadas. |
| `app/Http/Middleware/EstablecerUsuarioAuditoria.php` | Contexto de usuario limitado a operaciones con triggers verificados; SQL solo para MySQL/MariaDB; limpia la variable de conexión en finally, incluso ante fallos. |
| `app/Services/Auditoria.php` | Reestablece `@app_user_id` en la conexión actual, dentro de cada intento transaccional, incluyendo reconexiones previas al begin. |
| `app/Models/Contador.php` | Campo inicial en fillable y cast decimal; opción de bloqueo en `tarifaVigente()`. |
| `app/Models/Recibo.php` | Corrige el primer día de atraso; admite una fecha de evaluación única; utiliza el redondeo del proyecto para mora y total. |
| `app/Models/Pago.php` | Actualiza el comentario obsoleto que describía pagos como no funcionales. |
| `bootstrap/app.php` | Registra el middleware de auditoría. |
| `routes/web.php` | Añade consulta administrativa de recibos e integra auditoría; conserva permisos de impresión para Lector. |
| `config/adminlte.php` | Añade acceso a Historial de recibos exclusivamente para Administrador/Secretaria. |
| `database/migrations/2026_09_11_000001_add_lectura_inicial_to_contadores_table.php` | Migración aditiva y reversible del campo autorizado con CHECK no negativo. Aplicada, lote 2. |
| `resources/views/alta-servicio/create.blade.php` | Campo de base y explicación de cero frente a desconocida. |
| `resources/views/contadores/create.blade.php` | Campo de base inicial y sus validaciones. |
| `resources/views/contadores/edit.blade.php` | Base visible; edición bloqueada con historial; protege cambio de cliente y explica el motivo. |
| `resources/views/contadores/index.blade.php` | Distingue base cero de «Sin establecer». |
| `resources/views/lecturas/create.blade.php` | Presenta base desde BD; muestra aviso y oculta el formulario si falta base inicial. |
| `resources/views/pagos/create.blade.php` | Elimina el envío innecesario del monto; explica Q0.00 y ausencia de métodos; deshabilita confirmación en esos casos. |
| `resources/views/recibos/index.blade.php` | Consulta histórica con filtros, paginación, impresión, comprobante y acceso a cobro cuando corresponde. |
| `resources/views/tarifas/edit.blade.php` | Informa y protege los campos históricos; no sustituye visualmente capacidades importadas por la fórmula actual. |
| `resources/views/admin-demo.blade.php` | Cambia únicamente el texto de «Panel de prueba» a panel operativo; mantiene ruta y diseño. |
| `phpunit.xml` | Fuerza entorno testing y SQLite en memoria para evitar que variables externas apunten las pruebas a datos reales. |
| `tests/Support/FlujoTestCase.php` | Fixture aislado con restricciones equivalentes relevantes y comprobación explícita de conexión segura. |
| `tests/Feature/FlujoFacturacionTest.php` | Pruebas de altas, base, lecturas, consumo, mora, pagos, rollback, permisos y estado consolidado. |
| `tests/Feature/HistoricoTarifasTest.php` | Filtros históricos, paginación, protección de tarifas, vigencias y eliminaciones. |
| `tests/Feature/ProteccionesOperativasTest.php` | Flujo integral, caso de exceso 27 m³, archivos ante fallos, capacidades importadas, asignaciones activas y búsquedas. |
| `tests/Feature/AuditoriaTest.php` | Usuario parametrizado, limpieza tras fallo, exclusión de rutas sin triggers/SQLite y cambio de conexión. |
| `docs/AUDITORIA_FLUJO_FUNCIONAL.md` | Este informe. |

Estos archivos ya tenían cambios y se revisaron **sin nuevas modificaciones de esta auditoría**: `app/Http/Controllers/ClienteController.php`, `app/Http/Controllers/DashboardEstadoCuentaController.php`, `app/Models/Cliente.php`, `resources/views/dashboard/estado-cuenta.blade.php`, `resources/views/lecturas/index.blade.php`, `resources/views/pagos/index.blade.php` y `resources/views/recibos/imprimible.blade.php`. Por eso el diff total contra HEAD incluye trabajo anterior y no representa exclusivamente esta intervención.

## 3. Correcciones y decisiones técnicas

### Primera lectura

Antes se suponía cero cuando no había historial. Ahora una base desconocida permanece NULL y bloquea la primera lectura. Un cero explícito es válido. Con base 450 y lectura 465 se facturan 15 m³. Cuando hay historial, incluso con base NULL o un valor distinto, se usa exclusivamente la última lectura actual de BD. El Lector no tiene acceso a la edición de la base y los valores enviados desde el navegador se ignoran para calcularla.

### Concurrencia y atomicidad

La fila del contador se bloquea dentro de la transacción, también cuando todavía no tiene lecturas. Dentro de ese bloqueo se consulta la última lectura, se exige un período posterior, se valida la medición y se calcula el consumo. La edición de base y eliminación del contador usan el mismo bloqueo. Se conservan UNIQUE(contador_id, periodo) y UNIQUE(recibos.lectura_id). Si falla la inserción del recibo, se revierte también la lectura.

### Tarifa vigente e historial

El diseño existente ya versiona tarifas por tipo y fechas, y cierra la versión anterior al registrar otra. Por ello el contador mantiene el tipo contratado mediante su tarifa asignada, y la facturación selecciona la versión activa vigente **a la fecha de emisión**, que coincide con fecha_lectura en este flujo. `recibos.tarifa_id` conserva la versión utilizada. Se rechaza facturar si no existe versión válida; no se utiliza una tarifa vencida como alternativa silenciosa. Los recibos anteriores no se recalculan.

Se bloquean nombre, tipo, capacidad, precios, mora e inicio de vigencia de tarifas con recibos. Se permite cambiar estado o fecha final sin excluir emisiones existentes. Si una tarifa importada tiene una capacidad diferente de la fórmula actual de pajas, se conserva esa capacidad al cerrar/desactivar. El Servicio sigue siendo únicamente informativo.

### Cálculo y mora

Se conserva la fórmula: consumo dentro de capacidad por precio normal, más exceso por precio de exceso. Se conserva `Redondeo::monto()`; al no existir config/facturacion.php, siguen aplicándose sus valores predeterminados: dos decimales y PHP_ROUND_HALF_UP.

Se conserva la regla de vencimiento: emisión del día 1 al 10 vence al finalizar el 10 de ese mes; emisión posterior vence al finalizar el 10 siguiente. La mora fija y porcentual se suman una sola vez. Se corrigió el conteo que mostraba cero días durante parte del primer día vencido. El pago usa un único instante para determinar mora y guardar fecha.

Un PAGADO no genera mora nueva. La reimpresión pagada conserva el cálculo ya existente `max(pago.monto - recibo.monto, 0)` como mora pagada, y `pago.monto` como total efectivamente cobrado. Las pruebas verifican que esos importes se conservan meses después.

### Pago real y errores

El backend ignora monto/fecha enviados, bloquea recibo, exige PENDIENTE, comprueba que no exista pago y valida método activo. Inserción de pago y estado PAGADO forman una transacción; se conserva UNIQUE(pagos.recibo_id). Se distingue QueryException antes de errores de negocio y los detalles SQL no se muestran al operador.

Un total Q0.00 se rechaza antes de insertar porque la restricción real exige monto > 0. No se inventó un pago, una anulación ni una liquidación automática. Ese recibo permanece pendiente y su tratamiento comercial sigue pendiente de definición.

### Auditoría

Se inspeccionaron nueve triggers: INSERT, UPDATE y DELETE de tarifas, recibos y pagos, que consumen `@app_user_id`. No hay triggers equivalentes para clientes, contadores ni lecturas. El middleware delimita las rutas relevantes y limpia la variable; el servicio la restablece dentro de cada transacción para cubrir reconexiones. Se conservan los triggers existentes.

### Consulta, roles y eliminaciones

Administrador y Secretaria pueden recuperar recibos de cualquier estado por número, cliente, DPI, contador, período y estado. El Lector registra lecturas e imprime; las rutas rechazan pagos y mantenimientos para ese rol. El menú aplica los mismos permisos. El estado de cuenta continúa calculándose por cliente y todos sus contadores, sin almacenar saldos redundantes.

Las FKs reales impiden eliminaciones referenciadas. Se mantiene el rechazo de eliminación de clientes con contadores, y de contadores con lecturas. Tarifas/servicios utilizados se conservan y pueden desactivarse. También se bloqueó cambiar el cliente del contador con historial, porque desplazaría su deuda y sus comprobantes a otra persona.

### Numeración

Se conserva `sprintf('REC-%06d', lectura_id)`. La anchura de seis dígitos es mínima; el formato no trunca IDs mayores. El ID de lectura y las restricciones únicas evitan duplicados bajo el flujo actual. Los huecos por operaciones revertidas son posibles y no rompen el sistema. AQ-71 sigue pendiente.

## 4. Pendientes no modificados

- Política de cierre de recibos de Q0.00 y tratamiento de su eventual mora fija. Se mantuvo la regla de mora existente, sin decidir exenciones ni liquidaciones automáticas.
- Registrar las tarifas reales y, si corresponde, el catálogo de servicios. La base operativa estaba vacía de clientes, contadores, lecturas, tarifas, servicios, recibos y pagos. Solo se agregó Efectivo con autorización explícita; no se inventaron precios.
- Confirmar zona horaria antes de producción: `config/app.php` conserva UTC. Todas las pantallas y el pago usan esa configuración; una regla comercial basada en hora Guatemala necesita definir ese horario de corte explícitamente.
- AQ-71 (numeración definitiva) y AQ-43 (diseño general), sin cambios de alcance.
- No se propone ni aplica ningún otro cambio de esquema.

## 5. Migración y verificaciones ejecutadas

Migración creada y aplicada: `database/migrations/2026_09_11_000001_add_lectura_inicial_to_contadores_table.php`.

```sql
-- up(): ejecutado
ALTER TABLE contadores
  ADD COLUMN lectura_inicial DECIMAL(12,3) NULL,
  ADD CONSTRAINT chk_contadores_lectura_inicial_no_negativa
  CHECK (lectura_inicial IS NULL OR lectura_inicial >= 0);

-- down(): disponible; inspeccionado con --pretend, NO ejecutado
ALTER TABLE contadores
  DROP CONSTRAINT chk_contadores_lectura_inicial_no_negativa,
  DROP COLUMN lectura_inicial;
```

La reversión elimina únicamente el campo y su CHECK. Una reversión real perdería las bases registradas después de la migración: no se ejecutó contra la base operativa.

| Comando / comprobación | Resultado |
| --- | --- |
| `php -l` sobre PHP y Blade modificados/nuevos | 37 archivos sin errores en el barrido final de código. |
| `php artisan optimize:clear` | Correcto. |
| `php artisan route:list` | Correcto: 73 rutas, incluidas las del proveedor. |
| `php artisan route:list --except-vendor` | Correcto: 69 rutas según filtrado de Laravel. |
| `php artisan view:cache` | Correcto; todas las vistas compiladas. |
| `php artisan test` | **65 passed, 361 assertions, 0 fallos**. |
| `git diff --check` | Sin errores. |
| `php artisan migrate --path=database/migrations/2026_09_11_000001_add_lectura_inicial_to_contadores_table.php --pretend` | SQL aditivo revisado antes de ejecutar. |
| Mismo `migrate --path=... --force` | Aplicada correctamente, lote 2. |
| `php artisan migrate:rollback --path=database/migrations/2026_09_11_000001_add_lectura_inicial_to_contadores_table.php --pretend` | SQL de reversión inspeccionado; no se eliminó el campo. |
| `php artisan migrate:status` | Migración autorizada sigue aplicada, lote 2. |
| information_schema y SHOW CREATE TABLE | Confirmados DECIMAL(12,3), nullable, default NULL, CHECK, FKs, UNIQUE y triggers reales. |
| HTTP contra aplicación y MariaDB reales, usando sesiones en memoria y solo GET | 200 en alta, creación de contador, creación de lectura, pagos, recibos, estado de cuenta y creación de tarifa. |

SQLite verifica reglas, HTTP, roles, consultas dentro de transacciones, fallos/rollback y restricciones relevantes. No reproduce la contención real de filas ni ejecuta los triggers de MariaDB; la asignación/limpieza de auditoría se prueba adicionalmente con conexiones simuladas. No se presenta esta suite como prueba de carga concurrente en MariaDB ni como prueba de una impresora física.

## 6. Flujo final verificado

| Paso | Evidencia |
| --- | --- |
| Alta | Cliente nuevo o existente, DPI único, contador activo y base 450 persistida. |
| Lectura | Lector registra 465; backend usa base 450 y calcula consumo 15. En el mes siguiente usa la última lectura actual. |
| Recibo | Se genera PENDIENTE automáticamente: Q30.00 con precio normal Q2.00. El caso 100 → 127 con capacidad20 y exceso Q5.00 genera Q75.00. |
| Impresión | Respuesta HTTP correcta del documento pendiente con cliente, DPI, contador, servicio, tarifa y consumo. |
| Mora | Al vencer el ejemplo integral: Q3.00 fijos + 10% de Q30.00 = Q6.00; total Q36.00. |
| Pago | Secretaria cobra Q36.00 en Efectivo; referencia y observación persistidas. Un segundo pago se rechaza. |
| Comprobante | Se muestra PAGADO, monto original Q30.00, mora pagada Q6.00 y total pagado Q36.00; recuperación desde historial. |
| Estado de cuenta | Pasa de Con mora a Al día al desaparecer el último recibo pendiente; prueba adicional consolida varios contadores y excluye anulados/pagados del saldo. |

## 7. Riesgos restantes y límites

- Falta una prueba de contención simultánea sobre una instancia MariaDB aislada. Los bloqueos se revisaron en código y las pruebas comprueban que las consultas críticas ocurren dentro de transacción.
- El estado de cuenta carga clientes y recibos en colecciones completas; se debe medir su rendimiento cuando exista un volumen representativo.
- El documento histórico utiliza datos actuales de identidad del cliente y clasificación del contador; no constituye una instantánea de esos textos. La tarifa económica y el pago sí quedan protegidos por este flujo.
- La preparación para publicar en producción necesita una revisión separada de configuración: zona horaria, depuración, acceso a demo/documentación de AdminLTE y límites de intentos de acceso. No se modificó `.env` ni se amplió esta intervención a despliegue.

No se usó migrate:fresh, no se borraron registros operativos, no se reemplazó la estructura del proyecto, no se creó otra tabla, no se cambió de rama y no se hizo commit ni push.
