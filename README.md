# CRM Culturizarte

CRM para la gestión comercial de Culturizarte: instituciones educativas, docentes vinculados, visitas comerciales y control de stock y entrega de muestras de textos escolares.

## Componentes

- **Portal web** (Laravel): administración, autenticación con Sanctum y roles.
- **App móvil** (`mobile/`, .NET MAUI): registro de visitas comerciales y entrega de muestras desde el campo, y consulta del inventario del asesor.

## Puesta en marcha (portal web)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # datos demo: usuarios, directorio y catálogo
npm install && npm run build
php artisan serve
```
