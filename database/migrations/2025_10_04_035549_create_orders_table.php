<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de pedidos locales y sincronizados con Shopify.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Identificador interno del pedido en el ERP.

            $table->foreignId('store_id')->constrained()->restrictOnDelete(); // Tienda propietaria; impide eliminarla mientras tenga pedidos.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Usuario que registra el pedido; al eliminarlo se conserva la orden.

            // Datos externos: permanecen nulos hasta vincular el pedido con Shopify.
            $table->string('shopify_order_id', 191)->nullable(); // Identificador externo usado para reconocer la orden durante la sincronización.
            $table->string('shopify_order_name')->nullable(); // Código visible en Shopify, por ejemplo: #1001; distinto del identificador externo.
            $table->unique(['store_id', 'shopify_order_id']); // Evita importar dos veces la misma orden de Shopify dentro de una tienda.

            // Copia de los productos al registrar el pedido, independiente de cambios posteriores en el catálogo.
            $table->json('variants'); // Lista de variantes con id, sku, name, opciones, quantity y price (precio unitario).

            // Configuración de entrega del pedido.
            $table->foreignId('courier_address_id')->nullable()->constrained('addresses')->nullOnDelete(); // Dirección o sede del courier; si se elimina, la referencia queda nula.
            $table->string('delivery_type', 30)->nullable(); // Modalidad de entrega, por ejemplo: agency o express.
            $table->boolean('is_cod')->default(false); // Indica si el pedido es contraentrega (cobro al entregar).
            $table->boolean('freight_collect')->default(false); // Indica si el costo del envío se paga en destino.

            // Importes monetarios expresados en la moneda del pedido, con dos decimales.
            $table->decimal('subtotal', 14, 2)->default(0); // Importe de los productos antes de sumar el IGV y el envío.
            $table->decimal('igv', 14, 2)->default(0); // Importe del IGV del pedido; no es el porcentaje del impuesto.
            $table->decimal('delivery_cost', 14, 2)->default(0); // Costo del envío registrado para el pedido.
            $table->decimal('total', 14, 2)->default(0); // Importe final calculado: subtotal + IGV + costo de envío.
            $table->string('status', 30)->default('registrado')->index(); // Estado operativo del pedido; indexado para filtrar por estado.

            $table->timestamps(); // Fechas de creación y última actualización en el ERP (created_at y updated_at).
        });
    }

    /**
     * Elimina la tabla de pedidos al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
