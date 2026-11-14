<?php

namespace Tests\Unit;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Dashboard\ProductionPurchaseClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionPurchaseClassifierTest extends TestCase
{
    public static function descriptions(): array
    {
        return [
            ['Escalado 2 tallas y ploteo', 'molds'],
            ['molde de casaca denim talla S Y M', 'molds'],
            ['SERVICIO DE LAVANDERIA DE MUESTRAS', 'samples'],
            ['TELA TAFETA PARA EL FORRO DE COSTURA', 'materials'],
            ['elástico mercializado 4cm', 'materials'],
            ['Corte Tela Marron: S', 'cutting'],
            ['SERVICIO DE CONFECCION JONI', 'sewing'],
            ['SERVICIO DE OJAL Y BOTON', 'finishing'],
            ['Pegado de etiquetas', 'finishing'],
            ['SERVICIO DE CORTE Y COFECCION', null],
            ['NEGRO, PERLA, VERDE', null],
            ['Servicio de cinta', null],
            ['envio de moto con la tela que sobro', 'others'],
            ['Servicio de estibadores y descarga', 'others'],
            ['Recorte de presupuesto', null],
        ];
    }

    #[DataProvider('descriptions')]
    public function test_description_matching(string $description, ?string $expected): void
    {
        $purchase = new Purchase;
        $purchase->setRelation('items', collect([new PurchaseItem(['name' => $description, 'quantity' => 1, 'price' => 10])]));
        $this->assertSame($expected, app(ProductionPurchaseClassifier::class)->suggest($purchase));
    }

    public function test_mixed_purchase_requires_clear_majority_by_amount(): void
    {
        $purchase = new Purchase;
        $purchase->setRelation('items', collect([
            new PurchaseItem(['name' => 'Tela', 'quantity' => 10, 'price' => 20]),
            new PurchaseItem(['name' => 'Planchado', 'quantity' => 1, 'price' => 50]),
        ]));
        $classifier = app(ProductionPurchaseClassifier::class);
        $this->assertSame('materials', $classifier->suggest($purchase));
        $purchase->items[1]->quantity = 4;
        $this->assertNull($classifier->suggest($purchase));
    }
}
