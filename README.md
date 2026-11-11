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

B1. **Tiempos**: es una matriz de 48 franjas de 30 minutos que cubren el día completo, desde las 00:00 hasta las 24:00. Las columnas representan las franjas horarias y tienen scroll horizontal. Las filas se agrupan por tipo de dispositivo, con un fondo distintivo para cada fila de tipo, y debajo se muestran sus dispositivos individuales; el conjunto puede crecer con scroll vertical. Las reservas se representan en la fila del tipo de dispositivo porque no tienen una máquina asignada. Las asociaciones se representan en la fila del dispositivo concreto, y los eventos aparecen en las filas de todos los dispositivos que tienen asignados, por lo que un mismo evento puede ocupar varias filas verticales y varias franjas horarias. Cada registro ocupa todas las celdas de 30 minutos con las que se solape. Las reservas normales se indican en azul claro y las reservas vinculadas a un evento en azul oscuro; las asociaciones normales en amarillo y las asociaciones vinculadas a un evento en naranja; los eventos en rojo claro. El número representa la cantidad de registros de esa naturaleza presentes en la celda, contando cada asociación individualmente aunque varias compartan dispositivo. Si coinciden naturalezas distintas, sus indicadores y cantidades deben conservarse por separado, sin sumar eventos, reservas y asociaciones en un único número. Los dispositivos Activos se muestran con su colorimetría normal; los Bloqueados se muestran en gris y sus celdas pasan a gris oscuro cuando tienen asociaciones o eventos. Las asociaciones Eliminadas pueden dejar una sombra morada clara únicamente cuando la celda no contiene otra información; esa sombra no tiene número ni botón. Incluye selector de fecha y un indicador de la columna horaria actual, visible únicamente cuando la fecha seleccionada sea hoy.

B2. **Selección**: con un título "Selección" y una lista vertical con scroll. Al pulsar una celda de una fila de tipo, lista las reservas de ese tipo que se cruzan con la franja seleccionada; al pulsar una celda de un dispositivo, lista las asociaciones vigentes de ese dispositivo que se cruzan con dicha franja. Cada ficha muestra la información correspondiente del usuario, fecha y hora, duración y, cuando aplique, el evento relacionado. Las fichas distinguen por color las reservas y las asociaciones. Los eventos no se muestran como fichas en este componente. Las sombras moradas de asociaciones Eliminadas tampoco son pulsables ni generan elementos en Selección.

C. **Alertas**: tiene un título "Alertas" y un listado con scroll que se actualiza cíclicamente. Cada ficha es pulsable y muestra los minutos transcurridos desde que se disparó la alerta, el nombre del dispositivo si lo hay y el nombre del jugador truncado si es muy largo. Se muestran alertas por reservas dentro de su ventana de conversión, asociaciones Bloqueadas y asociaciones que alcanzaron su tiempo mínimo de uso respetable. Las más recientes aparecen primero. Al pulsar una ficha, el usuario correspondiente se carga en el módulo Jugador.

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

B. Botón de cerrar sesión.

### Vistas Reservaciones (views/reservaciones/*)

(para móvil y escritorio)

(requiere credenciales de sesión que No sean Administrador ni Master)

1. **index**: (próximamente...).

## Condiciónes de Servicios

### Administración -> Módulo Jugador

**Busqueda -> Buscar Jugador**:

1. Debe buscarse un solo usuario dada su cédula o código estudiantil, en el pánel de Búsqueda.
2. Si se halla, mostrar en el pánel de Resultado: rol, estado, nombres + apellidos, email, cédula - código si lo hay (en caso de ser invitado no mostraría email ni código, o si email o código son generados automáticamente al azar, ver regla de generación aleatoria). Si no se halla, fin del servicio.
3. Luego buscará si existe una **reserva** es decir, que ésta tenga estado en Activo o Bloqueado.
4. Si la encuentra, mostrará en el componente de Reserva: estado, evento (si lo hay), tipo de dispositivo, fecha y hora de inicio en Am/Pm, duración en minutos, y dos botónes: "Cancelar" y "Asociar" (si estado es Activo) o "Desasociar" (si estado es Bloqueado).
5. Sino entonces mostrar el forms para crear una nueva reserva, en el componente de Reserva.
6. Luego buscará si existe una **asociación** es decir, que ésta tenga su estado en Activo o Bloqueado.
7. Si la encuentra, mostrará en el componente Asociar: estado, evento (si lo hay), dispositivo, fecha y hora de inicio en Am/Pm, cuadro de texto no editable con scroll, con las observaciónes asociadas a esa asociación, si es que las hay, concatenándolas "obs1 - obs2 - obs3" sino no muestra el cuadro. Finalmente muestra un botón "Cerrar" y opcionalmente otro botón "Continuar" (solo si estado es Bloqueado).
8. Sino entonces mostrar el forms para crear una nueva asociación, en el componente de Asociar. Si hubo una reserva con estado Bloqueado (ítem 4), entonces colocar el id de reservación en el input oculto del forms de la asociación correspondiente. Precargar los inputs del forms con los datos de la reservación: evento si lo hay, lista de dispositivos filtrada según el tipo de dispositivo de la reservación.
9. En el componente de Horas, buscar para el usuario, las asociacións del día de hoy (con cualquier estado), calcularles sus horas (final - inicio) con 1 decimal, y colocar la lista así, ej: 08:21pm, PC 1, 0.2h (truncando el nombre del dispositivo para que no estire la interfaz).
10. Al final, abajo del componente de Horas, colocar el total de horas de la lista, con 1 decimal "Total: 3.2 horas".

