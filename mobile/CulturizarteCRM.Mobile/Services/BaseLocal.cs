using System.Text.Json;
using CulturizarteCRM.Mobile.Models;
using SQLite;

namespace CulturizarteCRM.Mobile.Services;

/// <summary>Copia local del catálogo maestro descargado del servidor (CU-M01).</summary>
[Table("catalogo")]
public class CatalogoLocal
{
    [PrimaryKey] public string Clave { get; set; } = "";
    public string Json { get; set; } = "";
    public DateTime ActualizadoEn { get; set; }
}

/// <summary>Visita registrada en el teléfono a la espera de enviarse (RF-01).</summary>
[Table("visitas_locales")]
public class VisitaLocal
{
    public const string Pendiente = "pendiente";
    public const string Sincronizada = "sincronizado";
    public const string Rechazada = "rechazado";

    [PrimaryKey] public string Uuid { get; set; } = "";
    public string Json { get; set; } = "";
    [Indexed] public string Estado { get; set; } = Pendiente;
    public string? Error { get; set; }
    public string Titulo { get; set; } = "";
    public string Detalle { get; set; } = "";
    public DateTime CreadaEn { get; set; }
    public DateTime? SincronizadaEn { get; set; }

    [Ignore] public string FechaTexto => CreadaEn.ToString("dd/MM/yyyy HH:mm");
    [Ignore] public bool EsRechazada => Estado == Rechazada;
    [Ignore] public string EstadoTexto => Estado switch
    {
        Pendiente => "Pendiente de sincronizar",
        Rechazada => "Rechazada por el servidor",
        _ => "Sincronizada",
    };
}

/// <summary>
/// Sprint 6 - Persistencia local en SQLite (offline-first). Guarda el catálogo
/// descargado (instituciones, docentes e inventario) y la cola de visitas.
/// </summary>
public class BaseLocal
{
    private const string ClaveInstituciones = "instituciones";
    private const string ClaveInventario = "inventario";

    private static readonly JsonSerializerOptions Json = new(JsonSerializerDefaults.Web);
    private readonly SemaphoreSlim _inicio = new(1, 1);
    private SQLiteAsyncConnection? _db;

    private async Task<SQLiteAsyncConnection> DbAsync()
    {
        if (_db is not null)
        {
            return _db;
        }

        await _inicio.WaitAsync();
        try
        {
            if (_db is null)
            {
                var ruta = Path.Combine(FileSystem.AppDataDirectory, "crm_culturizarte.db3");
                var db = new SQLiteAsyncConnection(ruta, SQLiteOpenFlags.ReadWrite | SQLiteOpenFlags.Create | SQLiteOpenFlags.SharedCache);
                await db.CreateTableAsync<CatalogoLocal>();
                await db.CreateTableAsync<VisitaLocal>();
                _db = db;
            }

            return _db;
        }
        finally
        {
            _inicio.Release();
        }
    }

    // --- Catálogo maestro ---

    public Task GuardarInstitucionesAsync(List<Institucion> instituciones) => GuardarAsync(ClaveInstituciones, instituciones);

    public Task GuardarInventarioAsync(List<Producto> productos) => GuardarAsync(ClaveInventario, productos);

    public async Task<List<Institucion>> GetInstitucionesAsync() => await LeerAsync<List<Institucion>>(ClaveInstituciones) ?? new();

    public async Task<List<Producto>> GetInventarioAsync() => await LeerAsync<List<Producto>>(ClaveInventario) ?? new();

    public async Task<DateTime?> UltimaDescargaAsync()
        => (await (await DbAsync()).FindAsync<CatalogoLocal>(ClaveInventario))?.ActualizadoEn;

    /// <summary>Descuenta del inventario local las muestras entregadas sin conexión.</summary>
    public async Task DescontarStockAsync(IEnumerable<NuevaMuestra> muestras)
    {
        var inventario = await GetInventarioAsync();
        foreach (var muestra in muestras)
        {
            var producto = inventario.FirstOrDefault(p => p.Id == muestra.ProductoId);
            if (producto is not null)
            {
                producto.StockAsesor = Math.Max(0, producto.StockAsesor - muestra.Cantidad);
            }
        }

        await GuardarInventarioAsync(inventario);
    }

    // --- Cola de visitas ---

    public async Task GuardarVisitaAsync(NuevaVisita visita, string titulo, string detalle)
    {
        await (await DbAsync()).InsertOrReplaceAsync(new VisitaLocal
        {
            Uuid = visita.UuidOffline,
            Json = JsonSerializer.Serialize(visita, Json),
            Estado = VisitaLocal.Pendiente,
            Titulo = titulo,
            Detalle = detalle,
            CreadaEn = visita.FechaVisita.LocalDateTime,
        });
    }

    public async Task<List<VisitaLocal>> GetPendientesAsync(int maximo)
        => await (await DbAsync()).Table<VisitaLocal>()
            .Where(v => v.Estado == VisitaLocal.Pendiente)
            .OrderBy(v => v.CreadaEn)
            .Take(maximo)
            .ToListAsync();

    public async Task<int> ContarPendientesAsync()
        => await (await DbAsync()).Table<VisitaLocal>().Where(v => v.Estado == VisitaLocal.Pendiente).CountAsync();

    /// <summary>Visitas que todavía no están en el servidor (pendientes o rechazadas).</summary>
    public async Task<List<VisitaLocal>> GetNoSincronizadasAsync()
        => await (await DbAsync()).Table<VisitaLocal>()
            .Where(v => v.Estado != VisitaLocal.Sincronizada)
            .OrderByDescending(v => v.CreadaEn)
            .ToListAsync();

    public async Task<int> ContarRegistradasDesdeAsync(DateTime desde)
        => await (await DbAsync()).Table<VisitaLocal>().Where(v => v.CreadaEn >= desde).CountAsync();

    public async Task MarcarAsync(string uuid, string estado, string? error)
    {
        var db = await DbAsync();
        var visita = await db.FindAsync<VisitaLocal>(uuid);
        if (visita is null)
        {
            return;
        }

        visita.Estado = estado;
        visita.Error = error;
        visita.SincronizadaEn = estado == VisitaLocal.Sincronizada ? DateTime.Now : null;
        await db.UpdateAsync(visita);
    }

    public async Task EliminarVisitaAsync(string uuid) => await (await DbAsync()).DeleteAsync<VisitaLocal>(uuid);

    /// <summary>Al cerrar sesión se borran los datos del asesor guardados en el teléfono.</summary>
    public async Task BorrarTodoAsync()
    {
        var db = await DbAsync();
        await db.DeleteAllAsync<CatalogoLocal>();
        await db.DeleteAllAsync<VisitaLocal>();
    }

    private async Task GuardarAsync<T>(string clave, T valor)
        => await (await DbAsync()).InsertOrReplaceAsync(new CatalogoLocal
        {
            Clave = clave,
            Json = JsonSerializer.Serialize(valor, Json),
            ActualizadoEn = DateTime.Now,
        });

    private async Task<T?> LeerAsync<T>(string clave)
    {
        var fila = await (await DbAsync()).FindAsync<CatalogoLocal>(clave);
        return fila is null ? default : JsonSerializer.Deserialize<T>(fila.Json, Json);
    }
}
