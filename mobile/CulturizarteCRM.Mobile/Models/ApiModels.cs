using System.Text.Json.Serialization;

namespace CulturizarteCRM.Mobile.Models;

/// <summary>Formato estándar de respuesta de la API (trait ApiResponse de Laravel).</summary>
public class ApiResponse<T>
{
    [JsonPropertyName("status")] public string Status { get; set; } = "";
    [JsonPropertyName("message")] public string? Message { get; set; }
    [JsonPropertyName("data")] public T? Data { get; set; }
}

public class LoginData
{
    [JsonPropertyName("token")] public string Token { get; set; } = "";
    [JsonPropertyName("expira_en")] public DateTimeOffset? ExpiraEn { get; set; }
    [JsonPropertyName("usuario")] public Usuario Usuario { get; set; } = new();
}

public class Usuario
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("name")] public string Name { get; set; } = "";
    [JsonPropertyName("email")] public string Email { get; set; } = "";
    [JsonPropertyName("roles")] public List<string> Roles { get; set; } = new();
}

public class Institucion
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("nombre")] public string Nombre { get; set; } = "";
    [JsonPropertyName("codigo_amie")] public string? CodigoAmie { get; set; }
    [JsonPropertyName("ciudad")] public string Ciudad { get; set; } = "";
    [JsonPropertyName("docentes")] public List<Docente> Docentes { get; set; } = new();

    public string Descripcion => $"{Nombre} ({CodigoAmie})";
}

public class Docente
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("nombre_completo")] public string NombreCompleto { get; set; } = "";
    [JsonPropertyName("area_asignatura")] public string? AreaAsignatura { get; set; }

    public string Descripcion => string.IsNullOrWhiteSpace(AreaAsignatura) ? NombreCompleto : $"{NombreCompleto} — {AreaAsignatura}";
}

public class Visita
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("uuid_offline")] public string UuidOffline { get; set; } = "";
    [JsonPropertyName("fecha_visita")] public DateTimeOffset FechaVisita { get; set; }
    [JsonPropertyName("nivel_interes")] public string NivelInteres { get; set; } = "";
    [JsonPropertyName("estado_adopcion")] public string EstadoAdopcion { get; set; } = "";
    [JsonPropertyName("acuerdos")] public string? Acuerdos { get; set; }
    [JsonPropertyName("observaciones")] public string? Observaciones { get; set; }
    [JsonPropertyName("institucion")] public Institucion? Institucion { get; set; }
    [JsonPropertyName("docente")] public Docente? Docente { get; set; }
    [JsonPropertyName("total_muestras")] public int? TotalMuestras { get; set; }

    public string Titulo => Institucion?.Nombre ?? "Institución";
    public string Detalle => $"{Docente?.NombreCompleto} · Interés {NivelInteres} · {EstadoAdopcion}"
        + (TotalMuestras > 0 ? $" · {TotalMuestras} muestra(s)" : "");
    public string FechaTexto => FechaVisita.ToLocalTime().ToString("dd/MM/yyyy HH:mm");
}

public class Paginado<T>
{
    [JsonPropertyName("data")] public List<T> Data { get; set; } = new();
    [JsonPropertyName("total")] public int Total { get; set; }
}