**Nota**: de aquí en adelante, todos los cambios ejecutados en el Módulo Jugador siempre re cargan la búsqueda de jugador, para actualizar interfaces.

**Reservar -> Crear Reserva**:

El servicio crea una reserva para un usuario autenticado. La reserva representa el derecho de disponer de **un dispositivo de un tipo determinado** durante una franja horaria; no es una asociación ni identifica todavía una máquina concreta.

1. **Validar al usuario y sus registros vigentes**:
   - La operación la puede solicitar un usuario regular autenticado. No está disponible para los roles Administrador ni Master desde la vista de reservaciones.
   - El usuario debe tener estado **Activo**. Un usuario Bloqueado o Eliminado no puede reservar.
   - El usuario no puede tener otra reserva en estado **Activo** o **Bloqueado**, sin importar la fecha de esa reserva. Si ya tiene una, se rechaza la solicitud y se informa que debe cancelar o resolver la reserva existente.
   - El usuario puede tener una asociación vigente y crear una reserva para otro día. No puede crear una reserva para el día calendario actual mientras tenga una asociación vigente en estado Activo o Bloqueado.
   - Una asociación vigente y una reserva futura de otro día sí pueden coexistir. La reserva sigue siendo única: no puede tener más de una reserva vigente aunque sean para días distintos.

2. **Validar los datos de la solicitud**:
   - Debe recibirse `tipo_dispo_id`, `inicio`, `duracion_min` y, opcionalmente, `evento_id`.
   - La fecha y hora de inicio no pueden ser anteriores al momento actual. No se permiten reservas retroactivas.
   - La duración debe ser positiva y corresponder a una opción válida del selector de duración del frontend. El backend también debe validarla; no debe confiar únicamente en el selector.
   - Por ahora no existe un límite máximo de anticipación ni una discretización obligatoria de los minutos de inicio. El administrador define la anticipación permitida mediante la configuración operativa, si posteriormente se incorpora.
   - Los horarios de apertura y cierre de la sala no se validan en este servicio porque son externos al sistema. La comunidad debe conocerlos antes de reservar.
   - Calcular `fin_reserva = inicio + duracion_min`. Todos los análisis de disponibilidad deben cubrir la franja completa, no solamente el instante de inicio.

3. **Determinar el ámbito de la reserva**:
   - Si `evento_id` es `NULL`, es una reserva general. Compite únicamente con otras reservas generales del mismo tipo de dispositivo (`evento_id IS NULL`).
   - Si `evento_id` contiene un evento, es una reserva interna de ese evento. Compite únicamente con otras reservas del mismo evento y tipo de dispositivo. No compite con reservas de otros eventos ni con reservas generales, porque el evento constituye una sala independiente.
   - Las reservas vinculadas a un evento solo están permitidas cuando `auto_admin = 0`. Si `auto_admin = 1`, el administrador gestiona la participación y los tiempos del evento; no se permiten reservas de jugadores vinculadas a él.

4. **Validar el evento, cuando aplique**:
   - El evento debe existir y estar en estado **Activo**.
   - El inicio de la reserva debe encontrarse dentro del intervalo del evento: `evento.inicio <= reserva.inicio < evento.fin`, donde `evento.fin = evento.inicio + duracion_horas`.
   - La duración de la reserva puede superar la hora de finalización del evento. La restricción afecta al inicio de la reserva, no obliga a que toda su duración quede contenida en el evento.
   - Los dispositivos disponibles para esa reserva son exclusivamente los dispositivos en estado **Activo** vinculados al evento mediante `evento_dispos` y cuyo `tipo_dispo_id` coincida con el tipo solicitado.
   - Si el evento no tiene suficientes dispositivos activos de ese tipo para cubrir la reserva, se rechaza la operación.

5. **Aplicar la prioridad de los eventos**:
   - Los eventos tienen prioridad sobre reservas y asociaciones. Una reserva general no puede utilizar la capacidad de un dispositivo que esté asignado a un evento Activo durante cualquier parte de la franja solicitada.
   - Para una reserva general, los dispositivos vinculados a eventos que se solapen con la franja deben excluirse del conjunto de dispositivos candidatos durante toda la reserva. Si el evento solo ocupa una parte de la franja, ese dispositivo no sirve como candidato para esa reserva, porque se exige disponibilidad continua.
   - Dos eventos solo colisionan cuando sus intervalos se solapan y tienen al menos un dispositivo asignado en común. Si utilizan dispositivos diferentes pueden coexistir, incluso con horarios coincidentes.
   - La creación posterior de un evento puede desplazar reservas o asociaciones existentes por tener mayor prioridad. La gestión de esos registros anteriores corresponde al administrador; el servicio de creación de reserva no debe alterar eventos.

