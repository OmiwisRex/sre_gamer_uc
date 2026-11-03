# SGUC (Sala Gamer Universidad Católica)

## Base de Datos

**Nota**, la imágen `MR.png` contiene la DB ya exportada de MySQL en su modelo relacional.

La tabla `usuarios` incluye jugadores y administradores. De la tabla `generos` y `roles` obtiene información poblacional. `sesiones` es opcional cuando son usuarios registrados con correo y contraseña para inicios de sesión, ahí estarán las credenciales, pero por ejemplo, los roles invitado no deberían tener registro en sesión. Siempre `sesiones` y `usuarios` es 1:1.

La tabla `estados` cuenta con flags para identificar la máquina de estados de varias tablas.

La tabla `dispositivos` son los equipos de la sala, como computadores, consolas, etc, y `tipo_dispos` son los tipos de dispositivos que puede haber, por ejemplo, el PC1 es tipo PC. Las reservaciónes se hacen por tipo y las asociaciónes por dispositivo, además, dispositivos tiene dos campos opcionales (direccion y password) en caso de que sea posible tener por ejemplo direcciónes IP para comandos remotos.

La tabla `auditorias` identifica qué usuarios han hecho `acciones` sobre tablas y llaves primarias de sus registros, para seguimiento de deciciónes administrativas.

La tabla `asociaciones` es cuando un usuario jugador está actualmente usando una máquina. Su fecha inicio es establecida al crear la asociación y su fecha final es actualizada a medida que pasa el tiempo hasta cerrar la asociación modificando el estado. Solo puede haber una asociación activa por usuario, aunque habrá más inactivas en el pasado, además en caso de estar asociado a un evento, puede no necesariamente estar asociado a un dispositivo.

Las `observaciones` son comentarios dejados por un administrador, ya sea referenciados a una asociación o no, por ejemplo, el jugador dijo que un gamepad estaba malo durante la revisión del equipo, entonces su asociación se hace con esa observación, o si el administrador se dió cuenta después, crea una observación sin asociación.

La tabla `reservas` es cuando un usuario pide la sala a futuro, el inicio dice cuándo y la duración define la franja en minutos. Los tipos de dispositivos dependen entonces de la disponibilidad de los mismos, según la tabla `dispositivos` y su estado. Cuando se reserva puede entrar en colisión con otras reservas y ser negada, pero tamién con asociaciónes siempre que estas estén aún activas y en su tiempo mínimo de juego (constante global).

Los `eventos` son similares a las reservas, pero pudiendo tener un nombre y descripción, un usuario a cargo, un apróximado de la cantidad de invitados (aforo), y un flag (auto_admin) que dice si es permitido o no para un jugador, hacer reservas y asociaciónes vinculadas internamente a ese evento, en caso negativo, se entiende que los encargados del evento administrarán los tiempos de uso de los equipos, aún así, los invitados al evento deberán registrarse asociados al evento más no a un dispositivo concreto.

Luego los eventos tienen `evento_dispos` que son los dispositivos de la sala que están reservados para un evento M:M.

## Servicios que usan Controladores

cuando se trata de cursores, es porque se cargarán los datos bajo demanda, entonces al llegar el scroll del frontend abajo, se llama al resto.

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

### Observaciónes

1. buscar lista de observaciónes, usando cursor y filtrando por usuario administrador o usuario asociado a asociaciones, ordenando por fecha de registro.
2. crear una observación, ya sea sin asociación o ligada a una asociacion, el frontend permitirá escoger alguna de las recientemente activas.

### Estadísticas

(próximamente...)

### Auditorías

**Nota**, los registros de auditorías se crean automáticamente cuando se hacen otras consultas a los servicios.

1. buscar lista de auditorías, usando cursor y filtrando por acción y usuario, ordenando por fecha de registro.

### Configuración

1. envío de contraseña y dos veces la nueva, para efectuar cambio de la misma.

### Login

1. envío de credenciales para inicio de sesión, y verificación del mismo.
2. solicitud de cambio de contraseña, enviará una nueva contraseña aleatoria al correo asociado.

## Frontend

La App es para uso en escritorio, excepto por las vistas que especifiquen ser para móvil, que serán los únicos responsivos.

En `app.css` estarán las importaciónes, tipografía y colorimetría, luego en `estilos.css` irán todas las clases CSS para el HTML de toda la App.

### Vistas Login (views/sesion/*)

(para móvil y escritorio, y no usan el layout)

1. **index**: muestra el logo institucional arriba, permite ingresar credenciales de acceso y botón de ingresar, también tiene un botón tipo link a "recuperar contraseña".
2. **recuperar**: similar al index, pero esta vez permite ingresar el correo, y un botón para enviar ahí la nueva contraseña aleatoria, tiene un botón tip link sobre "ya tiene cuenta" para volver al login.

### Vistas Menú Principal (views/*)

1. **index**: es la página de bienvenida, cuenta con una imágen central, va dentro del layout.
2. **layout**: quien es la estructura base donde van todas las demás vistas post login, por eso tiene pestañas laterales para cambiar de sub-vista. Tiene un header con el logo institucional, el título y una ficha de usuario logeado. En su pie de página tine información institucional.

