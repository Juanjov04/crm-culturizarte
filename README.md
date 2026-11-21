# CRM Culturizarte

CRM para la gestión comercial de Culturizarte: instituciones educativas, docentes vinculados y visitas comerciales.

## Componentes

- **Portal web** (Laravel): administración, autenticación con Sanctum y roles.
- **App móvil** (`mobile/`, .NET MAUI): registro de visitas comerciales desde el campo.

## Puesta en marcha (portal web)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```
