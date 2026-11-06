# SGUC (Sala Gamer Universidad Católica)

## Base de Datos

**Nota**, la imágen `MR.png` contiene la DB ya exportada de MySQL en su modelo relacional.

La tabla `usuarios` incluye jugadores y administradores. De la tabla `generos` y `rols` obtiene información poblacional, los roles son además los permisos. `sesiones` es opcional llenarla aunque siempre creado un registro a la par que en usuarios. Cuando son usuarios registrados con correo y contraseña para inicios de sesión, en sesiones estarán las credenciales (nunca usar Users de Laravel), pero por ejemplo, los roles invitado no suelen tener email o password. Siempre `sesiones` y `usuarios` es 1:1.

La tabla `estados` cuenta con flags para identificar la máquina de estados de varias tablas, por lo que en los selectores del frontend no necesariamente se cargarán todos los estados para todos los objetos.

La tabla `dispositivos` son los equipos de la sala, como computadores, consolas, etc, y `tipo_dispos` son los tipos de dispositivos que puede haber, por ejemplo, el PC1 es tipo PC. Las reservaciónes se hacen por tipo y las asociaciónes por dispositivo, además, dispositivos tiene dos campos opcionales (direccion y password) en caso de que sea posible tener por ejemplo direcciónes IP para comandos remotos.

La tabla `auditorias` identifica qué usuarios han hecho `acciones` sobre tablas y llaves primarias de sus registros, para seguimiento de deciciónes administrativas.

La tabla `asociaciones` es cuando un usuario jugador está actualmente usando una máquina. Su fecha inicio es establecida al crear la asociación y su fecha final es actualizada a medida que pasa el tiempo hasta cerrar la asociación modificando el estado. Solo puede haber una asociación activa por usuario, aunque habrá más inactivas en el pasado, además en caso de estar asociado a un evento, puede no necesariamente estar asociado a un dispositivo.

Las `observaciones` son comentarios dejados por un administrador, ya sea referenciados a una asociación o no, por ejemplo, el jugador dijo que un gamepad estaba malo durante la revisión del equipo, entonces su asociación se hace con esa observación, o si el administrador se dió cuenta después, crea una observación con o sin asociación (en ese caso solo podrá elegir asociaciónes recientes de la lista).

La tabla `reservas` es cuando un usuario pide la sala a futuro, el inicio dice cuándo y la duración define la franja en minutos. Los tipos de dispositivos dependen entonces de la disponibilidad de los mismos, según la tabla `dispositivos` y su estado. Cuando se reserva puede entrar en colisión con otras reservas y ser negada, pero tamién con asociaciónes siempre que estas estén aún activas y en su tiempo mínimo de juego (constante global).

Los `eventos` son similares a las reservas, pero pudiendo tener un nombre y descripción, un usuario a cargo, un apróximado de la cantidad de invitados (aforo), y un flag (auto_admin) que dice si es permitido o no para un jugador, hacer reservas y asociaciónes vinculadas internamente a ese evento, en caso negativo, se entiende que los encargados del evento administrarán los tiempos de uso de los equipos, aún así, los invitados al evento deberán registrarse asociados al evento más no a un dispositivo concreto.

Luego los eventos tienen `evento_dispos` que son los dispositivos de la sala que están reservados para un evento M:N.

## Consideraciónes

En la tabla `rols` están los permisos, `RolSeeder.php` los tiene descritos, es importante recalcar que "Invitado" es un usuario sin email o contraseña, "Administrador" tiene permisos básicos de uso de los CRUD y "Master" es un administrador con permisos totales, intocable por los Aministrador's. Los demás son usuarios regulares con permisos entre invitado y administrador.

No se eliminan registros de las tablas que contienen estado_id, ya que hay un estado de "Eliminado" por seguimiento de auditorías legales. Existe forma de recuperar los eliminados, el admin Master lo puede hacer.

Para un usuario invitado solo es obligatorio: cédula, nombres, apellidos (rol y estado son automáticos).

## Funcionalidades para Servicios y Controllers

Cuando se trata de cursores, es porque se cargarán los datos bajo demanda, entonces al llegar el scroll del frontend abajo, se llama al resto.

Los servicios más complejos son: crear asociación, crear reserva y crear evento, pues son los que deben calcular colisiónes en tiempo con otros registros de su naturaleza (más adelante se describen las reglas).

### Usuarios

1. buscar lista de usuarios, usando cursor y filtrando según rol o estado, ordenados por fecha de registro.
2. buscar toda la información de un usuario específico, no incluye reserva ni asociación vinculadas.
3. crear un nuevo usuario, ya sea invitado (sin credenciales de acceso), o completo (enviará una contraseña aleatoria al correo asociado), solo el admin master puede dar permisos administrativos.
4. editar un usuario, solo el admin master puede dar permisos administrativos, se puede convertir un invitado en registrado con credenciales (enviará una contraseña aleatoria al correo asociado), se puede cambiar el estado a eliminado.

