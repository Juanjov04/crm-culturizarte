using System.Net;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using CulturizarteCRM.Mobile.Models;

namespace CulturizarteCRM.Mobile.Services;

/// <summary>Error de la API con un mensaje listo para mostrar al asesor.</summary>
public class ApiException(string message, HttpStatusCode? status = null) : Exception(message)
{
    public HttpStatusCode? Status { get; } = status;
}

/// <summary>
/// Cliente de la API RESTful de Laravel (/api/v1). Guarda el token de Sanctum
/// en el almacenamiento seguro del dispositivo.
/// </summary>
public class ApiClient
{
    private const string ClaveUrl = "api_url";
    private const string ClaveToken = "api_token";
    private const string ClaveUsuario = "api_usuario";

    private static readonly JsonSerializerOptions Json = new(JsonSerializerDefaults.Web);
    private readonly HttpClient _http = new() { Timeout = TimeSpan.FromSeconds(20) };

    public Usuario? UsuarioActual { get; private set; }

    /// <summary>
    /// URL del servidor. En el emulador de Android, 10.0.2.2 apunta al localhost de la PC.
    /// </summary>
    public string UrlBase
    {
        get => Preferences.Get(ClaveUrl, DeviceInfo.Platform == DevicePlatform.Android
            ? "http://10.0.2.2:8000/api/v1/"
            : "http://127.0.0.1:8000/api/v1/");
        set => Preferences.Set(ClaveUrl, value.Trim().TrimEnd('/') + "/");
    }

    public async Task<bool> RestaurarSesionAsync()
    {
        var token = await SecureStorage.GetAsync(ClaveToken);
        var usuario = Preferences.Get(ClaveUsuario, "");
        if (string.IsNullOrEmpty(token) || string.IsNullOrEmpty(usuario))
        {
            return false;
        }

        UsuarioActual = JsonSerializer.Deserialize<Usuario>(usuario, Json);
        return UsuarioActual is not null;
    }

    public async Task<Usuario> LoginAsync(string email, string password)
    {
        var datos = await EnviarAsync<LoginData>(HttpMethod.Post, "auth/login", new
        {
            email,
            password,
            device_name = $"{DeviceInfo.Manufacturer} {DeviceInfo.Model}".Trim(),
        }, conToken: false);

        await SecureStorage.SetAsync(ClaveToken, datos.Token);
        Preferences.Set(ClaveUsuario, JsonSerializer.Serialize(datos.Usuario, Json));
        UsuarioActual = datos.Usuario;
        return datos.Usuario;
    }

    public async Task LogoutAsync()
    {
        try
        {
            await EnviarAsync<object>(HttpMethod.Post, "auth/logout", null);
        }
        catch (ApiException)
        {
            // Si no hay red o el token ya expiró, igual se cierra la sesión local.
        }

        SecureStorage.Remove(ClaveToken);
        Preferences.Remove(ClaveUsuario);
        UsuarioActual = null;
    }

    public Task<List<Institucion>> GetInstitucionesAsync()
        => EnviarAsync<List<Institucion>>(HttpMethod.Get, "instituciones?con_docentes=1", null);

    public async Task<List<Visita>> GetMisVisitasAsync()
        => (await EnviarAsync<Paginado<Visita>>(HttpMethod.Get, "visitas?por_pagina=50", null)).Data;

    public Task<Visita> RegistrarVisitaAsync(NuevaVisita visita)
        => EnviarAsync<Visita>(HttpMethod.Post, "visitas", visita);

    private async Task<T> EnviarAsync<T>(HttpMethod metodo, string ruta, object? cuerpo, bool conToken = true)
    {
        using var request = new HttpRequestMessage(metodo, new Uri(new Uri(UrlBase), ruta));
        request.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        if (conToken)
        {
            var token = await SecureStorage.GetAsync(ClaveToken);
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
        }

        if (cuerpo is not null)
        {
            request.Content = JsonContent.Create(cuerpo, options: Json);
        }

        HttpResponseMessage response;
        try
        {
            response = await _http.SendAsync(request);
        }
        catch (Exception ex) when (ex is HttpRequestException or TaskCanceledException)
        {
            throw new ApiException("No se pudo conectar con el servidor. Revisa tu conexión a internet.");
        }

        var texto = await response.Content.ReadAsStringAsync();

        if (!response.IsSuccessStatusCode)
        {
            throw new ApiException(LeerMensajeDeError(response.StatusCode, texto), response.StatusCode);
        }

        var resultado = JsonSerializer.Deserialize<ApiResponse<T>>(texto, Json);
        return resultado is not null && resultado.Data is not null
            ? resultado.Data
            : default!;
    }

    private static string LeerMensajeDeError(HttpStatusCode status, string texto)
    {
        if (status == HttpStatusCode.Unauthorized && !texto.Contains("Credenciales"))
        {
            return "Tu sesión expiró. Vuelve a iniciar sesión.";
        }

        try
        {
            using var doc = JsonDocument.Parse(texto);
            var raiz = doc.RootElement;

            // Errores de validación de Laravel (422): se muestran todos.
            if (raiz.TryGetProperty("errors", out var errores))
            {
                var mensajes = errores.EnumerateObject()
                    .SelectMany(campo => campo.Value.EnumerateArray().Select(m => m.GetString()))
                    .Where(m => !string.IsNullOrWhiteSpace(m));
                return string.Join("\n", mensajes);
            }

            if (raiz.TryGetProperty("message", out var mensaje) && !string.IsNullOrWhiteSpace(mensaje.GetString()))
            {
                return mensaje.GetString()!;
            }
        }
        catch (JsonException)
        {
        }

        return $"Error del servidor ({(int)status}).";
    }
}
