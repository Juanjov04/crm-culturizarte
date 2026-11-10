using CulturizarteCRM.Mobile.Models;
using CulturizarteCRM.Mobile.Services;

namespace CulturizarteCRM.Mobile.Views;

/// <summary>
/// Sprint 4 - Formulario de registro de visitas comerciales (RF-01, CU-M02).
/// Por ahora envía la visita directamente a la API; el guardado offline en
/// SQLite y la sincronización se agregan en el Sprint 6.
/// </summary>
public partial class VisitaFormPage : ContentPage
{
    private static readonly string[] Niveles = ["bajo", "medio", "alto"];
    private static readonly string[] Estados = ["prospecto", "interesado", "adoptado", "desertado"];

    private readonly ApiClient _api;
    private bool _cargado;

    public VisitaFormPage(ApiClient api)
    {
        InitializeComponent();
        _api = api;

        InteresPicker.ItemsSource = Niveles.Select(Capitalizar).ToList();
        InteresPicker.SelectedIndex = 1;
        EstadoPicker.ItemsSource = Estados.Select(Capitalizar).ToList();
        EstadoPicker.SelectedIndex = 0;

        FechaPicker.MaximumDate = DateTime.Today;
        FechaPicker.Date = DateTime.Today;
        HoraPicker.Time = DateTime.Now.TimeOfDay;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        if (_cargado)
        {
            return;
        }

        Ocupado(true);
        try
        {
            InstitucionPicker.ItemsSource = await _api.GetInstitucionesAsync();
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

    private void OnInstitucionCambiada(object? sender, EventArgs e)
    {
        var docentes = (InstitucionPicker.SelectedItem as Institucion)?.Docentes ?? new List<Docente>();
        DocentePicker.ItemsSource = docentes;
        DocentePicker.SelectedIndex = docentes.Count == 1 ? 0 : -1;
        DocentePicker.IsEnabled = docentes.Count > 0;
        SinDocentesLabel.IsVisible = InstitucionPicker.SelectedItem is not null && docentes.Count == 0;
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

        var fecha = new DateTimeOffset(FechaPicker.Date.Add(HoraPicker.Time));
        if (fecha > DateTimeOffset.Now)
        {
            MostrarError("La fecha de la visita no puede ser posterior a la fecha actual.");
            return;
        }

        var visita = new NuevaVisita
        {
            InstitucionId = institucion.Id,
            DocenteId = docente.Id,
            FechaVisita = fecha,
            NivelInteres = Niveles[Math.Max(InteresPicker.SelectedIndex, 0)],
            EstadoAdopcion = Estados[Math.Max(EstadoPicker.SelectedIndex, 0)],
            Acuerdos = Limpiar(AcuerdosEditor.Text),
            Observaciones = Limpiar(ObservacionesEditor.Text),
        };

        Ocupado(true);
        try
        {
            await _api.RegistrarVisitaAsync(visita);
            await DisplayAlert("Visita registrada", $"Se registró la visita a {docente.NombreCompleto}.", "Aceptar");
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
