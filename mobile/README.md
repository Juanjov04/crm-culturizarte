# App móvil del asesor (.NET MAUI)

App Android para que el asesor comercial inicie sesión y registre sus visitas a colegios y docentes (Sprint 4).
Consume la API de Laravel en `/api/v1`.

## Cómo probarla

1. Levanta la API en la PC, escuchando en todas las interfaces:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Abre `mobile/CulturizarteCRM.Mobile` en Visual Studio Code (extensión .NET MAUI) o Visual Studio y ejecútala en un emulador Android:

   ```bash
   dotnet build -t:Run -f net8.0-android
   ```

3. En la pantalla de inicio de sesión, la URL del servidor por defecto es `http://10.0.2.2:8000/api/v1/` (así ve el emulador al `localhost` de la PC). En un celular físico, cámbiala por la IP de la PC en la red, por ejemplo `http://192.168.1.20:8000/api/v1/`.

4. Usuario de prueba (creado por `php artisan db:seed`): `asesor@culturizarte.test` / `password`.

## Pantallas

- **Inicio de sesión**: obtiene un token de Sanctum y lo guarda en el almacenamiento seguro del teléfono.
- **Inicio**: visitas registradas hoy, acceso a nueva visita y a mis visitas.
- **Nueva visita**: formulario en 3 pasos (institución y docente → datos de la visita → acuerdos y observaciones).
- **Mis visitas**: historial del asesor (deslizar hacia abajo para actualizar).

## Pendiente para el Sprint 6

Guardar las visitas en SQLite cuando no haya conexión y sincronizarlas después. La API ya acepta un `uuid_offline` generado en el teléfono, así que reenviar una misma visita no la duplica.