6. **Calcular la disponibilidad durante toda la franja**:
   - Se parte del conjunto de dispositivos en estado **Activo** del tipo solicitado y permitido para el ámbito de la reserva:
     - Reserva general: dispositivos activos del tipo solicitado que no estén comprometidos por un evento durante la franja.
     - Reserva de evento: dispositivos activos de ese tipo que pertenezcan al evento solicitado.
   - Una reserva consume capacidad exclusiva de **un dispositivo** durante toda su franja. Las reservas existentes en estado Activo o Bloqueado deben considerarse vigentes y consumen una unidad mientras sus intervalos se solapen con la solicitud nueva y pertenezcan al mismo ámbito y tipo.
   - Una reserva no tiene `dispositivo_id`: el usuario reserva por tipo. Por tanto, el servicio debe calcular la capacidad del conjunto de dispositivos, no comparar reservas como si cada una tuviera una máquina asignada por el usuario.
   - La reserva nueva debe poder contar con un mismo dispositivo candidato disponible durante toda su franja. No es suficiente que haya un dispositivo libre al inicio y otro distinto al final, ni se puede asumir que el usuario cambiará de máquina durante la reserva.
   - Para calcularlo, dividir la franja solicitada en segmentos usando los inicios y finales de las reservas, asociaciones y eventos que se solapen. En cada segmento se calcula la capacidad elegible y se descuentan:
     - Las reservas Activas o Bloqueadas del mismo tipo y ámbito que estén vigentes en ese segmento, contando una unidad por reserva.
     - Los dispositivos físicos bloqueados por asociaciones que todavía estén dentro de su tiempo mínimo, contando cada `dispositivo_id` una sola vez, aunque tenga varias asociaciones.
     - Los dispositivos ocupados por eventos, según las reglas del punto anterior.
   - Debe quedar capacidad para la reserva nueva en todos los segmentos y debe poder mantenerse un mismo dispositivo candidato libre durante el intervalo completo. Si la capacidad se agota en un solo segmento, se rechaza la reserva.
   - No se deben contar filas de asociaciones para medir capacidad. Se cuentan dispositivos distintos. Por ejemplo, cuatro jugadores asociados a una misma consola consumen una sola unidad de capacidad física, no cuatro.
   - Las asociaciones en estado Activo o Bloqueado se consideran actividad vigente. Su bloqueo de capacidad para nuevas reservas depende de su tiempo mínimo, descrito en **Asociar -> Crear Asociación**, no de que la asociación siga abierta indefinidamente.
   - Las asociaciones generales afectan reservas generales. Las asociaciones vinculadas a un evento afectan las reservas de ese mismo evento. Los dispositivos de un evento, además, están fuera del conjunto disponible para reservas generales durante el horario de ese evento.

7. **Regla temporal de las asociaciones que afectan la disponibilidad**:
   - Una asociación sin `reserva_id` tiene un mínimo de 30 minutos, medido desde `asociaciones.inicio`.
   - Una asociación con `reserva_id` utiliza como referencia `reservas.inicio`, no la hora real a la que el usuario llegó y se sentó. Así, llegar tarde no desplaza artificialmente el horario reservado ni perjudica otras reservas.
   - El tiempo transcurrido para la asociación vinculada a una reserva se calcula como `MAX(0, asociaciones.final - reservas.inicio)`. Para una asociación sin reserva se calcula como `asociaciones.final - asociaciones.inicio`.
   - El mínimo aplicable es de 30 minutos para una asociación normal y la duración de la reserva vinculada cuando esta sea superior a 30 minutos. En consecuencia, el umbral aplicable es `MAX(30, reservas.duracion_min)` cuando existe reserva, y 30 minutos cuando no existe.
   - Mientras el tiempo calculado sea inferior al mínimo aplicable, el dispositivo consume capacidad para nuevas reservas. Una vez alcanzado ese mínimo, la asociación puede continuar abierta, pero ya no impide crear reservas por ese motivo. Esto no cierra la asociación ni autoriza a expulsar automáticamente al jugador; los conflictos presenciales los resuelve el administrador.
   - Las asociaciones Bloqueadas también se consultan y siguen vigentes. Su `final` no se actualiza mientras permanezcan Bloqueadas; ese valor conserva el último instante confirmado por la actualización cíclica y sirve como referencia provisional. El bloqueo no se interpreta como una pausa confirmada del jugador: el administrador verifica presencialmente si continúa sentado. Si pulsa Continuar, la asociación vuelve a Activo y las siguientes actualizaciones de `final` incluyen todo el tiempo transcurrido desde el último `final`, suponiendo que el jugador permaneció allí durante el bloqueo. Si pulsa Cerrar mientras sigue Bloqueada, la asociación pasa a Eliminado y conserva el último `final`, sin contar el tiempo posterior a ese instante. No se requiere modificar el esquema de la base de datos para registrar un inicio de bloqueo.

8. **Resultado de disponibilidad**:
   - Si no existe capacidad continua, se rechaza la reserva y se informa el motivo: falta de dispositivos activos, evento que ocupa los dispositivos, reservas que consumen la capacidad o asociaciones aún dentro de su mínimo.
   - Si existe capacidad continua, se permite continuar con el registro. La aprobación no asigna un dispositivo específico al usuario; la reserva sigue siendo por tipo y el administrador gestiona la asignación física cuando el usuario llega.
   - Como `reservas` no contiene `dispositivo_id`, el dispositivo candidato no queda persistido ni fijado en la base de datos. El servicio debe validar una unidad física que pueda mantenerse libre durante toda la franja, pero la máquina concreta se decide al atender al usuario. Si en el futuro se requiere garantizar y conservar la identidad de la máquina desde la creación de la reserva, será necesario añadir una asignación persistente (por ejemplo, un `dispositivo_id` opcional o una tabla de asignaciones).

9. **Crear el registro**:
   - Insertar en `reservas` el `usuario_id` autenticado, `tipo_dispo_id`, `evento_id` (o `NULL`), `inicio`, `duracion_min` y estado **Activo**.
   - Crear la auditoría correspondiente con la acción **Creó Reserva**, la tabla `reservas`, el índice del registro creado y el usuario que ejecutó la operación.
   - La validación de capacidad y la inserción deben ejecutarse en una misma transacción. Se debe bloquear también el registro del usuario para impedir dos reservas simultáneas del mismo usuario, y bloquear de forma consistente los registros que representan el conjunto de dispositivos candidatos para impedir que dos solicitudes concurrentes consuman la misma última unidad disponible. La cancelación de una reserva debe respetar el mismo protocolo de bloqueo del conjunto de dispositivos.

