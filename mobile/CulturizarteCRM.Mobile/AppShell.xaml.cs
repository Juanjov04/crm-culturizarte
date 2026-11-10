using CulturizarteCRM.Mobile.Views;

namespace CulturizarteCRM.Mobile;

public partial class AppShell : Shell
{
	public AppShell()
	{
		InitializeComponent();

		// Pantallas a las que se navega desde el inicio.
		Routing.RegisterRoute(nameof(VisitaFormPage), typeof(VisitaFormPage));
		Routing.RegisterRoute(nameof(VisitasPage), typeof(VisitasPage));
	}
}
