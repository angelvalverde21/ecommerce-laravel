<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\GraphQLResponseHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopifyReportService
{
    protected string $apiUrl;
    protected string $token;
    protected string $pageInfo;


    public function getDailyOrdersReport(int $days = 7): array
    {
        // Normalizar valor
        $days = max(1, $days);

        // Definir rango: últimos N días incluyendo hoy
        $endDate = Carbon::now('America/Lima')->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        // Traer órdenes del servicio interno (usa Y-m-d strings)
        $ordersRaw = $this->getOrdersBetween($startDate->toDateString(), $endDate->toDateString());
        $orders = collect($ordersRaw ?? []);

        // Si no vinieron órdenes, registrar para debug
        if ($orders->isEmpty()) {
            Log::warning('getDailyOrdersReport: getOrdersBetween returned empty', [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]);
        }

        // Helper que intenta extraer la fecha de creación desde varias claves posibles
        $extractCreatedAt = function ($order) {
            if (is_array($order)) {
                return $order['date'] ?? $order['createdAt'] ?? $order['created_at']
                    ?? ($order['order']['createdAt'] ?? null)
                    ?? ($order['node']['createdAt'] ?? null)
                    ?? ($order['order']['created_at'] ?? null);
            }

            // si es objeto
            if (is_object($order)) {
                return $order->date ?? $order->createdAt ?? $order->created_at
                    ?? ($order->order->createdAt ?? null)
                    ?? ($order->node->createdAt ?? null)
                    ?? ($order->order->created_at ?? null);
            }

            return null;
        };

        // Mapear cada orden a su fecha local (America/Lima)
        $mapped = $orders->map(function ($order) use ($extractCreatedAt) {
            $createdAt = $extractCreatedAt($order);
            if (!$createdAt) {
                return null; // lo filtraremos luego
            }

            try {
                $localDate = Carbon::parse($createdAt)->setTimezone('America/Lima')->toDateString();
            } catch (\Exception $e) {
                // si no se puede parsear
                Log::warning('getDailyOrdersReport: failed to parse createdAt', ['createdAt' => $createdAt]);
                return null;
            }

            return ['date' => $localDate, 'order' => $order];
        })->filter(); // eliminamos nulls

        // Agrupar por fecha local
        $grouped = $mapped->groupBy('date');

        // Construir el periodo con días consecutivos (inclusivo)
        $period = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->setTimezone('America/Lima')->toDateString();
            $count = $grouped->has($date) ? $grouped->get($date)->count() : 0;
            $period[] = [
                'date' => $date,
                'order_count' => $count,
            ];
        }

        return $period;
    }
}
