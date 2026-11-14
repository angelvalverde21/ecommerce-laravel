<?php

namespace App\Services\Dashboard;

use App\Models\Manufacture;
use App\Models\Purchase;
use Illuminate\Support\Str;

class ProductionPurchaseClassifier
{
    private const TERMS = [
        'molds' => ['molde', 'moldes', 'molderia', 'patronaje', 'patron', 'patrones', 'ploteo', 'plotter', 'escalado'],
        'samples' => ['muestra', 'muestras', 'prototipo', 'prototipos', 'contramuestra'],
        'materials' => ['tela', 'telas', 'forro', 'forros', 'hilo', 'hilos', 'boton', 'botones', 'cierre', 'cierres', 'elastico', 'elasticos', 'entretela', 'insumo', 'insumos', 'avios', 'algodon', 'jersey', 'rib', 'drill', 'popelina', 'franela', 'denim', 'twill', 'etiquetas', 'bolsas', 'cinta', 'seda', 'tafeta', 'tricotex', 'viscosa', 'malbec', 'mabet', 'tocuyo'],
        'others' => ['estibador', 'estibadores', 'estiba', 'carga', 'descarga', 'cargador', 'cargadores', 'transporte', 'flete', 'envio', 'recojo', 'gasolina', 'almuerzo'],
        'cutting' => ['corte', 'cortado', 'tizado', 'tendido', 'cortador'],
        'sewing' => ['costura', 'costurero', 'costurera', 'confeccion', 'cofeccion', 'confecciones', 'armado', 'remalle', 'remallado', 'recta', 'recubierto'],
        'finishing' => ['acabado', 'acabados', 'estampado', 'estampados', 'bordado', 'bordados', 'lavado', 'lavanderia', 'limpieza', 'planchado', 'vaporizado', 'ojal', 'ojales', 'sublimado', 'sublimacion', 'dtf', 'serigrafia'],
    ];

    public function suggest(Purchase $purchase): ?string
    {
        $scores = array_fill_keys(array_keys(self::TERMS), 0);
        foreach ($purchase->items as $item) {
            $text = ' '.trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($item->name ?? '')))).' ';
            $sample = preg_match('/\b(muestra|muestras|prototipo|prototipos|contramuestra)\b/', $text);
            $labelService = str_contains($text, 'pegado de etiquetas');
            // Context after "para" describes the destination, rather than the purchased service.
            $text = explode(' para ', $text)[0].' ';
            if (!$sample && str_contains($text, 'servicio de cinta')) { return null; }
            $matches = [];
            foreach (self::TERMS as $code => $terms) {
                if (collect($terms)->contains(fn ($term) => str_contains($text, ' '.$term.' '))) {
                    $matches[] = $code;
                }
            }
            // A sample service belongs to samples even when it mentions sewing or cutting.
            if ($sample) {
                $matches = ['samples'];
            } elseif ($labelService) {
                $matches = ['finishing'];
            } elseif (count($matches) > 1) {
                $services = array_values(array_diff($matches, ['materials']));
                if (count($services) === 1) { $matches = $services; }
            }
            if (count($matches) !== 1) {
                // Mixed services in a single concept need a person's review.
                if (count($matches) > 1) { return null; }
                continue;
            }
            $scores[$matches[0]] += max(1, abs((float) $item->quantity * (float) $item->price));
        }
        arsort($scores);
        $values = array_values($scores);
        // Require a clear majority of recognized concepts by amount.
        return $values[0] > 0 && $values[0] > array_sum($scores) * 0.6 ? array_key_first($scores) : null;
    }

    public function classify(Manufacture $production, bool $apply = true): array
    {
        return $apply
            ? \Illuminate\Support\Facades\DB::transaction(fn () => $this->classifyPending($production, true))
            : $this->classifyPending($production, false);
    }

    private function classifyPending(Manufacture $production, bool $apply): array
    {
        $stages = \App\Models\ProductionStage::pluck('id', 'code');
        if ($apply) {
            app(ProductionStageStateService::class)->synchronize($production, $stages->values()->all());
        }
        $result = ['assigned' => 0, 'remaining' => 0, 'rows' => []];
        foreach ($production->purchases()->whereNull('production_stage_id')->with('items')->get() as $purchase) {
            $code = $this->suggest($purchase) ?? 'others';
            $stageId = $stages->get($code);
            $result['rows'][] = [$production->id, $purchase->id, $purchase->items->pluck('name')->implode(' / '), $code];
            if (!$stageId) { $result['remaining']++; continue; }
            $result['assigned'] += $apply
                ? $production->purchases()->whereKey($purchase->id)->whereNull('production_stage_id')->update(['production_stage_id' => $stageId])
                : 1;
        }
        if ($apply && $result['assigned']) {
            app(ProductionStageStateService::class)->synchronize($production, $stages->values()->all());
        }
        return $result;
    }
}
