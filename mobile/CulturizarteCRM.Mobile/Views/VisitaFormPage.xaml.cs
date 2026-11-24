using System.Collections.ObjectModel;
using CulturizarteCRM.Mobile.Models;
using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Sprint 4 - Formulario de registro de visitas comerciales (RF-01, CU-M02).
/// Sprint 5 - Paso de asignación de muestras con control de stock (RF-03).
/// Por ahora envía la visita directamente a la API; el guardado offline en
/// SQLite y la sincronización se agregan en el Sprint 6.
/// </summary>
public partial class VisitaFormPage : ContentPage
{
    private static readonly string[] Niveles = ["bajo", "medio", "alto"];
    private static readonly string[] Estados = ["prospecto", "interesado", "adoptado", "desertado"];
    private static readonly string[] Formatos = ["fisica", "digital"];

    private readonly ApiClient _api;
    private readonly ObservableCollection<NuevaMuestra> _muestras = new();
    private List<Producto> _productos = new();
    private IDispatcherTimer? _reloj;
    private bool _cargado;

    public VisitaFormPage(ApiClient api)
    {
        InitializeComponent();
        _api = api;

        InteresPicker.ItemsSource = Niveles.Select(Capitalizar).ToList();
        InteresPicker.SelectedIndex = 1;
        EstadoPicker.ItemsSource = Estados.Select(Capitalizar).ToList();
        EstadoPicker.SelectedIndex = 0;
        FormatoPicker.ItemsSource = new List<string> { "Física", "Digital" };
        FormatoPicker.SelectedIndex = 0;

        BindableLayout.SetItemsSource(MuestrasLista, _muestras);
        ActualizarFecha();
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // La fecha y hora mostradas siguen al reloj del dispositivo (no se pueden editar).
        if (_reloj is null)
        {
            _reloj = Dispatcher.CreateTimer();
            _reloj.Interval = TimeSpan.FromSeconds(20);
            _reloj.Tick += (_, _) => ActualizarFecha();
        }
        ActualizarFecha();
        _reloj.Start();

        if (_cargado)
        {
            return;
        }

        Ocupado(true);
        try
        {
            var instituciones = _api.GetInstitucionesAsync();
            var inventario = _api.GetInventarioAsync();
            InstitucionPicker.ItemsSource = await instituciones;
            _productos = await inventario;
            _cargado = true;
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

    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _reloj?.Stop();
    }

    private void ActualizarFecha() => FechaLabel.Text = DateTime.Now.ToString("dd/MM/yyyy HH:mm");

    private void OnInstitucionCambiada(object? sender, EventArgs e)
    {
        var docentes = (InstitucionPicker.SelectedItem as Institucion)?.Docentes ?? new List<Docente>();
        DocentePicker.ItemsSource = docentes;
        DocentePicker.SelectedIndex = docentes.Count == 1 ? 0 : -1;
        DocentePicker.IsEnabled = docentes.Count > 0;
        SinDocentesLabel.IsVisible = InstitucionPicker.SelectedItem is not null && docentes.Count == 0;
        OnDocenteCambiado(sender, e);
    }

    /// <summary>RF-03: solo se ofrecen los textos compatibles con el área del docente.</summary>
    private void OnDocenteCambiado(object? sender, EventArgs e)
    {
        _muestras.Clear();

        if (DocentePicker.SelectedItem is not Docente docente)
        {
            MuestraForm.IsVisible = false;
            MuestrasAyudaLabel.Text = "Elige el docente para ver los textos compatibles con su área.";
            MuestrasAyudaLabel.IsVisible = true;
            return;
        }

        var compatibles = _productos.Where(p => p.EsCompatibleCon(docente)).ToList();
        ProductoPicker.ItemsSource = compatibles;
        ProductoPicker.SelectedIndex = -1;
        MuestraForm.IsVisible = compatibles.Count > 0;
        MuestrasAyudaLabel.Text = compatibles.Count > 0
            ? $"Textos de {docente.AreaAsignatura}. Las muestras físicas descuentan de tu inventario."
            : $"No hay textos del catálogo para el área {docente.AreaAsignatura}.";
    }

    private void OnCantidadCambiada(object? sender, ValueChangedEventArgs e)
        => CantidadLabel.Text = $"Cantidad: {(int)e.NewValue}";

    private void OnAgregarMuestra(object? sender, EventArgs e)
    {
        ErrorLabel.IsVisible = false;

        if (ProductoPicker.SelectedItem is not Producto producto)
        {
            MostrarError("Selecciona el texto escolar de la muestra.");
            return;
        }

        if (_muestras.Any(m => m.ProductoId == producto.Id))
        {
            MostrarError("Ese texto ya está en la lista de muestras.");
            return;
        }

        var formato = Formatos[Math.Max(FormatoPicker.SelectedIndex, 0)];
        var cantidad = (int)CantidadStepper.Value;

        // Misma regla que el servidor: sin stock no se entrega una muestra física.
        if (formato == "fisica" && producto.StockAsesor == 0)
        {
            MostrarError($"No tienes stock de «{producto.Titulo}». Solo puedes entregarlo en formato digital.");
            return;
        }

        if (formato == "fisica" && cantidad > producto.StockAsesor)
        {
            MostrarError($"Solo tienes {producto.StockAsesor} unidades de «{producto.Titulo}».");
            return;
        }

        _muestras.Add(new NuevaMuestra
        {
            ProductoId = producto.Id,
            Titulo = producto.Titulo,
            Formato = formato,
            Cantidad = cantidad,
        });

        ProductoPicker.SelectedIndex = -1;
        CantidadStepper.Value = 1;
    }

    private void OnQuitarMuestra(object? sender, EventArgs e)
    {
        if ((sender as BindableObject)?.BindingContext is NuevaMuestra muestra)
        {
            _muestras.Remove(muestra);
        }
    }

    private async void OnGuardarClicked(object? sender, EventArgs e)
    {
        ErrorLabel.IsVisible = false;

        if (InstitucionPicker.SelectedItem is not Institucion institucion)
        {
            MostrarError("Selecciona la institución visitada.");
            return;
        }

        if (DocentePicker.SelectedItem is not Docente docente)
        {
            MostrarError("Selecciona el docente visitado.");
            return;
        }

        var visita = new NuevaVisita
        {
            InstitucionId = institucion.Id,
            DocenteId = docente.Id,
            // Observación del jurado: siempre la fecha y hora actual.
            FechaVisita = DateTimeOffset.Now,
            NivelInteres = Niveles[Math.Max(InteresPicker.SelectedIndex, 0)],
            EstadoAdopcion = Estados[Math.Max(EstadoPicker.SelectedIndex, 0)],
            Acuerdos = Limpiar(AcuerdosEditor.Text),
            Observaciones = Limpiar(ObservacionesEditor.Text),
            Muestras = _muestras.ToList(),
        };

        Ocupado(true);
        try
        {
            await _api.RegistrarVisitaAsync(visita);
            var detalle = _muestras.Count > 0 ? $" con {_muestras.Sum(m => m.Cantidad)} muestra(s)" : "";
            await DisplayAlert("Visita registrada", $"Se registró la visita a {docente.NombreCompleto}{detalle}.", "Aceptar");
            await Shell.Current.GoToAsync("..");
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
        GuardarButton.IsEnabled = !ocupado;
        Cargando.IsRunning = Cargando.IsVisible = ocupado;
    }

    private static string? Limpiar(string? texto) => string.IsNullOrWhiteSpace(texto) ? null : texto.Trim();

    private static string Capitalizar(string texto) => char.ToUpper(texto[0]) + texto[1..];
}
