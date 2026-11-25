using CulturizarteCRM.Mobile.Models;
using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Sprint 5 - Consultar inventario disponible (CU-M03): stock de muestras del
/// asesor por texto escolar y stock referencial de bodega.
/// </summary>
public partial class InventarioPage : ContentPage
{
    private readonly ApiClient _api;
    private List<Producto> _productos = new();

    public InventarioPage(ApiClient api)
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

    private void OnBuscar(object? sender, TextChangedEventArgs e) => Filtrar();

    private async Task CargarAsync()
    {
        try
        {
            // Primero los textos que el asesor tiene en su inventario.
            _productos = (await _api.GetInventarioAsync())
                .OrderByDescending(p => p.StockAsesor > 0)
                .ThenBy(p => p.Titulo)
                .ToList();
            VacioLabel.Text = "No hay textos que coincidan.";
            Filtrar();
        }
        catch (ApiException ex)
        {
            VacioLabel.Text = ex.Message;
            ListaProductos.ItemsSource = null;
        }
        finally
        {
            Refresco.IsRefreshing = false;
        }
    }

    private void Filtrar()
    {
        var texto = Producto.Normalizar(Buscador.Text);
        ListaProductos.ItemsSource = string.IsNullOrEmpty(texto)
            ? _productos
            : _productos.Where(p => Producto.Normalizar($"{p.Titulo} {p.Edicion} {p.CodigoIsbn} {p.Area} {p.Serie}").Contains(texto)).ToList();
    }
}
