using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Inicio del asesor (Figura 10): resumen del día, estado de conexión y, desde
/// el Sprint 6, las visitas guardadas en el teléfono pendientes de sincronizar.
/// </summary>
public partial class InicioPage : ContentPage
{
    private readonly ApiClient _api;
    private readonly SincronizacionService _sync;

    public InicioPage(ApiClient api, SincronizacionService sync)
    {
        InitializeComponent();
        _api = api;
        _sync = sync;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        SaludoLabel.Text = $"¡Hola, {_api.UsuarioActual?.Name ?? "asesor"}!";

        _sync.Cambio += OnCambioSincronizacion;
        Connectivity.Current.ConnectivityChanged += OnConectividadCambiada;

        await ActualizarAsync();

        // Al abrir la app se envían las pendientes y se descarga el catálogo (CU-M01).
        if (_sync.HayConexion)
        {
            await SincronizarAsync(mostrarResultado: false);
        }
    }

    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _sync.Cambio -= OnCambioSincronizacion;
        Connectivity.Current.ConnectivityChanged -= OnConectividadCambiada;
    }

    private void OnCambioSincronizacion(object? sender, EventArgs e)
        => MainThread.BeginInvokeOnMainThread(async () => await ActualizarAsync());

    private void OnConectividadCambiada(object? sender, ConnectivityChangedEventArgs e)
        => MainThread.BeginInvokeOnMainThread(async () => await ActualizarAsync());

    private async Task ActualizarAsync()
    {
        var pendientes = await _sync.ContarPendientesAsync();
        PendientesLabel.Text = pendientes.ToString();
        PendientesBadge.BackgroundColor = pendientes > 0 ? Color.FromArgb("#FEF3C7") : (Color)Application.Current!.Resources["ExitoFondo"];
        PendientesLabel.TextColor = pendientes > 0 ? Color.FromArgb("#B45309") : (Color)Application.Current!.Resources["Exito"];

        UltimaSyncLabel.Text = _sync.UltimaSincronizacion is { } fecha
            ? $"Última sincronización: {fecha:dd/MM/yyyy HH:mm}"
            : pendientes > 0 ? "Se enviarán al recuperar la conexión." : "Todo está al día.";

        SyncErrorLabel.Text = _sync.UltimoError;
        SyncErrorLabel.IsVisible = !string.IsNullOrEmpty(_sync.UltimoError);

        VisitasHoyLabel.Text = (await _sync.ContarRegistradasHoyAsync()).ToString();

        var conectado = _sync.HayConexion;
        MostrarEstado(conectado, conectado
            ? pendientes > 0 ? "Conectado. Enviando visitas pendientes…" : "Conectado al servidor. ¡Buen trabajo!"
            : "Sin conexión: puedes seguir registrando visitas, se guardan en el teléfono.");
        SincronizarButton.IsEnabled = conectado;
    }

    private async void OnSincronizarClicked(object? sender, EventArgs e) => await SincronizarAsync(mostrarResultado: true);

    private async Task SincronizarAsync(bool mostrarResultado)
    {
        SincronizarButton.IsEnabled = false;
        SyncIndicador.IsRunning = SyncIndicador.IsVisible = true;
        try
        {
            var (enviadas, rechazadas) = await _sync.SincronizarAsync();

            if (_sync.UltimoError?.Contains("sesión expiró") == true)
            {
                await _api.LogoutAsync();
                await Shell.Current.GoToAsync("//login");
                return;
            }

            if (mostrarResultado)
            {
                var mensaje = _sync.UltimoError ?? (enviadas + rechazadas == 0
                    ? "No había visitas pendientes. El catálogo quedó actualizado."
                    : $"Se enviaron {enviadas} visita(s).");
                if (rechazadas > 0)
                {
                    mensaje += $"\n{rechazadas} visita(s) fueron rechazadas: revísalas en la pestaña Visitas.";
                }

                await DisplayAlert("Sincronización", mensaje, "Aceptar");
            }
        }
        finally
        {
            SyncIndicador.IsRunning = SyncIndicador.IsVisible = false;
            await ActualizarAsync();
        }
    }

    // Indicador de conexión de la barra superior y mensaje del resumen.
    private void MostrarEstado(bool conectado, string mensaje)
    {
        var verde = (Color)Application.Current!.Resources["Exito"];
        var rojo = (Color)Application.Current!.Resources["Tertiary"];

        EstadoPillLabel.Text = conectado ? "● Online" : "● Sin conexión";
        EstadoPillLabel.TextColor = conectado ? verde : rojo;
        EstadoPill.Stroke = conectado ? Color.FromArgb("#BBF7D0") : Color.FromArgb("#FECACA");

        EstadoLabel.Text = mensaje;
        EstadoLabel.TextColor = conectado ? verde : rojo;
        EstadoBox.BackgroundColor = conectado ? (Color)Application.Current!.Resources["ExitoFondo"] : Color.FromArgb("#FEF2F2");
    }

    private async void OnNuevaVisitaClicked(object? sender, EventArgs e)
        => await Shell.Current.GoToAsync(nameof(VisitaFormPage));

    private async void OnMisVisitasClicked(object? sender, EventArgs e)
        => await Shell.Current.GoToAsync("//visitas");

    private async void OnInventarioClicked(object? sender, EventArgs e)
        => await Shell.Current.GoToAsync("//inventario");

    private async void OnCerrarSesionClicked(object? sender, EventArgs e)
    {
        var pendientes = await _sync.ContarPendientesAsync();
        var aviso = pendientes > 0
            ? $"Tienes {pendientes} visita(s) sin sincronizar que se perderán si sales ahora. ¿Salir de todos modos?"
            : "¿Deseas salir de la aplicación?";

        if (await DisplayAlert("Cerrar sesión", aviso, "Sí", "No"))
        {
            await _api.LogoutAsync();
            await _sync.BorrarDatosLocalesAsync();
            await Shell.Current.GoToAsync("//login");
        }
    }
}
