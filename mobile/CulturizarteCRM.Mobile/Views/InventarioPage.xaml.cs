using CulturizarteCRM.Mobile.Models;
using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Sprint 5 - Consultar inventario disponible (CU-M03): stock de muestras del
/// asesor por texto escolar y stock referencial de bodega.
/// Sprint 6: se lee del teléfono (última sincronización), así funciona sin conexión.
/// </summary>
public partial class InventarioPage : ContentPage
{
    private readonly SincronizacionService _sync;
    private List<Producto> _productos = new();

    public InventarioPage(SincronizacionService sync)
    {
        InitializeComponent();
        _sync = sync;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        await CargarAsync();
    }

    private async void OnRefrescar(object? sender, EventArgs e)
    {
        // Deslizar hacia abajo envía las pendientes y descarga el inventario actualizado.
        await _sync.SincronizarAsync();
        await CargarAsync();
    }

    private void OnBuscar(object? sender, TextChangedEventArgs e) => Filtrar();

    private async Task CargarAsync()
    {
        try
        {
            // Primero los textos que el asesor tiene en su inventario.
            _productos = (await _sync.GetInventarioAsync())
                .OrderByDescending(p => p.StockAsesor > 0)
                .ThenBy(p => p.Titulo)
                .ToList();
            VacioLabel.Text = _productos.Count == 0
                ? "Aún no hay inventario guardado en el teléfono. Conéctate a internet para descargarlo."
                : "No hay textos que coincidan.";
            Filtrar();
        }
        catch (Exception ex)
        {
            VacioLabel.Text = $"No se pudo leer el inventario del teléfono: {ex.Message}";
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
