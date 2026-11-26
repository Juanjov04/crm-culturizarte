using System.Collections.ObjectModel;
using CulturizarteCRM.Mobile.Models;
using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Sprint 4 - Formulario de registro de visitas comerciales (RF-01, CU-M02).
/// Sprint 5 - Paso de asignación de muestras con control de stock (RF-03).
/// Sprint 6 - Offline-first (RF-01): la visita se guarda primero en el teléfono
/// (SQLite) y se envía al servidor cuando hay conexión.
/// </summary>
public partial class VisitaFormPage : ContentPage
{
    private static readonly string[] Niveles = ["bajo", "medio", "alto"];
    private static readonly string[] Estados = ["prospecto", "interesado", "adoptado", "desertado"];

    /// <summary>Igual que Visita::MAX_DIAS_FUTURO en el servidor.</summary>
    private const int MaxDiasFuturo = 365;

    private readonly SincronizacionService _sync;
    private readonly ObservableCollection<NuevaMuestra> _muestras = new();
    private List<Producto> _productos = new();
    private bool _fechaEditada;
    private bool _ajustandoFecha;
    private bool _cargado;

    public VisitaFormPage(SincronizacionService sync)
    {
        InitializeComponent();
        _sync = sync;

        InteresPicker.ItemsSource = Niveles.Select(Capitalizar).ToList();
        InteresPicker.SelectedIndex = 1;
        EstadoPicker.ItemsSource = Estados.Select(Capitalizar).ToList();
        EstadoPicker.SelectedIndex = 0;

        BindableLayout.SetItemsSource(MuestrasLista, _muestras);
        PonerFechaActual();
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // Mientras el asesor no cambie la fecha, se muestra la hora actual.
        if (!_fechaEditada)
        {
            PonerFechaActual();
        }

        if (_cargado)
        {
            return;
        }

        // Se usa el catálogo guardado en el teléfono: funciona sin conexión (CU-M01).
        Ocupado(true);
        try
        {
            var instituciones = await _sync.GetInstitucionesAsync();
            _productos = await _sync.GetInventarioAsync();
            InstitucionPicker.ItemsSource = instituciones;
            _cargado = instituciones.Count > 0;

            if (instituciones.Count == 0)
            {
                MostrarError("Aún no hay colegios guardados en el teléfono. Conéctate a internet una vez para descargar el catálogo.");
            }
        }
        finally
        {
            Ocupado(false);
        }
    }

    // --- Fecha y hora (observación del jurado: solo hacia adelante) ---

    private void PonerFechaActual()
    {
        _ajustandoFecha = true;
        var ahora = DateTime.Now;
        FechaPicker.MinimumDate = ahora.Date;
        FechaPicker.MaximumDate = ahora.Date.AddDays(MaxDiasFuturo);
        FechaPicker.Date = ahora.Date;
        HoraPicker.Time = new TimeSpan(ahora.Hour, ahora.Minute, 0);
        _ajustandoFecha = false;
    }

    private DateTime FechaElegida => FechaPicker.Date.Date.Add(HoraPicker.Time);

    private void OnFechaCambiada(object? sender, DateChangedEventArgs e) => MarcarFechaEditada();

    private void OnHoraCambiada(object? sender, System.ComponentModel.PropertyChangedEventArgs e)
    {
        if (e.PropertyName == nameof(TimePicker.Time))
        {
            MarcarFechaEditada();
        }
    }

    private void MarcarFechaEditada()
    {
        if (_ajustandoFecha)
        {
            return;
        }

        _fechaEditada = true;
        var anterior = FechaElegida < DateTime.Now.AddMinutes(-1);
        FechaAyudaLabel.Text = anterior
            ? "La fecha y hora no pueden ser anteriores a este momento."
            : "Solo se permiten la fecha y hora actuales o posteriores.";
        FechaAyudaLabel.TextColor = anterior ? Color.FromArgb("#B91C1C") : (Color)Application.Current!.Resources["TextoSuave"];
    }

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
            ? $"Textos de {docente.AreaAsignatura}. Cada muestra descuenta de tu inventario."
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

        var cantidad = (int)CantidadStepper.Value;

        // Misma regla que el servidor: sin stock no se entrega la muestra.
        if (producto.StockAsesor == 0)
        {
            MostrarError($"No tienes stock de «{producto.Titulo}» para entregarlo como muestra.");
            return;
        }

        if (cantidad > producto.StockAsesor)
        {
            MostrarError($"Solo tienes {producto.StockAsesor} unidades de «{producto.Titulo}».");
            return;
        }

        _muestras.Add(new NuevaMuestra
        {
            ProductoId = producto.Id,
            Titulo = producto.NombreCompleto,
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

        // Todos los campos son obligatorios.
        if (InteresPicker.SelectedIndex < 0 || EstadoPicker.SelectedIndex < 0)
        {
            MostrarError("Selecciona el nivel de interés y el estado del docente.");
            return;
        }

        if (Limpiar(AcuerdosEditor.Text) is null)
        {
            MostrarError("Escribe los acuerdos alcanzados en la visita.");
            return;
        }

        if (Limpiar(ObservacionesEditor.Text) is null)
        {
            MostrarError("Escribe las observaciones de la visita.");
            return;
        }

        // Si el asesor no tocó la fecha se usa la hora del registro; si la cambió,
        // no puede quedar en el pasado (se permite 1 minuto por el llenado).
        var fecha = _fechaEditada ? FechaElegida : DateTime.Now;
        if (fecha < DateTime.Now.AddMinutes(-1))
        {
            MostrarError("La fecha y hora de la visita no pueden ser anteriores a este momento.");
            return;
        }

        var visita = new NuevaVisita
        {
            InstitucionId = institucion.Id,
            DocenteId = docente.Id,
            FechaVisita = new DateTimeOffset(fecha),
            NivelInteres = Niveles[Math.Max(InteresPicker.SelectedIndex, 0)],
            EstadoAdopcion = Estados[Math.Max(EstadoPicker.SelectedIndex, 0)],
            Acuerdos = Limpiar(AcuerdosEditor.Text),
            Observaciones = Limpiar(ObservacionesEditor.Text),
            Muestras = _muestras.ToList(),
        };

        Ocupado(true);
        try
        {
            await _sync.GuardarVisitaAsync(visita, institucion, docente);
            var detalle = _muestras.Count > 0 ? $" con {_muestras.Sum(m => m.Cantidad)} muestra(s)" : "";
            var envio = _sync.HayConexion
                ? "Se está enviando al servidor."
                : "Estás sin conexión: quedó guardada en el teléfono y se enviará sola cuando vuelvas a tener internet.";
            await DisplayAlert("Visita registrada", $"Se registró la visita a {docente.NombreCompleto}{detalle}.\n\n{envio}", "Aceptar");
            await Shell.Current.GoToAsync("..");
        }
        catch (Exception ex)
        {
            MostrarError($"No se pudo guardar la visita en el teléfono: {ex.Message}");
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