10. **Vigencia y liberación de capacidad**:
    - Las reservas en estado Activo o Bloqueado consumen capacidad durante su intervalo programado.
    - Si el usuario cancela la reserva, esta pasa a estado **Eliminado** y deja de consumir capacidad.
    - Si no se convierte en asociación dentro de la tolerancia de 15 minutos posteriores a su hora de inicio, la reserva debe pasar automáticamente a **Eliminado**. La tarea que realiza esta limpieza debe ejecutarse periódicamente.
   - La limpieza automática normal debe vencer reservas en estado **Activo** que no hayan iniciado su conversión. Una reserva **Bloqueada** indica que el usuario está en el flujo de conversión a asociación y no debe eliminarse por el mismo proceso mientras esa operación esté en curso. Si se abandona el formulario, debe restaurarse a Activo o resolverse mediante una regla específica para conversiones abandonadas.
    - Una reserva que ya fue creada no se invalida retroactivamente por una asociación nueva. Las asociaciones pueden coexistir con reservas y el administrador resuelve presencialmente el posible reclamo del dispositivo.

**Reserva -> Cancelar**:

1. Pasa la reservación cargada a estado Eliminado.

**Reserva -> Asociar o Desasociar**:

1. Cambia el estado de la reservación cargada entre Activo y Bloqueado.

**Asociar -> Crear Asociación**:

El servicio registra que un usuario está utilizando la sala. La asociación representa el uso efectivo de un dispositivo o la participación en un evento. No tiene una duración máxima: permanece abierta hasta que el administrador la cierre. Mientras la asociación está **Activa**, su campo `final` se actualiza periódicamente. Si pasa a **Bloqueado**, `final` deja de avanzar mientras el administrador comprueba presencialmente si el jugador sigue sentado. El bloqueo no confirma que el jugador haya dejado de jugar ni descuenta automáticamente ese intervalo: al pulsar Continuar, el tiempo transcurrido desde el último `final` se considera parte del uso; al cerrar mientras está Bloqueada, el `final` permanece en el último instante registrado.

1. **Quién puede crearla y validación del usuario**:
   - Solo un usuario con rol **Administrador** o **Master** puede ejecutar este servicio desde el módulo de Administración.
   - El usuario que se va a asociar debe existir y estar en estado **Activo**. Los usuarios Bloqueados o Eliminados no pueden ser asociados.
   - Un usuario solo puede tener una asociación vigente, es decir, una asociación en estado **Activo** o **Bloqueado**. Si ya tiene una, no se crea otra; debe resolverse la existente mediante Continuar o Cerrar.
   - Una asociación vigente puede coexistir con una reserva futura de otro día. No puede coexistir con una reserva del mismo día: al crear una asociación independiente, cualquier reserva vigente de ese usuario cuyo inicio corresponda al día calendario actual debe pasar a **Eliminado**. Esto evita que un usuario que ya está jugando mantenga además una reserva para más tarde ese mismo día.
   - Si la reserva futura corresponde a otro día, se conserva. El usuario puede estar asociado ahora y mantener una única reserva para un día posterior.

2. **Datos recibidos**:
   - `usuario_id`: usuario que se asociará.
   - `dispositivo_id`: opcional, dispositivo donde jugará.
   - `evento_id`: opcional, evento al que pertenece la asociación.
   - `reserva_id`: opcional, reserva que se está convirtiendo en asociación. Este valor permite identificar el origen de la operación y conservar la relación para estadísticas.
   - Observación: opcional. Si se recibe, se crea el registro correspondiente en `observaciones`, asociado al administrador que ejecuta la operación y a la asociación creada.
   - No se permite crear una asociación sin dispositivo y sin evento.

3. **Combinaciones permitidas de dispositivo y evento**:
   - **Dispositivo sin evento**:
     - El dispositivo debe existir y estar en estado **Activo**.
     - No debe estar bajo la administración de un evento que se encuentre actualmente en curso. Los eventos tienen prioridad sobre las asociaciones generales.
     - Si existen otros usuarios asociados al mismo dispositivo, la operación puede continuar. Se deben mostrar sus nombres y el tiempo que llevan asociados para que el administrador confirme si están jugando juntos o si se debe escoger otro dispositivo.
   - **Dispositivo y evento**:
     - El evento debe existir, estar en estado **Activo** y encontrarse actualmente en curso: `evento.inicio <= ahora < evento.fin`.
     - El evento debe tener `auto_admin = 0`. En este modo se permiten las operaciones normales de asociación y reserva, pero siempre restringidas a los dispositivos que pertenecen al evento.
     - El dispositivo debe estar en estado **Activo** y aparecer en `evento_dispos` para ese evento. No basta con que sea del mismo tipo: debe ser uno de los dispositivos concretos asignados al evento.
     - Si el dispositivo ya tiene otros usuarios asociados, se muestran sus nombres y tiempos para que el administrador confirme si la asociación compartida es intencional. La presencia de otras asociaciones no constituye por sí sola un rechazo.
   - **Solo evento, sin dispositivo**:
     - El evento debe existir, estar en estado **Activo**, encontrarse actualmente en curso y tener `auto_admin = 1`.
     - Esta modalidad representa la participación en un evento cuyos encargados administran los dispositivos y los tiempos de uso. La asociación se registra al evento, sin asignar una máquina concreta.
   - **Sin dispositivo ni evento**: combinación inválida; se rechaza.
   - No se permite asociar un dispositivo a un evento con `auto_admin = 1`. En ese modo la asociación debe hacerse únicamente al evento.
   - No se permite asociar un dispositivo a un evento con `auto_admin = 0` si el dispositivo no pertenece a `evento_dispos`.

