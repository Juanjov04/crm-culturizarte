using System.Net;
using CulturizarteCRM.Mobile.Models;

namespace CulturizarteCRM.Mobile.Services;

/// <summary>
/// Sprint 6 - Operación offline-first y sincronización asíncrona (RF-01, RF-02).
///
/// Toda visita se guarda primero en el teléfono (SQLite) y luego se envía en
/// segundo plano. Al recuperar la conexión se envían las pendientes en un solo
/// paquete JSON y después se descarga el catálogo actualizado (colegios,
/// docentes e inventario). Si falla la red, las visitas siguen pendientes y se
/// reintentan después sin perder información.
/// </summary>
public class SincronizacionService
{
    private const int MaximoPorPaquete = 50;

    private readonly ApiClient _api;
    private readonly BaseLocal _local;
    private readonly SemaphoreSlim _enCurso = new(1, 1);

    public SincronizacionService(ApiClient api, BaseLocal local)
    {
        _api = api;
        _local = local;

        // Sincroniza sola en cuanto el teléfono vuelve a tener internet.
        Connectivity.Current.ConnectivityChanged += async (_, e) =>
        {
            if (e.NetworkAccess == NetworkAccess.Internet && _api.UsuarioActual is not null)
            {
                await SincronizarAsync();
            }
        };
    }

    /// <summary>Se dispara cuando cambian las visitas pendientes o el catálogo local.</summary>
    public event EventHandler? Cambio;

    public bool HayConexion => Connectivity.Current.NetworkAccess == NetworkAccess.Internet;

    public DateTime? UltimaSincronizacion { get; private set; }

    public string? UltimoError { get; private set; }

    // --- Catálogo maestro (CU-M01) ---

    public async Task<List<Institucion>> GetInstitucionesAsync()
    {
        var instituciones = await _local.GetInstitucionesAsync();
        if (instituciones.Count == 0 && HayConexion)
        {
            await DescargarCatalogoAsync();
            instituciones = await _local.GetInstitucionesAsync();
        }

        return instituciones;
    }

    public async Task<List<Producto>> GetInventarioAsync()
    {
        var inventario = await _local.GetInventarioAsync();
        if (inventario.Count == 0 && HayConexion)
        {
            await DescargarCatalogoAsync();
            inventario = await _local.GetInventarioAsync();
        }

        return inventario;
    }

    /// <summary>Descarga colegios, docentes e inventario. Devuelve false si no se pudo.</summary>
    public async Task<bool> DescargarCatalogoAsync()
    {
        if (!HayConexion)
        {
            return false;
        }

        try
        {
            // Solo se reemplaza el inventario si no quedan muestras por enviar,
            // para no perder los descuentos hechos sin conexión.
            await _local.GuardarInstitucionesAsync(await _api.GetInstitucionesAsync());
            if (await _local.ContarPendientesAsync() == 0)
            {
                await _local.GuardarInventarioAsync(await _api.GetInventarioAsync());
            }

            Cambio?.Invoke(this, EventArgs.Empty);
            return true;
        }
        catch (ApiException ex)
        {
            UltimoError = ex.Message;
            return false;
        }
    }

    // --- Registro de visitas (RF-01) ---

    /// <summary>Guarda la visita en el teléfono y, si hay conexión, la envía en segundo plano.</summary>
    public async Task GuardarVisitaAsync(NuevaVisita visita, Institucion institucion, Docente docente)
    {
        visita.CapturadaOffline = !HayConexion;
        var muestras = visita.Muestras.Count > 0 ? $" · {visita.Muestras.Sum(m => m.Cantidad)} muestra(s)" : "";

        await _local.GuardarVisitaAsync(
            visita,
            institucion.Nombre,
            $"{docente.NombreCompleto} · Interés {visita.NivelInteres} · {visita.EstadoAdopcion}{muestras}");
        await _local.DescontarStockAsync(visita.Muestras);
        Cambio?.Invoke(this, EventArgs.Empty);

        // No bloquea la pantalla: el envío sigue en segundo plano.
        _ = Task.Run(SincronizarAsync);
    }

    public Task<int> ContarPendientesAsync() => _local.ContarPendientesAsync();

    public Task<List<VisitaLocal>> GetNoSincronizadasAsync() => _local.GetNoSincronizadasAsync();

    public Task<int> ContarRegistradasHoyAsync() => _local.ContarRegistradasDesdeAsync(DateTime.Today);

    public async Task DescartarAsync(string uuid)
    {
        await _local.EliminarVisitaAsync(uuid);
        Cambio?.Invoke(this, EventArgs.Empty);
    }

    // --- Sincronización (RF-02) ---

    /// <summary>
    /// Envía las visitas pendientes y luego actualiza el catálogo local.
    /// Devuelve cuántas quedaron sincronizadas y cuántas rechazó el servidor.
    /// </summary>
    public async Task<(int Enviadas, int Rechazadas)> SincronizarAsync()
    {
        if (!HayConexion || _api.UsuarioActual is null)
        {
            return (0, 0);
        }

        // Si ya hay una sincronización en curso no se lanza otra.
        if (!await _enCurso.WaitAsync(0))
        {
            return (0, 0);
        }

        var enviadas = 0;
        var rechazadas = 0;
        try
        {
            UltimoError = null;
            List<VisitaLocal> lote;
            while ((lote = await _local.GetPendientesAsync(MaximoPorPaquete)).Count > 0)
            {
                var respuesta = await _api.SincronizarAsync(lote.Select(v => v.Json).ToList());

                foreach (var resultado in respuesta.Resultados)
                {
                    if (resultado.Estado == "rechazado")
                    {
                        rechazadas++;
                        await _local.MarcarAsync(resultado.UuidOffline, VisitaLocal.Rechazada, string.Join("\n", resultado.Errores));
                    }
                    else
                    {
                        enviadas++;
                        await _local.MarcarAsync(resultado.UuidOffline, VisitaLocal.Sincronizada, null);
                    }
                }

                Cambio?.Invoke(this, EventArgs.Empty);
            }

            UltimaSincronizacion = DateTime.Now;
            await DescargarCatalogoAsync();
        }
        catch (ApiException ex)
        {
            // Sin red o servidor caído: las visitas siguen pendientes para el próximo intento.
            UltimoError = ex.Status == HttpStatusCode.Unauthorized
                ? "Tu sesión expiró. Vuelve a iniciar sesión para sincronizar."
                : ex.Message;
        }
        finally
        {
            _enCurso.Release();
            Cambio?.Invoke(this, EventArgs.Empty);
        }

        return (enviadas, rechazadas);
    }

    /// <summary>Al cerrar sesión se borran los datos locales del asesor.</summary>
    public Task BorrarDatosLocalesAsync() => _local.BorrarTodoAsync();
}
