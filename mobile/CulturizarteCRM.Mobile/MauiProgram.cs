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

		// Quita el subrayado nativo de Android en los campos de texto:
		// en el diseño del prototipo cada campo ya va dentro de su propia caja.
		Microsoft.Maui.Handlers.EntryHandler.Mapper.AppendToMapping("SinSubrayado", (handler, view) =>
		{
#if ANDROID
			handler.PlatformView.BackgroundTintList =
				Android.Content.Res.ColorStateList.ValueOf(Android.Graphics.Color.Transparent);
#endif
		});

#if DEBUG
		builder.Logging.AddDebug();
#endif

		return builder.Build();
	}
}
