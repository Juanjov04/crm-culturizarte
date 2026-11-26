<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\User;
use App\Support\Ecuador;
use App\Support\Indicadores;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sprint 7 - Panel de indicadores de gestión comercial y retención
 * (RF-04, CU-W02). Exclusivo para Gerente y Administrador.
 */
class IndicadoresController extends Controller
{
    public function index(Request $request): View
    {
        return view('indicadores.index', $this->datos($request));
    }

    /** Vista para imprimir o guardar como PDF desde el navegador. */
    public function imprimir(Request $request): View
    {
        return view('indicadores.imprimir', $this->datos($request));
    }

    /**
     * Reporte tabular en CSV (separado por punto y coma, UTF-8) que se abre en
     * Excel y se importa directamente en Power BI.
     */
    public function exportar(Request $request, string $tipo): StreamedResponse
    {
        $indicadores = new Indicadores($this->filtrosValidados($request));

        [$encabezados, $filas] = match ($tipo) {
            'visitas' => $this->filasVisitas($indicadores),
            'colegios' => $this->filasColegios($indicadores),
            'muestras' => $this->filasMuestras($indicadores),
        };

        $archivo = "crm_{$tipo}_{$indicadores->desde->format('Ymd')}_{$indicadores->hasta->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, ';');
            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ';');
            }
            fclose($salida);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function datos(Request $request): array
    {
        $indicadores = new Indicadores($this->filtrosValidados($request));

        return [
            'indicadores' => $indicadores,
            'filtros' => $indicadores->filtros(),
            'kpis' => $indicadores->kpis(),
            'graficos' => [
                'visitasPorMes' => $indicadores->visitasPorMes(),
                'estados' => $indicadores->estadosDeColegios(),
                'provincias' => $indicadores->adopcionesPorProvincia(),
                'series' => $indicadores->muestrasPorSerie(),
                'anual' => $indicadores->comparativaAnual(),
            ],
            'ranking' => $indicadores->rankingAsesores(),
            'colegios' => $indicadores->estadoPorColegio(),
            'asesores' => User::role('Asesor')->orderBy('name')->get(['id', 'name']),
            'provincias' => array_keys(Ecuador::provincias()),
            'series' => Producto::SERIES,
        ];
    }

    private function filtrosValidados(Request $request): array
    {
        return $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'provincia' => ['nullable', 'string', 'max:60'],
            'asesor_id' => ['nullable', 'integer'],
            'serie' => ['nullable', 'string', 'max:40'],
        ]);
    }

    private function filasVisitas(Indicadores $indicadores): array
    {
        $visitas = $indicadores->visitasDelPeriodo()
            ->with(['user:id,name', 'institucion:id,nombre,codigo_amie,provincia,ciudad,tipo', 'docente:id,nombres,apellidos,area_asignatura'])
            ->withSum('muestrasEntregadas as unidades_muestras', 'cantidad')
            ->orderBy('fecha_visita')
            ->get();

        return [
            ['id_visita', 'fecha', 'asesor', 'codigo_amie', 'colegio', 'tipo', 'provincia', 'ciudad', 'docente', 'area', 'nivel_interes', 'estado_adopcion', 'origen', 'capturada_offline', 'unidades_muestras'],
            $visitas->map(fn ($v) => [
                $v->id, $v->fecha_visita->format('Y-m-d H:i'), $v->user->name, $v->institucion->codigo_amie, $v->institucion->nombre,
                $v->institucion->tipo, $v->institucion->provincia, $v->institucion->ciudad, $v->docente?->nombre_completo, $v->docente?->area_asignatura,
                $v->nivel_interes, $v->estado_adopcion, $v->origen, $v->capturada_offline ? 'si' : 'no', (int) $v->unidades_muestras,
            ]),
        ];
    }

    private function filasColegios(Indicadores $indicadores): array
    {
        return [
            ['codigo_amie', 'colegio', 'tipo', 'provincia', 'ciudad', 'estado_comercial', 'ultima_visita', 'asesor', 'visitas', 'unidades_muestras'],
            $indicadores->estadoPorColegio()->map(fn ($c) => [
                $c->codigo_amie, $c->colegio, $c->tipo, $c->provincia, $c->ciudad, $c->estado,
                $c->ultima_visita->format('Y-m-d'), $c->asesor, $c->visitas, $c->muestras,
            ]),
        ];
    }

    private function filasMuestras(Indicadores $indicadores): array
    {
        $muestras = $indicadores->muestrasDelPeriodo()
            ->with(['producto', 'visita.user:id,name', 'visita.institucion:id,nombre,provincia', 'visita.docente:id,nombres,apellidos'])
            ->get();

        return [
            ['fecha', 'asesor', 'colegio', 'provincia', 'docente', 'codigo_texto', 'texto', 'edicion', 'serie', 'grado', 'cantidad'],
            $muestras->sortBy(fn ($m) => $m->visita->fecha_visita)->map(fn ($m) => [
                $m->visita->fecha_visita->format('Y-m-d'), $m->visita->user->name, $m->visita->institucion->nombre, $m->visita->institucion->provincia,
                $m->visita->docente?->nombre_completo, $m->producto->codigo_isbn, $m->producto->titulo, $m->producto->edicion,
                $m->producto->serie, $m->producto->nivel_educativo, $m->cantidad,
            ]),
        ];
    }
}