4. **Asociaciones simultáneas sobre un mismo dispositivo**:
   - No existe una restricción de una asociación por dispositivo. Varios usuarios pueden estar asociados al mismo dispositivo al mismo tiempo. Por ejemplo, varios jugadores pueden usar juntos una consola.
   - Las asociaciones de un mismo dispositivo se consideran una sola unidad física al calcular la disponibilidad de reservas. Para ese cálculo se cuenta `COUNT(DISTINCT dispositivo_id)`, no el número de asociaciones.
   - El SQL no define un máximo de jugadores por dispositivo. Por tanto, el servicio no debe inventar un límite numérico; la decisión de permitir el uso compartido corresponde al administrador presencialmente.
   - La existencia de asociaciones simultáneas debe quedar visible en la interfaz para ayudar al administrador a distinguir una partida compartida de una ocupación accidental.

5. **Reservas de otros usuarios durante la asociación**:
   - Una reserva no impide crear una asociación sobre un dispositivo del mismo tipo y ámbito. La reserva tiene prioridad sobre la asociación, pero no bloquea por completo el uso temporal del dispositivo.
   - Si existe una reserva Activa o Bloqueada de otro usuario cuyo intervalo incluya el momento actual, para el mismo tipo de dispositivo y ámbito (reserva general o mismo evento), el sistema debe advertir al administrador antes de confirmar.
   - La advertencia debe indicar que el titular de la reserva puede llegar durante su franja y reclamar el dispositivo. Si llega, el administrador puede cerrar o trasladar la asociación del usuario que está ocupándolo.
   - La advertencia no es un rechazo automático. El administrador puede confirmar la asociación y asumir la gestión presencial del posible conflicto.
   - Las reservas vinculadas a eventos solo se comparan con asociaciones del mismo evento. Las reservas generales se comparan con asociaciones generales. Los eventos conservan su prioridad: una asociación no puede utilizar un dispositivo de un evento distinto mientras ese evento esté en curso.

6. **Conversión de una reserva en asociación**:
   - Esta modalidad se utiliza cuando el administrador pulsa **Asociar** sobre una reserva del usuario.
   - La reserva debe pertenecer al mismo usuario, estar en estado **Bloqueado** (flag temporal mientras se completa el formulario) y seguir dentro de su tolerancia de conversión: desde 15 minutos antes hasta 15 minutos después de `reservas.inicio`.
   - El dispositivo seleccionado debe estar en estado Activo, ser del mismo `tipo_dispo_id` de la reserva y, si la reserva tiene evento, pertenecer a ese evento mediante `evento_dispos`.
   - El evento de la asociación debe coincidir con el `evento_id` de la reserva. No se puede convertir una reserva de un ámbito a una asociación de otro.
   - Se guarda el `reserva_id` en la nueva asociación. Esta relación identifica el origen de la asociación para estadísticas; no representa una modalidad distinta de uso del dispositivo.
   - Al completar correctamente la asociación, la reserva pasa a estado **Eliminado**. No se deja en Activo ni en Bloqueado, porque ya fue consumida por la asociación.
   - Mientras se completa el formulario, la reserva permanece Bloqueada y sigue considerándose vigente. Si el administrador cancela el formulario o la creación falla antes de confirmar, la reserva debe volver a estado **Activo**.
   - La conversión y la creación de la asociación deben ejecutarse de forma atómica: o se crea la asociación y se elimina la reserva, o no se crea la asociación y la reserva se restaura a Activo.

7. **Asociación independiente y reservas del mismo usuario**:
   - Si se crea una asociación sin convertir una reserva concreta, y el usuario tiene una reserva vigente cuyo inicio cae en el día calendario actual, dicha reserva pasa a **Eliminado** al confirmar la asociación.
   - Si la reserva del usuario corresponde a otro día, se conserva.
   - Si la asociación se crea a partir de una reserva, se aplica la regla del punto anterior: se elimina exactamente la reserva indicada por `reserva_id` al confirmar la operación.
   - La comparación de días debe realizarse con la zona horaria operativa de la aplicación, no con una fecha UTC distinta de la utilizada por la sala.

8. **Inicio, final y acumulación del tiempo**:
   - Al crear la asociación, `asociaciones.inicio` registra el momento real en que se crea la asociación y `asociaciones.final` se inicializa con ese mismo momento.
   - Mientras la asociación esté **Activa**, un proceso periódico actualiza `asociaciones.final` con el momento actual. Así se mantiene actualizado el tiempo acumulado.
   - Cuando la asociación pasa a **Bloqueado**, el proceso deja de actualizar `final`. El administrador verifica si el usuario sigue sentado en el dispositivo. El último valor de `final` queda como el instante de referencia del bloqueo; no se crea un nuevo campo ni se altera el esquema SQL.
   - Una asociación Bloqueada sigue siendo vigente y genera una alerta hasta que el administrador la resuelva. Durante la verificación, el contador visible permanece estático en el último `final`.
   - Si el administrador pulsa Continuar, se cambia el estado a **Activo** y se actualiza `final` al momento actual. Al conservar `inicio`, el intervalo completo desde el último `final` hasta la reanudación queda incluido en el tiempo total, bajo la suposición de que el usuario permaneció jugando.
   - Si el administrador pulsa Cerrar mientras la asociación está Bloqueada, pasa a Eliminado y conserva el último `final`; el tiempo posterior al último registro no se cuenta. Si se cierra mientras está Activa, `final` se actualiza al momento del cierre.
   - Las asociaciones no tienen una duración máxima. Pueden permanecer abiertas más allá del mínimo de 30 minutos; superar ese mínimo solo significa que dejan de bloquear capacidad para nuevas reservas, no que la asociación se cierre.
   - **Referencia temporal para el mínimo de bloqueo de capacidad**:
     - Si la asociación no tiene `reserva_id`, el inicio de referencia es `asociaciones.inicio` y el tiempo calculado es `asociaciones.final - asociaciones.inicio`.
     - Si tiene `reserva_id`, el inicio de referencia es `reservas.inicio` y el tiempo calculado es `MAX(0, asociaciones.final - reservas.inicio)`. No se utiliza la llegada tardía del usuario como nuevo punto de partida, para no desplazar el horario reservado.
     - El mínimo es 30 minutos para asociaciones normales. Para asociaciones procedentes de una reserva se utiliza la duración reservada cuando sea superior a 30 minutos; por tanto, el umbral es `MAX(30, reservas.duracion_min)`.
     - Mientras el tiempo calculado sea inferior al umbral, el dispositivo consume capacidad al validar nuevas reservas. Una vez alcanzado el umbral, la asociación puede continuar abierta sin bloquear nuevas reservas.

