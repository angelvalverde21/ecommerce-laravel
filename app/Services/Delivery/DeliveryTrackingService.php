<?php

namespace App\Services\Delivery;

use App\Models\DeliveryTracking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryTrackingService
{
    private const DATES = ['handed_over_on', 'expected_on', 'received_on'];

    public function create(Model $owner, array $data, User $actor): DeliveryTracking
    {
        return DB::transaction(function () use ($owner, $data, $actor) {
            $owner->newQuery()->whereKey($owner->getKey())->lockForUpdate()->firstOrFail();
            if ($owner->deliveryTracking()->exists()) {
                throw ValidationException::withMessages(['delivery' => 'Este registro ya tiene seguimiento. Actualiza el existente.']);
            }
            $dates = array_intersect_key($data, array_flip(self::DATES));
            $this->validateDates($dates);
            $tracking = $owner->deliveryTracking()->create($dates + ['store_id' => $owner->store_id]);
            $changes = collect($dates)->mapWithKeys(fn ($value, $key) => [$key => ['before' => null, 'after' => $value]])->all();
            $this->record($tracking, 'created', $changes, null, $actor);

            return $tracking->loadCount('reschedules');
        });
    }

    public function update(DeliveryTracking $tracking, array $data, User $actor): DeliveryTracking
    {
        return DB::transaction(function () use ($tracking, $data, $actor) {
            $tracking = DeliveryTracking::whereKey($tracking->id)->lockForUpdate()->firstOrFail();
            if ($tracking->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Otra persona actualizó esta entrega. Recarga el historial antes de guardar.']);
            }
            $before = collect(self::DATES)->mapWithKeys(fn ($field) => [$field => $tracking->$field?->format('Y-m-d')])->all();
            $dates = array_replace($before, array_intersect_key($data, array_flip(self::DATES)));
            $this->validateDates($dates);
            $changes = [];
            foreach (self::DATES as $field) {
                if ($before[$field] !== $dates[$field]) {
                    $changes[$field] = ['before' => $before[$field], 'after' => $dates[$field]];
                }
            }
            if (! $changes) {
                return $tracking->loadCount('reschedules');
            }
            $reason = trim($data['reason'] ?? '');
            $rescheduled = isset($changes['expected_on']);
            $corrected = (isset($changes['received_on']) && $before['received_on'] !== null)
                || (isset($changes['handed_over_on']) && $before['handed_over_on'] !== null);
            if (($rescheduled || $corrected) && $reason === '') {
                throw ValidationException::withMessages(['reason' => 'Indica el motivo de la reprogramación o corrección.']);
            }
            if ($rescheduled && $before['received_on']) {
                throw ValidationException::withMessages(['expected_on' => 'La entrega ya fue recibida. Conserva la fecha comprometida.']);
            }
            $tracking->fill($dates);
            $tracking->revision++;
            $tracking->save();
            $type = $rescheduled ? 'rescheduled' : (isset($changes['received_on']) && ! $before['received_on'] ? 'received' : 'corrected');
            $this->record($tracking, $type, $changes, $reason ?: null, $actor);

            return $tracking->loadCount('reschedules');
        });
    }

    private function validateDates(array $dates): void
    {
        $today = now('America/Lima')->toDateString();
        $handed = $dates['handed_over_on'] ?? null;
        $received = $dates['received_on'] ?? null;
        $errors = [];
        if ($handed && $handed > $today) {
            $errors['handed_over_on'] = 'La fecha del encargo no puede estar en el futuro.';
        }
        if ($received && $received > $today) {
            $errors['received_on'] = 'La recepción real no puede estar en el futuro.';
        }
        if ($handed && $dates['expected_on'] < $handed) {
            $errors['expected_on'] = 'La entrega prevista no puede ser anterior al encargo.';
        }
        if ($handed && $received && $received < $handed) {
            $errors['received_on'] = 'La recepción no puede ser anterior al encargo.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function record(DeliveryTracking $tracking, string $type, array $changes, ?string $reason, User $actor): void
    {
        $tracking->events()->create([
            'type' => $type, 'changes' => $changes, 'reason' => $reason,
            'user_id' => $actor->id, 'actor_name' => $actor->name,
        ]);
    }
}
