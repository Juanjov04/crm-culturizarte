using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

public partial class LoginPage : ContentPage
{
    private const string ClaveDuenoDatos = "datos_locales_usuario";

    private readonly ApiClient _api;
    private readonly SincronizacionService _sync;

    public LoginPage(ApiClient api, SincronizacionService sync)
    {
        InitializeComponent();
        _api = api;
        _sync = sync;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // Si ya había una sesión guardada, entra directo.
        if (await _api.RestaurarSesionAsync())
        {
            await Shell.Current.GoToAsync("//inicio");
        }
    }

    private async void OnIngresarClicked(object? sender, EventArgs e)
    {
        var email = EmailEntry.Text?.Trim() ?? "";
        var password = PasswordEntry.Text ?? "";

        if (email.Length == 0 || password.Length == 0)
        {
            MostrarError("Ingresa tu correo y contraseña.");
            return;
        }

        // La dirección del servidor no se muestra al asesor: se usa la configurada en ApiClient.
        Ocupado(true);

        try
        {
            var usuario = await _api.LoginAsync(email, password);

            // Los datos del teléfono pertenecen a un asesor: si entra otro, se borran
            // para que sus visitas pendientes no se envíen con la cuenta equivocada.
            if (Preferences.Get(ClaveDuenoDatos, usuario.Id) != usuario.Id)
            {
                await _sync.BorrarDatosLocalesAsync();
            }
            Preferences.Set(ClaveDuenoDatos, usuario.Id);

            PasswordEntry.Text = "";
            ErrorLabel.IsVisible = false;
            await Shell.Current.GoToAsync("//inicio");
        }
        catch (ApiException ex)
        {
            MostrarError(ex.Message);
        }
        finally
        {
            Ocupado(false);
        }
    }

    private void MostrarError(string mensaje)
    {
        ErrorLabel.Text = mensaje;
        ErrorLabel.IsVisible = true;
    }

    private void Ocupado(bool ocupado)
    {
        IngresarButton.IsEnabled = !ocupado;
        Cargando.IsRunning = Cargando.IsVisible = ocupado;
    }
}
