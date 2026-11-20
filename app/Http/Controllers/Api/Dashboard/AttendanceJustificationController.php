<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\Crud\AttendanceService;
use App\Services\Dashboard\Employee\AttendanceDocumentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceJustificationController extends Controller
{
    private function authorizeStore(Store $store, Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
    }

    public function store(Store $store, Request $request, AttendanceService $service)
    {
        $this->authorizeStore($store, $request);
        $values = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.CarbonImmutable::today('America/Lima')->toDateString()],
            'reason' => ['required', 'string', 'max:1000', 'regex:/\S/'],
        ]);
        $employee = $store->employees()->with('schedules')->findOrFail($values['employee_id']);
        $schedule = $employee->schedules->firstWhere('day_of_week', CarbonImmutable::parse($values['date'])->dayOfWeekIso);
        abort_if($schedule?->work_type === 'rest', 422, 'No se puede justificar una falta en un día de descanso.');
        $attendance = DB::transaction(function () use ($store, $employee, $values, $request) {
            $attendance = $store->attendances()->where('employee_id', $employee->id)->where('date', $values['date'])->lockForUpdate()->first();
            abort_if($attendance && ($attendance->check_in || $attendance->check_out || $attendance->work_type === 'home_office'), 422,
                'Este día ya tiene marcaciones o trabajo remoto. Revisa la asistencia antes de justificar una falta.');
            $data = ['absence_justified' => true, 'justification_reason' => trim($values['reason']),
                'justified_by' => $request->user()->id, 'justified_at' => now(), 'work_type' => 'onsite'];
            if ($attendance) {
                $attendance->update($data);
                return $attendance->fresh();
            }
            return $store->attendances()->create([...$data, 'employee_id' => $employee->id, 'date' => $values['date']]);
        });

        return responseOk($service->describe($employee, $attendance), 'Falta justificada. El día cuenta completo para el pago.');
    }

    public function upload(Store $store, int $attendance_id, Request $request, AttendanceDocumentService $documents)
    {
        $this->authorizeStore($store, $request);
        $attendance = $store->attendances()->findOrFail($attendance_id);
        abort_unless($attendance->absence_justified, 422, 'Primero guarda la justificación.');
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240']]);
        $values = $documents->store($request->file('file'), 'attendance-justifications/'.$store->id.'/'.$attendance->id);
        try {
            $image = $attendance->images()->create($values);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete(array_values(array_intersect_key($values, array_flip(['thumbnail', 'medium', 'large']))));
            throw $error;
        }
        return responseOk($documents->present($image), 'Documento adjuntado.');
    }

    public function download(Store $store, int $attendance_id, int $document_id, Request $request, AttendanceDocumentService $documents)
    {
        $this->authorizeStore($store, $request);
        $document = $store->attendances()->findOrFail($attendance_id)->images()->findOrFail($document_id);
        abort_unless(str_starts_with((string) $document->large, 'attendance-justifications/'.$store->id.'/'.$attendance_id.'/'), 404);
        abort_unless(Storage::disk('local')->exists($document->large), 404);
        $values = $request->validate(['variant' => ['sometimes', 'in:thumbnail,medium,large']]);
        $variant = $values['variant'] ?? 'large';
        if ($variant !== 'large') $documents->ensureVariants($document);
        $path = $document->{$variant};
        abort_unless($path && str_starts_with($path, 'attendance-justifications/'.$store->id.'/'.$attendance_id.'/') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, $variant === 'large' ? $document->name : pathinfo($document->name, PATHINFO_FILENAME).'-'.$variant.'.webp');
    }
}
