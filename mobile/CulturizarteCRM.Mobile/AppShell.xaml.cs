using CulturizarteCRM.Mobile.Views;

namespace CulturizarteCRM.Mobile;

public partial class AppShell : Shell
{
	public AppShell()
	{
		InitializeComponent();

		// Pantalla a la que se navega desde el inicio (las visitas ya son una pestaña).
		Routing.RegisterRoute(nameof(VisitaFormPage), typeof(VisitaFormPage));
	}
}
