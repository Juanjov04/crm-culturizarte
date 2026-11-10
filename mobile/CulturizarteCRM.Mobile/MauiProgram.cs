using CulturizarteCRM.Mobile.Services;
using CulturizarteCRM.Mobile.Views;
using Microsoft.Extensions.Logging;

namespace CulturizarteCRM.Mobile;

public static class MauiProgram
{
	public static MauiApp CreateMauiApp()
	{
		var builder = MauiApp.CreateBuilder();
		builder
			.UseMauiApp<App>()
			.ConfigureFonts(fonts =>
			{
				fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
				fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
			});

		// Servicios y pantallas (inyección de dependencias).
		builder.Services.AddSingleton<ApiClient>();
		builder.Services.AddTransient<LoginPage>();
		builder.Services.AddTransient<InicioPage>();
		builder.Services.AddTransient<VisitaFormPage>();
		builder.Services.AddTransient<VisitasPage>();

#if DEBUG
		builder.Logging.AddDebug();
#endif

		return builder.Build();
	}
}
