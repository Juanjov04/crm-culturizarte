using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>Elemento de la lista: visita del servidor o guardada en el teléfono.</summary>
public class ItemVisita
{
    public string Titulo { get; init; } = "";
    public string Detalle { get; init; } = "";
    public string FechaTexto { get; init; } = "";
    public string EstadoTexto { get; init; } = "";
    public bool MostrarEstado { get; init; }
    public Color ColorEstado { get; init; } = Color.FromArgb("#16A34A");
    public Color FondoEstado { get; init; } = Color.FromArgb("#F0FDF4");
    public VisitaLocal? Local { get; init; }
}

/// <summary>
/// Historial del asesor. Desde el Sprint 6 muestra primero las visitas que aún
/// están en el teléfono (pendientes o rechazadas por el servidor).
/// </summary>
public partial class VisitasPage : ContentPage
{
    private readonly ApiClient _api;
    private readonly SincronizacionService _sync;

    public VisitasPage(ApiClient api, SincronizacionService sync)
    {
        InitializeComponent();
        _api = api;
        _sync = sync;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        _sync.Cambio += OnCambio;
        await CargarAsync();
    }

    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _sync.Cambio -= OnCambio;
    }

    private void OnCambio(object? sender, EventArgs e) => MainThread.BeginInvokeOnMainThread(async () => await CargarAsync());

    private async void OnRefrescar(object? sender, EventArgs e)
    {
        await _sync.SincronizarAsync();
        await CargarAsync();
    }

    private async Task CargarAsync()
    {
        var items = (await _sync.GetNoSincronizadasAsync()).Select(v => new ItemVisita
        {
            Titulo = v.Titulo,
            Detalle = v.Detalle,
            FechaTexto = v.FechaTexto,
            EstadoTexto = v.EsRechazada ? $"Rechazada: {v.Error} (toca para ver opciones)" : "Guardada en el teléfono · pendiente de sincronizar",
            MostrarEstado = true,
            ColorEstado = v.EsRechazada ? Color.FromArgb("#B91C1C") : Color.FromArgb("#B45309"),
            FondoEstado = v.EsRechazada ? Color.FromArgb("#FEF2F2") : Color.FromArgb("#FEF3C7"),
            Local = v,
        }).ToList();

        AvisoLabel.IsVisible = false;
        if (_sync.HayConexion)
        {
            try
            {
                items.AddRange((await _api.GetMisVisitasAsync()).Select(v => new ItemVisita
                {
                    Titulo = v.Titulo,
                    Detalle = v.Detalle,
                    FechaTexto = v.FechaTexto,
                }));
            }
            catch (ApiException ex)
            {
                AvisoLabel.Text = ex.Message;
                AvisoLabel.IsVisible = true;
            }
        }
        else
        {
            AvisoLabel.Text = "Sin conexión: se muestran solo las visitas guardadas en el teléfono.";
            AvisoLabel.IsVisible = true;
        }

        ListaVisitas.ItemsSource = items;
        Refresco.IsRefreshing = false;
    }

    /// <summary>Una visita rechazada se puede descartar para que no quede bloqueada en el teléfono.</summary>
    private async void OnSeleccion(object? sender, SelectionChangedEventArgs e)
    {
        ListaVisitas.SelectedItem = null;
        if (e.CurrentSelection.FirstOrDefault() is not ItemVisita { Local: { EsRechazada: true } local })
        {
            return;
        }

        var descartar = await DisplayAlert(
            "Visita rechazada",
            $"{local.Titulo}\n{local.Detalle}\n\nMotivo: {local.Error}\n\nPuedes descartarla y registrarla de nuevo con los datos corregidos.",
            "Descartar", "Mantener");

        if (descartar)
        {
            await _sync.DescartarAsync(local.Uuid);
        }
    }
}
