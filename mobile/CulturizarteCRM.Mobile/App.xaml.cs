namespace CulturizarteCRM.Mobile;

public partial class App : Application
{
	public App()
	{
		InitializeComponent();

		// La app usa la paleta clara del prototipo (Figura 10) aunque el teléfono esté en modo oscuro.
		UserAppTheme = AppTheme.Light;

		MainPage = new AppShell();
	}
}