### Vistas Administración (views/administracion/*)

**Nota** el archivo `ej_godot.html` es un ejemplo de esta interfaz, creada en otro software, además las imágenes `mockup1.png` y `mockup2.png` son esa misma interfaz de ejemplo.

1. **index**: contiene todo lo necesario para la mayoría de acciónes administrativas, está conformada por módulos, se divide en dos partes: la de abajo tiene el módulo B, y la de arriba se subdivide en dos partes: la de la izquierda tiene el módulo A y la de la derecha el C.

A. **Jugador**: está subdividida en varios módulos, se divide en dos partes, la de la derecha con el módulo A4, la de la izquierda se subdivide en dos partes, la de arriba con el módulo A1, y la de abajo se subdivide en dos partes, la de la izquierda con el módulo A2 y la derecha con el módulo A3.

A1. **Búsqueda**: tiene como título "Jugador", tiene arriba un botón para crear nuevo jugador, y un indicador de su estado en la DB, tiene luego un espacio para escribir un ID con el botón de envío, y abajo un espacio para escribir el nombre y apellidos del usuario si lo encontró.

A2. **Reservar**: es una ficha que puede tener dos estados (título Reserva y Reservar): mostrar información de reserva si la hay (los mismos datos del formulario quemados, y dos botónes, cancelar y asociar) y mostrar formulario de creación de reserva si no la hay (selector de tipo de dispositivo, selector de evento, fecha de reserva, hora de reserva, minutos reservados, y botón de envío).

A3. **Asociar**: es una ficha que puede tener dos estados (tírulo Asociación y Asociar): mostrar información de asociación si la hay (los mismos datos del formulario quemados, y un botón de cerrar) y mostrar formulario de creación de asociación si no la hay (selector de dispositivo, selector de evento, espacio para escribir observación opcional, indicador superior de si la asociación proviene de reserva, y botón de envío).

A4. **Horas**: tiene un título "Horas" y luego un listado con scroll, donde se van collocando las entradas a modo de tabla, con 3 columnas: hora de inicio, dispositivo usado y fracción de horas acumuladas. Abajo fuera del scroll tiene un texto del total de horas acumuladas.

B. **Horarios**: se divide en dos espacios, el mayor y a la izquierda B1, y a la derecha de menor tamaño B2.

B1. **Tiempos**: es una rejilla que tiene etiquetas para las filas, asociadas a cada dispositivo (puede crecer en vertical scroll), y etiquetas arriba para las columnas, asociadas a las horas del día, de 6am a 12pm (crece horizontalmente scroll), cada cuadro puede tener o no un botón que indica ocupado, y su color depende de los ítems ahí, sean reservas asociaciónes o eventos. Además hay un selector de fecha y día, así como un indicador vertical de franja horaria actual (puede ser subrrayar celdas).

B2. **Selección**: con un título "Selección" y una lista vertical con scroll, tiene fichas según la franja horaria seleccionada, cada ficha contiene el nombre del usuario con apellido, la fecha y hora, y la duración en minutos, además las fichas se distinguen en color según sean reservas o asociaciónes o eventos.

C. **Alertas**: tiene un título "Alertas" y luego un listado con scroll, donde se van colocando las fichas de alerta, cada ficha es pulsable y cuenta con 3 partes: tiempo en minutos para ordenar listado de alertas, nombe del dispositivo si lo hay, nombre del jugador truncado por si es muy largo. También la ficha tendrá un color indicando el tipo de alerta, si es asociación que ha pasado mucho tiempo abierta, o reserva que está por empezar.

### Vistas Configuración (views/configuracion/*)

(para móvil y escritorio)

1. **index**: permite al usuario logeado cambiar algunos de sus datos, habrá una lista de opciónes por categorías agrupadas:

- Cambio de contraseña, donde se pide la antigua y dos veces la nueva, con su botón de enviar.













































### Vistas Reservaciones (views/reservaciones/*)

(para móvil y escritorio)

Es la única que ve un usuario no administrador, desde allí puede ver horarios de la sala, alertas y ver, hacer o eliminar su propia reserva.

### Vistas Usuarios (views/usuarios/*)

Permite crear un nuevo usuario, editarlo y ver la lista de usuarios filtrados.

### Vistas Dispositivos (views/dispositivos/*)

Para crear dispositivos, actualizarlos y eliminarlos, también permite cambiar el estado de los mismos, por ejemplo para indicar que un dispositivo está en mantenimiento.

### Vistas Eventos (views/eventos/*)

Permite crear, actualiza o eliminar eventos, muestra también la línea de tiempo para poder acomodarlos.

### Vistas Observaciónes (views/observaciones/*)

Allí se crean observaciónes con o sin asociación vinculada, además permite ver según filtro, otras observaciónes hechas.

### Vistas Estadísticas (views/estadisticas/*)

Para observar datos generales de uso de la sala gamer, permite ver gráficos filtrados y también extraer los datos para ser procesados por otro software, por ejemplo Excel.

### Vistas Auditorías (views/auditorias/*)

Muestra según filtro, las acciónes administrativas hechas.



































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