### Dispositivos

1. buscar lista de dispositivos, usando cursor y filtrando por tipo_dispo o estado, ordenados por fecha de registro.
2. buscar toda la información de un dispositivo específico.
3. crear un nuevo dispositivo, solo el admin master puede hacerlo.
4. editar un dispositivo, solo el admin master puede elminarlo, los demás admins pueden cambiar el estado de otras formas.

### Eventos

1. buscar lista de eventos, usando cursor y filtrándo por estado, ordenados por fecha de inicio.
2. buscar toda la información de un evento específico.
3. crear un evento.
4. editar un evento, incluye cambiar su estado a eliminado.

### Administración

1. buscar todo el horario del día seleccionado según filtro, para ser puesto en la línea de tiempo.
2. crear nueva asociación, suponiendo que se cumplan las reglas, opcionalmente puede generar registro de observación.
3. crear nueva reserva, suponiendo que se cumplan las reglas.
4. buscar información básica de un usuario, su asociación y reserva si las hay, y su histórico de asociaciónes del día actual.
5. buscar las alertas que pueda haber en el instante presente, debido a asociaciónes y reservas.
6. buscar asociaciónes, reservas y eventos para una franja de un día específico.

### Observaciónes

1. buscar lista de observaciónes, usando cursor y filtrando por usuario administrador, usuario asociado a asociaciones, evento asociado a asociaciónes o dispositivo asociado a asociaciónes, ordenando por fecha de registro, esto devuelve la fecha de registro, el id y el mensaje.
2. crear una observación, ya sea sin asociación o ligada a una asociacion, el frontend permitirá escoger alguna de las recientemente activas, las del último día.
3. buscar toda la información de una observación específica (nombre y apellidos del admin que la hizo, nombre y apellidos del usuario asociado, evento asociado y dispositivo asociado si los hay, y el mensaje con fecha de registro).

### Estadísticas

(próximamente...).

### Auditorías

**Nota**, los registros de auditorías se crean automáticamente cuando se hacen otras consultas a los servicios.

1. buscar lista de auditorías, usando cursor y filtrando por acción y usuario, ordenando por fecha de registro.

### Configuración

1. envío de contraseña y dos veces la nueva, para efectuar cambio de la misma.

### Login

1. envío de credenciales para inicio de sesión, y verificación del mismo.
2. solicitud de cambio de contraseña, enviará una nueva contraseña aleatoria al correo asociado.
3. obtener información básica del usuario para mostrar y saber si está logueado, para la ficha (nombres + apellidos, email, rol).

## Frontend

La App es para uso en escritorio, excepto por las vistas que especifiquen ser para móvil, que serán los únicos responsivos.

En `app.css` estarán las importaciónes, tipografía y colorimetría, luego en `estilos.css` irán todas las clases CSS para el HTML de toda la App.

### Vistas Login (views/sesion/*)

(para móvil y escritorio, y no usan el layout.blade.php)

(estas vistas no requieren credenciales de login)

1. **index**: muestra el logo institucional arriba, permite ingresar credenciales de acceso y botón de ingresar, también tiene un botón tipo link a "recuperar contraseña".
2. **recuperar**: similar al index, pero esta vez permite ingresar el correo, y un botón para enviar ahí la nueva contraseña aleatoria, tiene un botón tip link sobre "ya tiene cuenta" para volver al login.

### Vistas Menú Principal (views/*)

(requiere cualquier tipo de credenciales de inicio de sesión)