9. **Confirmación y persistencia**:
   - Una vez superadas las validaciones y las advertencias confirmadas por el administrador, crear la asociación en estado **Activo**, con el usuario, dispositivo (si aplica), evento (si aplica), reserva (si aplica), `inicio` y `final`.
   - Si se recibió una observación, crearla vinculada a la asociación y al administrador que ejecutó la operación.
   - Crear la auditoría correspondiente con la acción **Creó Asociación**, tabla `asociaciones`, índice del registro creado y usuario administrador que ejecutó la operación.
   - La creación de la asociación, la eliminación de la reserva convertida y la eliminación de una reserva independiente del mismo día deben formar parte de una misma transacción. Se debe bloquear el registro del usuario mientras se comprueba que no tenga otra asociación vigente y se resuelve su reserva del día. Si una parte falla, no deben quedar estados parciales.

10. **Criterio de prioridad durante la operación**:
    - Los eventos tienen prioridad sobre reservas y asociaciones. Un dispositivo asignado a un evento en curso no puede utilizarse para una asociación general.
    - Las reservas tienen prioridad sobre asociaciones. Sin embargo, una asociación puede crearse aunque exista una reserva coincidente, siempre mostrando la advertencia descrita anteriormente.
    - Las asociaciones pueden coexistir entre sí sobre un dispositivo. La interfaz y el administrador presencial determinan si los usuarios comparten una partida o si debe cerrarse alguna asociación.

**Asociación -> Cerrar**:

1. Pasa la asociación a estado Eliminado. Si su estado anterior era Activo, actualiza la datetime "final" al momento actual.

**Asociación -> Continuar**:

1. Pasa el estado de la asociación a Activo y actualiza su datetime "final" al momento actual.

### Administración -> Módulo Jugador

**Actualización Cíclica**:

1. La interfaz ejecuta un único ciclo mediante un `setInterval` de JavaScript cada 5 segundos mientras el módulo de Administración permanezca abierto. El ciclo actualiza los datos visibles sin recargar la página completa.
2. La primera operación del ciclo es actualizar `asociaciones.final` al momento actual para todas las asociaciones en estado **Activo** que tengan `dispositivo_id` no nulo. Esta actualización debe ejecutarse antes de consultar el horario y las alertas, para que ambas consultas utilicen los tiempos más recientes. No se actualiza `final` de asociaciones Bloqueadas, Eliminadas ni de asociaciones sin dispositivo.
3. Si hay un usuario cargado en el módulo Jugador, consultar nuevamente todas sus asociaciones cuya fecha de actividad corresponda al día actual de la sala, sin filtrar por estado. Esto incluye asociaciones Activas, Bloqueadas y Eliminadas del día.
4. Para cada asociación del listado, calcular el tiempo mostrado como `MAX(0, asociaciones.final - asociaciones.inicio)`, expresado en horas con un decimal. El total es la suma de los tiempos individuales. Este cálculo de horas de juego utiliza el inicio real de la asociación; no debe confundirse con el cálculo del tiempo mínimo de bloqueo de capacidad de una asociación proveniente de reserva, que toma como referencia `reservas.inicio`.
5. Las asociaciones Activas con dispositivo avanzan porque el primer paso actualiza `final`. Las asociaciones Bloqueadas y Eliminadas permanecen estáticas mientras no cambie su estado o su `final`. Una asociación nueva o cerrada durante el día aparece en la siguiente respuesta del ciclo, sin que el administrador tenga que volver a buscar al usuario.
6. Si el usuario seleccionado cambia, el siguiente ciclo debe consultar las asociaciones del nuevo usuario. Si no hay usuario seleccionado, se omite únicamente la consulta de sus horas; el resto del ciclo continúa.
7. El frontend actualiza el listado y el total de horas en el componente A4 sin reconstruir la página ni perder el usuario seleccionado. El ciclo no debe crear temporizadores duplicados ni iniciar otra petición si la petición anterior continúa pendiente. Si una petición falla, se conserva la última información visible y se intenta nuevamente en el siguiente ciclo.

### Administración -> Módulo Horarios

**Tiempos**:

