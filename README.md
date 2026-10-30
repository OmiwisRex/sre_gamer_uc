# SRE Gamer UC

## Frontend

### Vista Portada Login

Incluye login, registro, recuperación de contraseña y similares, es lo que se ve cuando no se ha identificado el usuario.

### Vista Configuración

Permite al usuario logeado cambiar algunos de sus datos, cambiar contraseña y en general administrar la App.

### Vista Reservación

Es la única que ve un usuario no administrador, desde allí puede ver horarios de la sala, alertas y ver, hacer o eliminar su propia reserva.

### Vista Menú Principal

Es la página de bienvenida, es también la estructura base donde todas las demás vistas post login yacen, por eso tiene pestañas o botónes para cambiar de sub-vista.

### Vista Administra Sala

Contiene todo lo necesario para la mayoría de acciónes administrativas, ahí se ven las alertas, datos del juegador seleccionado, donde puede ver o crear asociaciónes o reservas, puede ver también las horas que ha jugado en el día, además, muestra la línea de tiempo vs dispositivos según su uso.

### Vista CRUD Usuarios

Permite crear un nuevo usuario, editarlo y ver la lista de usuarios filtrados.

### Vista CRUD Dispositivos

Para crear dispositivos, actualizarlos y eliminarlos, también permite cambiar el estado de los mismos, por ejemplo para indicar que un dispositivo está en mantenimiento.

### Vista CRUD Eventos

Permite crear, actualiza o eliminar eventos, muestra también la línea de tiempo para poder acomodarlos.

### Vista Observaciónes

Allí se crean observaciónes con o sin asociación vinculada, además permite ver según filtro, otras observaciónes hechas.

### Vista Estadísticas

Para observar datos generales de uso de la sala gamer, permite ver gráficos filtrados y también extraer los datos para ser procesados por otro software, por ejemplo Excel.

### Vista Auditorías

Muestra según filtro, las acciónes administrativas hechas.

## Base de Datos

La tabla `usuarios` incluye jugadores y administradores, de la tabla `generos` y `roles` obtiene información poblacional, `sesiones` es opcional cuando son usuarios registrados con correo y contraseña para inicios de sesión, ahí estarán las credenciales, pero por ejemplo, los roles invitado no deberían tener registro en sesión, siempre `sesiones` y `usuarios` es 1:1.

La tabla `estados` cuenta con flags para identificar la máquina de estados de varias tablas.

La tabla `dispositivos` son los equipos de la sala, como computadores, consolas, etc, y `tipo_dispos` son los tipos de dispositivos que puede haber, por ejemplo, el PC1 es tipo PC, las reservaciónes se hacen por tipo y las asociaciónes por dispositivo, además, dispositivos tiene dos campos opcionales (direccion y password) en caso de que sea posible tener por ejemplo direcciónes IP para comandos remotos.

La tabla `auditorias` identifica qué usuarios ha hecho `acciones` sobre tablas y llaves primarias de sus registros, para seguimiento de deciciónes administrativas.

La tabla `asociaciones` es cuando un usuario jugador está actualmente usando una máquina, su fecha inicio es establecida al crear la asociación y su fecha final es colocada cuando se cierra la asociación (estado), solo puede haber una asociación activa por usuario, aunque habrá más inactivas en el pasado, además en caso de estar asociado a un evento, puede no necesariamente estar asociado a un dispositivo.

Las `observaciones` son comentarios dejados por un administrador, ya sea referenciados a una asociación o no, por ejemplo, el jugador dijo que un gamepad estaba malo durante la revisión del equipo, entonces su asociación se hace con esa observación, o si el administrador se dió cuenta después, edita la asociación o crea una observación sin asociación.

La tabla `reservas` es cuando un usuario pide la sala a futuro, el inicio dice cuándo y la duración define la franja en minutos, los tipos de dispositivos dependen entonces de la disponibilidad de los mismos, según la tabla `dispositivos` y su estado, cuando se reserva puede entrar en colisión con otras reservas y ser negada, pero tamién con asociaciónes siempre que estas hayan cumplido con su tiempo mínimo de juego (constante global).

Los `eventos` son similares a las reservas, pero pudiendo tener un nombre y descripción, un usuario a cargo, un apróximado de la cantidad de invitados (aforo), y un flag (auto_admin) que dice si es permitido o no para un jugador, hacer reservas y asociaciónes vinculadas internamente a ese evento, en caso negativo, se entiende que los encargados del evento administrarán los tiempos de uso de los equipos, aún así, los invitados al evento deberán registrarse asociados al evento más no a un dispositivo concreto.

Luego los eventos tienen `evento_dispos` que son los dispositivos de la sala que están reservados para un evento M:M.

Finalmente, el sistema hace CRUD por parte de los usuarios administradores, para:

- `usuarios`, que a veces afecta `sesiones`.
- `dispositivos`.
- `asociaciones` que a veces afecta a `observaciones`.
- `reservas`.
- `eventos` que afecta a `evento_dispos`.
- `observaciones` sin asociaciónes.

De manera automática se llena `auditorías`.

## Instalar en Local

1. clona el repositorio GitHub a una espacio local.
2. `composer install` completa el proyecto Laravel.
3. `Copy-Item .env.example .env` obtiene de nuevo .env desde su ejemplo.
4. configura .env con las credenciales de la DB por ejemplo para XAMPP.
5. `php artisan key:generate` crea la llave local.
6. crear en MySQL (ej: XAMPP phpmyadmin), la DB nombrada en .env.
7. `php artisan migrate:fresh` destruye las tablas y las re-crea.
8. `php artisan db:seed` llena los datos iniciales.
9. `npm ci` o `npm install` dependiendo si existe package-lock.json o si no.
10. `npm run build` para compilar el frontend.
11. `php artisan serve` ejecutar el servidor.
12. `php artisan optimize:clear` opcional, si hay problemas de caché.

## Desarrollador

- Ing. Omar Jordan Jordan (Omwekiatl).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Derechos reservados a la Unicatólica Lumen Gentium (Cali, Colombia), empresa dueña del software.