/// <summary>Texto escolar del catálogo con el stock del asesor (Sprint 5, CU-M03).</summary>
public class Producto
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("codigo_isbn")] public string CodigoIsbn { get; set; } = "";
    [JsonPropertyName("titulo")] public string Titulo { get; set; } = "";
    [JsonPropertyName("edicion")] public string? Edicion { get; set; }
    [JsonPropertyName("serie")] public string Serie { get; set; } = "";
    [JsonPropertyName("area")] public string Area { get; set; } = "";
    [JsonPropertyName("nivel_educativo")] public string NivelEducativo { get; set; } = "";
    [JsonPropertyName("stock_asesor")] public int StockAsesor { get; set; }
    [JsonPropertyName("stock_bodega")] public int StockBodega { get; set; }

    public string Detalle => string.IsNullOrWhiteSpace(Edicion)
        ? $"{Serie} · {NivelEducativo} · {Area}"
        : $"{Edicion}\n{Serie} · {NivelEducativo} · {Area}";
    public string NombreCompleto => string.IsNullOrWhiteSpace(Edicion) ? Titulo : $"{Titulo} — {Edicion}";
    public string StockTexto => StockAsesor == 1 ? "1 unidad" : $"{StockAsesor} unidades";
    public string BodegaTexto => $"Bodega: {StockBodega}";
    public bool SinStock => StockAsesor == 0;
    public string Descripcion => NombreCompleto;

    /// <summary>RF-03: mismo criterio del servidor (sin mayúsculas, tildes ni espacios extra).</summary>
    public bool EsCompatibleCon(Docente docente) => Normalizar(Area) == Normalizar(docente.AreaAsignatura);

    public static string Normalizar(string? texto)
    {
        if (string.IsNullOrWhiteSpace(texto)) return "";
        var sinTildes = new string(texto.Normalize(System.Text.NormalizationForm.FormD)
            .Where(c => System.Globalization.CharUnicodeInfo.GetUnicodeCategory(c) != System.Globalization.UnicodeCategory.NonSpacingMark)
            .ToArray());
        return string.Join(' ', sinTildes.ToLowerInvariant().Split(' ', StringSplitOptions.RemoveEmptyEntries));
    }
}

/// <summary>Muestra entregada al docente en la visita (RF-03).</summary>
public class NuevaMuestra
{
    [JsonPropertyName("producto_id")] public int ProductoId { get; set; }
    [JsonPropertyName("formato")] public string Formato { get; set; } = "fisica";
    [JsonPropertyName("cantidad")] public int Cantidad { get; set; } = 1;

    [JsonIgnore] public string Titulo { get; set; } = "";
    [JsonIgnore] public string Resumen => $"{Cantidad} × {Titulo} ({(Formato == "digital" ? "digital" : "física")})";
}

/// <summary>Datos que envía el formulario de visita (RF-01).</summary>
public class NuevaVisita
{
    [JsonPropertyName("uuid_offline")] public string UuidOffline { get; set; } = Guid.NewGuid().ToString();
    [JsonPropertyName("institucion_id")] public int InstitucionId { get; set; }
    [JsonPropertyName("docente_id")] public int DocenteId { get; set; }
    [JsonPropertyName("fecha_visita")] public DateTimeOffset FechaVisita { get; set; }
    [JsonPropertyName("nivel_interes")] public string NivelInteres { get; set; } = "medio";
    [JsonPropertyName("estado_adopcion")] public string EstadoAdopcion { get; set; } = "prospecto";
    [JsonPropertyName("acuerdos")] public string? Acuerdos { get; set; }
    [JsonPropertyName("observaciones")] public string? Observaciones { get; set; }
    [JsonPropertyName("latitud")] public double? Latitud { get; set; }
    [JsonPropertyName("longitud")] public double? Longitud { get; set; }
    [JsonPropertyName("muestras")] public List<NuevaMuestra> Muestras { get; set; } = new();
    /// <summary>Sprint 6: true si el teléfono no tenía internet al registrar la visita.</summary>
    [JsonPropertyName("capturada_offline")] public bool CapturadaOffline { get; set; }
}

/// <summary>Respuesta de POST /sincronizacion (Sprint 6, RF-02).</summary>
public class RespuestaSincronizacion
{
    [JsonPropertyName("resultados")] public List<ResultadoSincronizacion> Resultados { get; set; } = new();
    [JsonPropertyName("sincronizadas")] public int Sincronizadas { get; set; }
    [JsonPropertyName("rechazadas")] public int Rechazadas { get; set; }
}

public class ResultadoSincronizacion
{
    [JsonPropertyName("uuid_offline")] public string UuidOffline { get; set; } = "";
    [JsonPropertyName("estado")] public string Estado { get; set; } = "";
    [JsonPropertyName("visita_id")] public int? VisitaId { get; set; }
    [JsonPropertyName("errores")] public List<string> Errores { get; set; } = new();
}
