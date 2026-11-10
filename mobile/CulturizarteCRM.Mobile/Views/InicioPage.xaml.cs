using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

public partial class InicioPage : ContentPage
{
    private readonly ApiClient _api;

    public InicioPage(ApiClient api)
    {
        InitializeComponent();
        _api = api;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        SaludoLabel.Text = $"Hola, {_api.UsuarioActual?.Name ?? "asesor"}";

        try
        {
            var visitas = await _api.GetMisVisitasAsync();
            var hoy = DateTime.Today;
            VisitasHoyLabel.Text = visitas.Count(v => v.FechaVisita.ToLocalTime().Date == hoy).ToString();
            EstadoLabel.Text = "Conectado al servidor";
        }
        catch (ApiException ex)
        {
            VisitasHoyLabel.Text = "—";
            EstadoLabel.Text = ex.Message;

            if (ex.Status == System.Net.HttpStatusCode.Unauthorized)
            {
                await _api.LogoutAsync();
                await Shell.Current.GoToAsync("//login");
            }
        }
    }

    private async void OnNuevaVisitaClicked(object? sender, EventArgs e)
        => await Shell.Current.GoToAsync(nameof(VisitaFormPage));

    private async void OnMisVisitasClicked(object? sender, EventArgs e)
        => await Shell.Current.GoToAsync(nameof(VisitasPage));

    private async void OnCerrarSesionClicked(object? sender, EventArgs e)
    {
        if (await DisplayAlert("Cerrar sesión", "¿Deseas salir de la aplicación?", "Sí", "No"))
        {
            await _api.LogoutAsync();
            await Shell.Current.GoToAsync("//login");
        }
    }
}
