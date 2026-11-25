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

## Inventario real de libros (kardex)

El catálogo de demostración se reemplaza con el kardex de la editorial. Las cantidades son información de la empresa, por eso el archivo **no se sube a Git**:

1. En Excel, abrir el kardex (hoja con las columnas `CODIGO`, `NOMBRE`, `OBSERVACIONES`, `CANT`) y elegir *Guardar como → CSV UTF-8*.
2. Guardarlo en `storage/app/private/inventario/kardex.csv` (carpeta ignorada por Git).
3. Revisar y luego importar:

```bash
php artisan catalogo:importar --simular          # muestra cómo quedará el catálogo
php artisan catalogo:importar --desactivar-otros # importa y oculta los textos que no están en el kardex
```

Volver a importar actualiza el stock de bodega de cada código sin duplicar textos.
