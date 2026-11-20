<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Hoja de recepción N.º {{ $receipt->number }}</title>
<style>
@page { margin: 32px; } body { font-family: DejaVu Sans, sans-serif; color: #26384a; font-size: 10px; }
h1 { font-size: 21px; margin: 8px 0; } h2 { font-size: 13px; margin: 8px 0; } p { line-height: 1.6; }
.muted { color: #65758a; } .heading { border-bottom: 2px solid #2d8060; padding-bottom: 14px; margin-bottom: 18px; }
.status { padding: 10px; background: #f0f4f7; margin: 14px 0; } table { border-collapse: collapse; width: 100%; margin-top: 18px; }
th { background: #edf3f0; text-align: left; font-size: 9px; padding: 10px 7px; } td { padding: 10px 7px; border-bottom: 1px solid #dde4eb; }
.quantity { text-align: right; } tr { page-break-inside: avoid; } thead { display: table-header-group; } tfoot td { background: #f4f7f9; font-weight: bold; }
.signatures { margin-top: 65px; } .signatures td { width: 50%; padding-top: 20px; border-top: 1px solid #9ca9b7; border-bottom: 0; text-align: center; }
.notes { white-space: pre-wrap; } .footer { margin-top: 20px; font-size: 8px; color: #65758a; }
</style></head><body>
<div class="heading"><span class="muted">{{ $store->name ?? $store->slug }} · {{ $source->type === 'order' ? 'Orden de fabricación' : 'Producción' }} #{{ $source->id }}</span><h1>Hoja de recepción N.º {{ $receipt->number }}</h1><h2>{{ $source->name }}</h2></div>
<p><strong>Fecha de recepción:</strong> {{ $receipt->received_on->format('d/m/Y') }}<br><strong>Registrada por:</strong> {{ $receipt->receiver?->name ?? 'No registrado' }}<br><strong>Versión del documento:</strong> {{ $receipt->revision }}</p>
<div class="status">@if($receipt->status === 'draft')<strong>GUARDADA · PENDIENTE DE INGRESO AL INVENTARIO</strong><br>Esta hoja no acredita ingreso al kardex ni modifica las existencias.@elseif($receipt->status === 'reversed')<strong>RECEPCIÓN REVERTIDA</strong>@else<strong>INGRESO CONFIRMADO</strong>@if($receipt->posted_at)<br>{{ $receipt->posted_at->format('d/m/Y H:i') }} · {{ $receipt->poster?->name ?? 'Responsable no registrado' }}@endif @endif @if($receipt->is_historical)<br>Documento histórico reconstruido del kardex.@endif</div>
@if($receipt->notes)<p class="notes"><strong>Observaciones:</strong><br>{{ $receipt->notes }}</p>@endif
<table><thead><tr><th>#</th><th>Producto / variante</th><th>SKU</th><th class="quantity">Cantidad</th><th>Verificado</th></tr></thead><tbody>
@foreach($receipt->items as $item)<tr><td>{{ $loop->iteration }}</td><td><strong>{{ $item->product_name }}</strong><br><span class="muted">{{ collect($item->options)->pluck('value')->implode(' · ') }}</span></td><td>{{ $item->sku ?? 'Sin SKU' }}</td><td class="quantity">{{ $item->quantity }}</td><td>________</td></tr>@endforeach
</tbody><tfoot><tr><td colspan="3">Total · {{ $receipt->items->count() }} variantes</td><td class="quantity">{{ $receipt->items->sum('quantity') }} ud.</td><td></td></tr></tfoot></table>
<table class="signatures"><tr><td>Entregado por<br>Nombre y firma</td><td>Recibido y verificado por<br>Nombre y firma</td></tr></table>
<p class="footer">Documento generado el {{ now()->format('d/m/Y H:i') }}. Confirma el ingreso al inventario desde el ERP después de verificar las cantidades.</p>
</body></html>