1. **index**: es la página de bienvenida, cuenta con una imágen central, va dentro del layout.
2. **layout**: quien es la estructura base donde van todas las demás vistas (excepto views/sesion/* Login's), por eso tiene pestañas laterales para cambiar de sub-vista (se muestran según permisos del rol en sesión activa). Tiene un header con el logo institucional, el título y una ficha de usuario logeado (nombres + apellidos, email, rol). En su pie de página tine información institucional.

### Vistas Administración (views/administracion/*)

(requiere credenciales de Administrador o Master)

**Nota** el archivo `ej_godot.html` es un ejemplo de esta interfaz (aunque su CSS no coincide con el deseado en este proyecto Laravel), creada en otro software, además las imágenes `mockup1.png` y `mockup2.png` son esa misma interfaz de ejemplo.

1. **index**: contiene todo lo necesario para la mayoría de acciónes administrativas, está conformada por módulos, se divide en dos partes: la de abajo tiene el módulo B, y la de arriba se subdivide en dos partes: la de la izquierda tiene el módulo A y la de la derecha el C.

A. **Jugador**: está subdividida en varios componentes, se divide en dos partes, la de arriba con el componente A1, la de abajo se subdivide en 3 componentes, izquierda A2, centro A3 y derecha A4.

A1. **Búsqueda**: tiene como título "Jugador", tiene un botón para crear nuevo jugador, y un indicador de su estado en la DB, tiene luego un espacio para escribir un ID con el botón de envío, y un espacio para escribir los datos básicos del usuario si lo encontró.

A2. **Reservar**: es una ficha que puede tener dos estados (títulos: Reserva y Reservar), mostrar información de reserva si la hay (los mismos datos del formulario quemados, y dos botónes, cancelar y asociar) y mostrar formulario de creación de reserva si no la hay (selector de tipo de dispositivo, selector de evento, fecha de reserva, hora de reserva, minutos reservados, y botón de envío).

A3. **Asociar**: es una ficha que puede tener dos estados (tírulos: Asociación y Asociar), mostrar información de asociación si la hay (los mismos datos del formulario quemados, y un botón de cerrar) y mostrar formulario de creación de asociación si no la hay (selector de dispositivo, selector de evento, espacio para escribir observación opcional, indicador superior de si la asociación proviene de reserva, y botón de envío).

A4. **Horas**: tiene un título "Horas" y luego un listado con scroll, donde se van colocando las entradas a modo de tabla, con 3 columnas: hora de inicio, dispositivo usado y fracción de horas acumuladas. Abajo fuera del scroll tiene un texto del total de horas acumuladas.

B. **Horarios**: se divide en dos espacios, el mayor y a la izquierda B1, y a la derecha de menor tamaño B2.

B1. **Tiempos**: es una rejilla que tiene etiquetas para las filas, asociadas a cada dispositivo (puede crecer en vertical scroll), y etiquetas arriba para las columnas, asociadas a las horas del día, de 1am a 12pm (crece horizontalmente scroll), cada cuadro puede tener o no un botón que indica ocupado, y su color depende de los ítems ahí, sean reservas asociaciónes o eventos. Además hay un selector de fecha y día, así como un indicador vertical de franja horaria actual (puede ser subrrayar celdas).

B2. **Selección**: con un título "Selección" y una lista vertical con scroll, tiene fichas según la franja horaria seleccionada, cada ficha contiene el nombre del usuario con apellido, la fecha y hora, y la duración en minutos, además las fichas se distinguen en color según sean reservas o asociaciónes o eventos.

C. **Alertas**: tiene un título "Alertas" y luego un listado con scroll, donde se van colocando las fichas de alerta, cada ficha es pulsable y cuenta con 3 partes: tiempo en minutos para ordenar listado de alertas, nombe del dispositivo si lo hay, nombre del jugador truncado por si es muy largo. También la ficha tendrá un color indicando el tipo de alerta, si es asociación que ha pasado mucho tiempo abierta, o reserva que está por empezar.

### Vistas Usuarios (views/usuarios/*)

(requiere credenciales de Administrador o Master)

1. **index**: dividido en dos partes, a la izquierda la lista donde se buscan los registros de usuarios, mostrando(cédula, nombre con apellidos, estado, rol) y al pulsar un registro se carga la información completa en la parte derecha, mostrando (nombre con apellidos, cédula, código si lo hay, edad según año de nacimiento, género, estado, rol), además cuenta en el lado izquierdo filtros para (cédula o código, estado y rol). En la parte superior debe tener un botón para crear un nuevo usuario, lo que lleva a dicha interfaz. En el lado derecho donde se ven los detalles, aparecerá el botón de editar usuario, que lleva a dicha interfaz.

2. **create**: formulario para llenar los datos necesarios para crear un usuario. Se divide en dos partes, arriba la información básica, para crear al menos a un invitado (nombres, apellidos, cédula y botón para crear invitado). Abajo la información extra para un usuario de la comunidad (año de nacimiento, género, código de estudiante si lo tiene, rol, email, y botón para crear usuario de la comunidad).

3. **edit**: permite editar un usuario, similar a create pero incluye el cambio de estado. Puede tener también email y datos no presentes en invitado, por si se quiere convertir un invitado en usuario de comunidad.

### Vistas Eventos (views/eventos/*)

(requiere credenciales de Administrador o Master)

1. **index**: dividido en dos partes, a la izquierda la lista donde se buscan los registros de eventos, mostrando(nombre del evento, fecha de inicio, aforo, estado) y al pulsar un registro se carga la información completa en la parte derecha, mostrando (nombre del evento, descripción, nombre con apellidos del usuario organizador, fecha de inicio, duración, aforo, estado, si es auto administrado), además cuenta en el lado izquierdo filtros para (estado, cédula del organizador). En la parte superior debe tener un botón para crear un nuevo evento, lo que lleva a dicha interfaz. En el lado derecho donde se ven los detalles, aparecerá el botón de editar evento, que lleva a dicha interfaz.

2. **create**: formulario para llenar los datos necesarios para crear un evento (nombre, descripción, cédula del organizador, fecha de inicio, duración, aforo aproximado, si es auto administrado, y listado para agregar o quitar dispositivos vinculados).

3. **edit**: permite editar un evento, similar a create pero incluye el cambio de estado.

### Vistas Dispositivos (views/dispositivos/*)

(requiere credenciales de Administrador o Master)

1. **index**: dividido en dos partes, a la izquierda la lista donde se buscan los registros de dispositivos, mostrando(nombre del dispostivo, tipo de dispositivo, estado) y al pulsar un registro se carga la información completa en la parte derecha, mostrando (nombre del dispositivo, descripción, tipo de dispositivo, estado, y opcionalmente direccion si la hay), además cuenta en el lado izquierdo filtros para (estado, tipo dispositivo). En la parte superior debe tener un botón para crear un nuevo dispositivo, lo que lleva a dicha interfaz. En el lado derecho donde se ven los detalles, aparecerá el botón de editar dispositivo, que lleva a dicha interfaz.

2. **create**: formulario para llenar los datos necesarios para crear un dispisitivo (nombre, tipo de dispositivo, descripción).

3. **edit**: permite editar un dispositivo, similar a create pero incluye el cambio de estado.

### Vistas Observaciónes (views/observaciones/*)

(requiere credenciales de Administrador o Master)

1. **index**: se divide en dos partes, arriba permite hacer una observación, el formulario tiene (mensaje escrito, selector de asociación de lista reciente del día lo que es opcional). Abajo continúa un conjunto de varios filtros de búsqueda (cédula del administrador que hizo la observación, cádula o código del jugador, evento, dispositivo, fecha de observación, relación que dice con o sin asciación). Más abajo, se divide en dos, a la izquierda está un listado de observaciónes, filtradas. Lo que muestra la lista es (fecha del registro, mensaje pero truncado para no ocupar más de una línea). Y a la derecha, y al pulsar sobre una entrada, se ve el mensaje completo, el nombre con apellidos del admin que la hizo y del usuario de la asociación si la hay, así mismo el dsipositivo y evento asociados si los hay.

### Vistas Estadísticas (views/estadisticas/*)

(requiere credenciales de Administrador o Master)

1. **index**: (próximamente...).

### Vistas Auditorías (views/auditorias/*)

(requiere credenciales de Administrador o Master)

1. **index**: muestra un listado de auditorías, con filtros por (cédula de usuario, nombre de usuario, tipo de acción, nombre de tabla, día del registro). Las auditorías muestran la fecha del registro, el nombre de la acción, la tabla afectada, su indice y el nombre del usuario que lo hizo.

### Vistas Configuración (views/configuracion/*)

(para móvil y escritorio)

(requiere cualquier tipo de credenciales de inicio de sesión)

1. **index**: permite al usuario logeado cambiar algunos de sus datos, habrá una lista de opciónes por categorías agrupadas:

A. Cambio de contraseña, donde se pide la antigua y dos veces la nueva, con su botón de enviar.

### Vistas Reservaciones (views/reservaciones/*)

(para móvil y escritorio)

(requiere credenciales de sesión que No sean Administrador ni Master)

1. **index**: (próximamente...).

## Condiciónes de Servicios

(próximamente...).

## Instalar en Local

- recomendable usar XAMPP para la base de datos MySQL.
- recomendable usar un editor como VScode.
- tener instalado Composer.
- tener instalado Node.js con npm.
- tener instalado Git para clonar.

1. clona el repositorio GitHub a una espacio local.
2. `composer install` completa el proyecto Laravel.
3. `Copy-Item .env.example .env` obtiene de nuevo .env desde su ejemplo.
4. configura .env con las credenciales de la DB por ejemplo para XAMPP.
5. `php artisan key:generate` crea la llave local.
6. crear en MySQL (ej: XAMPP phpmyadmin), la DB nombrada en .env.
7. `php artisan migrate:fresh` destruye las tablas y las re-crea.
8. `php artisan db:seed` llena los datos iniciales.
9. `npm ci` o `npm install` dependiendo si existe package-lock.json o si no.
10. `npm run build` o `npm run dev` para compilar o ejecutar continuamente el frontend.
11. `php artisan serve` ejecutar el servidor (si usó npm run dev tendrá que abrir otra terminal).
12. `php artisan optimize:clear` opcional, si hay problemas de caché.

## Desarrollador

- Ing. Omar Jordan Jordan (Omwekiatl).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Derechos reservados a la Unicatólica Lumen Gentium (Cali, Colombia), empresa dueña del software.
