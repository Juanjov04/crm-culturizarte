using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

public partial class LoginPage : ContentPage
{
    private readonly ApiClient _api;

    public LoginPage(ApiClient api)
    {
        InitializeComponent();
        _api = api;
        UrlEntry.Text = _api.UrlBase;
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

        if (!Uri.TryCreate(UrlEntry.Text?.Trim(), UriKind.Absolute, out _))
        {
            MostrarError("La URL del servidor no es válida.");
            return;
        }

        _api.UrlBase = UrlEntry.Text!.Trim();
        Ocupado(true);

        try
        {
            await _api.LoginAsync(email, password);
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