1. El componente representa un día completo, desde las 00:00 hasta las 24:00, en 48 columnas consecutivas de 30 minutos. Las columnas tienen scroll horizontal y las filas scroll vertical.
2. Las filas se organizan jerárquicamente por `tipo_dispos` y sus `dispositivos`. La fila del tipo tiene un fondo distintivo y funciona como agrupador de los dispositivos que pertenecen a ese tipo.
3. Las **reservas** se representan en la fila del tipo de dispositivo, nunca en una fila de máquina concreta, porque `reservas` solo almacena `tipo_dispo_id` y no asigna un `dispositivo_id`. Las reservas en estado Activo o Bloqueado se cuentan en cada franja de 30 minutos con la que se solape su intervalo `[inicio, inicio + duracion_min)`.
4. Las **asociaciones** se representan en la fila del dispositivo indicado por `dispositivo_id`. Las asociaciones en estado Activo o Bloqueado se cuentan en cada franja que se solape con `[inicio, final)`. Cada registro de asociación cuenta por separado; por tanto, si cuatro usuarios están asociados a la misma consola durante una franja, el contador de asociaciones de esa celda será cuatro.
5. Los **eventos** se representan en cada fila de dispositivo incluido en `evento_dispos`. Cada evento ocupa todas las franjas de su intervalo `[inicio, inicio + duracion_horas)` y todas las filas de sus dispositivos asignados. Por eso, un mismo evento puede ocupar varias celdas horizontales y varias filas verticales. Se cuentan los eventos que se solapan con cada celda.
6. Un elemento ocupa todas las celdas de 30 minutos que intersequen con su intervalo, aunque solo cubra una parte de la franja. Los intervalos se interpretan como `[inicio, fin)`: si un elemento termina exactamente al comienzo de una celda, no ocupa esa celda.
7. La colorimetría de las celdas es:
   - Reserva general: azul claro, con la cantidad de reservas.
   - Reserva vinculada a un evento: azul oscuro, con la cantidad de reservas de evento.
   - Asociación sin evento: amarillo, con la cantidad de asociaciones.
   - Asociación vinculada a un evento: naranja, con la cantidad de asociaciones del evento.
   - Evento asignado al dispositivo: rojo claro, con la cantidad de eventos.
   - Asociación Eliminada: sombra morada clara, únicamente cuando la celda no contiene ninguna otra información. La sombra no muestra número ni botón y no se incluye en Selección.
8. Cuando en una misma celda coincidan elementos de naturalezas distintas, el servicio debe entregar sus cantidades por separado. El frontend debe conservar los indicadores y colores de cada naturaleza dentro de la celda, sin sumar reservas, asociaciones y eventos en un único contador.
9. Los dispositivos **Activos** se muestran con la colorimetría normal. Los dispositivos **Bloqueados** se muestran en gris, como no disponibles; si tienen una asociación o un evento en una franja, esa celda se representa en gris oscuro. Los dispositivos Eliminados no forman parte del listado operativo normal.
10. Las asociaciones sin `dispositivo_id` (asociaciones vinculadas únicamente a un evento) no se atribuyen a una máquina concreta y no generan ocupación en ninguna fila física. El evento relacionado sí aparece en las filas de los dispositivos que tenga asignados.
11. Cada celda con información operativa presenta un botón con sus indicadores y cantidades. Las celdas vacías no presentan botón. La sombra morada de asociaciones Eliminadas es solo visual y tampoco es pulsable.
12. El selector permite cambiar la fecha y consultar días futuros o pasados. La columna correspondiente a la hora actual se resalta únicamente cuando la fecha seleccionada sea el día actual de la sala.
13. El horario se vuelve a consultar en cada actualización cíclica de 5 segundos, después de actualizar `final` de las asociaciones Activas con dispositivo. También se consulta al entrar al módulo o al cambiar la fecha seleccionada.
14. Las filas de tipo y de dispositivo tienen significados distintos: al seleccionar una celda de tipo se consultan reservas del tipo; al seleccionar una celda de dispositivo se consultan asociaciones de ese dispositivo. No se debe inventar una asignación física de reserva, ya que la base de datos no la conserva.

**Selección**:

1. Al pulsar una celda de una fila de tipo, consultar las reservas en estado Activo o Bloqueado de ese `tipo_dispo_id` cuyo intervalo se cruce con la franja seleccionada.
2. Al pulsar una celda de dispositivo, consultar las asociaciones en estado Activo o Bloqueado de ese `dispositivo_id` cuyo intervalo `[inicio, final)` se cruce con la franja seleccionada.
3. Las fichas muestran los datos necesarios para identificar al usuario y el registro: nombre y apellido, fecha y hora, duración, dispositivo o tipo de dispositivo y evento relacionado cuando aplique. Las fichas de reservas se distinguen en azul claro o azul oscuro según sean generales o vinculadas a un evento; las asociaciones se distinguen en amarillo o naranja según estén vinculadas a un evento.
4. Los eventos no se muestran como fichas en Selección, aunque sí ocupan visualmente las celdas de sus dispositivos en Tiempos. Las asociaciones Eliminadas tampoco se listan: únicamente pueden dejar la sombra morada no interactiva descrita en Tiempos.
5. La lista corresponde exclusivamente a la fila y franja pulsadas. Las reservas se consultan por tipo; las asociaciones, por dispositivo. No se asigna artificialmente una reserva a una de las máquinas de su tipo.
6. Al cambiar la fecha, seleccionar otra celda o actualizarse el horario, la selección debe corresponder a la celda vigente. Si deja de existir la celda o cambia la fila/fecha seleccionada, se limpia o reconstruye la lista para evitar mostrar información de otra franja.

### Administración -> Módulo Alertas

**Actualización Cíclica**:

