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
        SaludoLabel.Text = $"¡Hola, {_api.UsuarioActual?.Name ?? "asesor"}!";

        try
        {
            var visitas = await _api.GetMisVisitasAsync();
            var hoy = DateTime.Today;
            VisitasHoyLabel.Text = visitas.Count(v => v.FechaVisita.ToLocalTime().Date == hoy).ToString();
            MostrarEstado(conectado: true, "Conectado al servidor. ¡Buen trabajo!");
        }
        catch (ApiException ex)
        {
            VisitasHoyLabel.Text = "—";
            MostrarEstado(conectado: false, ex.Message);

            if (ex.Status == System.Net.HttpStatusCode.Unauthorized)
            {
                await _api.LogoutAsync();
                await Shell.Current.GoToAsync("//login");
            }
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

    private async void OnCerrarSesionClicked(object? sender, EventArgs e)
    {
        if (await DisplayAlert("Cerrar sesión", "¿Deseas salir de la aplicación?", "Sí", "No"))
        {
            await _api.LogoutAsync();
            await Shell.Current.GoToAsync("//login");
        }
    }
}
