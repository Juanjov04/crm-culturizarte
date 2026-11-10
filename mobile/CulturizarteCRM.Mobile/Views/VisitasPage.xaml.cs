using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

public partial class VisitasPage : ContentPage
{
    private readonly ApiClient _api;

    public VisitasPage(ApiClient api)
    {
        InitializeComponent();
        _api = api;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        await CargarAsync();
    }

    private async void OnRefrescar(object? sender, EventArgs e) => await CargarAsync();

    private async Task CargarAsync()
    {
        try
        {
            ListaVisitas.ItemsSource = await _api.GetMisVisitasAsync();
        }
        catch (ApiException ex)
        {
            VacioLabel.Text = ex.Message;
            ListaVisitas.ItemsSource = null;
        }
        finally
        {
            Refresco.IsRefreshing = false;
        }
    }
}