1. La lista se consulta y actualiza en cada ciclo de 5 segundos. La actualización ocurre después de refrescar `final` de las asociaciones Activas que tengan dispositivo.
2. Se generan únicamente las siguientes clases de alerta:
   - **Reserva en ventana de conversión**: reserva en estado Activo cuya hora actual esté entre 15 minutos antes y 15 minutos después de `reservas.inicio`. La alerta desaparece cuando la reserva pasa a Bloqueado para convertirse, pasa a Eliminado o sale de la ventana. Una reserva Bloqueada no genera esta alerta.
   - **Asociación Bloqueada**: asociación en estado Bloqueado. La alerta aparece desde el instante de bloqueo y permanece hasta que el administrador la continúe o la cierre. El instante de disparo se toma de `asociaciones.final`, que conserva el último instante actualizado antes del bloqueo. No se agrega un campo nuevo ni se modifica la base de datos para registrar el bloqueo.
   - **Asociación que superó el tiempo mínimo respetable**: asociación en estado Activo con `dispositivo_id` no nulo que alcanzó su umbral mínimo. Para una asociación sin reserva, el umbral es 30 minutos desde `asociaciones.inicio`. Para una asociación con `reserva_id`, el umbral es `MAX(30, reservas.duracion_min)` contado desde `reservas.inicio`. El instante de disparo es el inicio de referencia más dicho umbral. Las asociaciones sin dispositivo no generan esta alerta.
3. Una asociación Bloqueada genera solamente su alerta de bloqueo, nunca además una alerta por superar el mínimo. Una asociación Eliminada no genera alertas. Una asociación Activa que ya superó el mínimo conserva una sola alerta de exceso, no una ficha nueva en cada ciclo.
4. La antigüedad de cada alerta se calcula en minutos desde su instante de disparo, usando minutos completos transcurridos. Para reservas se usa `reservas.inicio - 15 minutos`; para asociaciones Bloqueadas se usa el último `asociaciones.final`; para asociaciones que superaron el mínimo se usa el instante exacto en que alcanzaron el umbral descrito.
5. Las alertas se ordenan desde la más reciente hasta la más antigua, es decir, menor cantidad de minutos de existencia primero. Si varias tienen la misma antigüedad, se conserva un orden estable, por ejemplo por tipo y llave primaria.
6. Cada ficha muestra la antigüedad en minutos, el nombre del dispositivo si existe y el nombre del usuario truncado si es necesario. La apariencia o color de la ficha debe permitir distinguir si corresponde a reserva, asociación Bloqueada o exceso del tiempo mínimo.
7. Al pulsar una ficha, se carga su `usuario_id` en el módulo Jugador y se ejecuta la búsqueda/actualización normal de ese usuario. No se navega fuera del módulo ni se recarga la página completa.
8. Si una alerta deja de cumplir sus condiciones, debe desaparecer en el siguiente ciclo. La lista no debe duplicar alertas de una misma reserva o asociación.
9. Las alertas son una herramienta de intervención del administrador presencial. No cancelan reservas, no cierran asociaciones y no expulsan usuarios automáticamente.

### Eventos

**Create -> Crear Evento**:

1. El servicio solo permite crear eventos a un usuario autorizado para administrar eventos. El registro nuevo queda en estado **Activo**.
2. Deben recibirse `nombre`, `descripcion`, `usuario_id` del organizador, `inicio`, `duracion_horas`, `aforo`, `auto_admin` y la lista de `dispositivo_id` que integrarán el evento.
3. **Validar los datos**:
   - El nombre debe ser único.
   - El organizador debe existir y estar en estado Activo.
   - `inicio` debe ser posterior al momento actual; no se permiten eventos retroactivos ni que comiencen en el instante actual.
   - `duracion_horas` y `aforo` deben ser mayores que cero.
   - `auto_admin` debe tener un valor booleano válido.
   - El evento debe tener al menos un dispositivo.
   - Todos los dispositivos seleccionados deben existir, estar en estado Activo y no repetirse en la lista enviada.
4. **Calcular el intervalo del evento**: `fin = inicio + duracion_horas`. El intervalo se interpreta como `[inicio, fin)`, de modo que dos eventos consecutivos pueden tocarse en el límite sin colisionar.
5. **Buscar colisiones únicamente con eventos Activos**. Un evento existente colisiona si su intervalo se solapa con el nuevo y comparte al menos un `dispositivo_id`. La colisión se determina por la intersección entre los dispositivos nuevos y los registrados en `evento_dispos` para los eventos existentes.
6. Si existe una colisión, rechazar la creación e informar los nombres de los dispositivos concretos que producen el conflicto. No basta con informar que hay un evento en el mismo horario.
7. Si los horarios se solapan, pero los eventos utilizan conjuntos de dispositivos completamente distintos, permitir la creación.
8. Los eventos Bloqueados o Eliminados no participan en la validación de colisiones.
9. **No comprobar ni rechazar por reservas o asociaciones existentes**. El evento tiene prioridad sobre ambas. Su creación no debe mostrar advertencias por esos registros ni eliminarlos, bloquearlos o cambiarles el estado automáticamente. La resolución posterior de esos conflictos corresponde al administrador presencial.
10. Al aprobarse la validación, crear el registro en `eventos` y sus relaciones en `evento_dispos` dentro de una transacción. Si falla la creación de cualquiera de las relaciones, revertir la operación completa para no dejar un evento con una asignación parcial de dispositivos.
11. La comprobación de colisiones y la inserción deben ejecutarse con bloqueo transaccional consistente sobre los dispositivos seleccionados, para evitar que dos solicitudes administrativas concurrentes creen eventos incompatibles sobre una misma máquina. La existencia de un solo administrador habitual reduce la concurrencia administrativa, pero no elimina la necesidad de proteger la integridad frente a solicitudes simultáneas.

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

- The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

- Derechos reservados a la Unicatólica Lumen Gentium (Cali, Colombia), empresa dueña del software.
