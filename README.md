# SRE Gamer UC

## Instalar en Local

1. clona el repositorio GitHub a una espacio local
2. `composer install` completa el proyecto Laravel
3. `Copy-Item .env.example .env` obtiene de nuevo .env desde su ejemplo
4. configura .env con las credenciales de la DB por ejemplo para XAMPP
5. `php artisan key:generate` crea la llave local
6. `php artisan migrate` crea las tablas
7. `php artisan db:seed` llena los datos iniciales
8. `npm ci` o `npm install` dependiendo si existe package-lock.json o si no
9. `npm run build` para compilar el Frontend
10. `php artisan serve` ejecutar el servidor
11. `php artisan optimize:clear` opcional, si hay problemas de caché

## Desarrollador

- Omar Jordan Jordan (Omwekiatl)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Derechos reservados a la Unicatólica Lumen Gentium (Cali, Colombia), empresa dueña del software.
