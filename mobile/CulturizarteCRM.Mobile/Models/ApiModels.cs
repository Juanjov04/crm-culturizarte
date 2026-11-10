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

    public string Titulo => Institucion?.Nombre ?? "Institución";
    public string Detalle => $"{Docente?.NombreCompleto} · Interés {NivelInteres} · {EstadoAdopcion}";
    public string FechaTexto => FechaVisita.ToLocalTime().ToString("dd/MM/yyyy HH:mm");
}

public class Paginado<T>
{
    [JsonPropertyName("data")] public List<T> Data { get; set; } = new();
    [JsonPropertyName("total")] public int Total { get; set; }
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
}
