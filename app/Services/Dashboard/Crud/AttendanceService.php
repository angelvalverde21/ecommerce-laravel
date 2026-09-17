<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Store;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceService
{

    public function store(Store $store, Request $request) {}

    private function readFile(Request $request)
    {

        //
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $path = $file->store('uploads');

        // return response()->json([
        //     'path' => $path
        // ]);

        //Leyendo el archivo subido recientemente

        $path = storage_path('app/private/' . $path);

        $handle = fopen($path, 'r');

        $rows = [];
        $line = 0;

        while (($data = fgetcsv($handle, 0, ';')) !== false) {

            $line++;

            if ($line <= 2) {
                continue;
            }

            $dni = trim($data[1] ?? '');
            $name = trim($data[2] ?? '');
            $datetime = trim($data[3] ?? ''); //fecha del registro del evento

            // Ignorar filas vacías
            if (!$dni || !$datetime) {
                continue;
            }

            $rows[] = [
                'dni' => $dni,
                'name' => $name,
                'datetime' => $datetime,
            ];
        }

        fclose($handle);

        return $rows;
        // foreach ($rows as $row) {
        //     Log::info($row);
        // }

    }

    public function upload(Store $store, Request $request)
    {
        $rows = $this->readFile($request); //las assistencias en bruto con repetidos y todo

        $dailyAttendances = $this->filterRows($rows); //Elimina las marcas duplicadas y me trae solo filas unicas con inicio y fin de jornada

        $rowsToUpsert = $this->prepareRows($store, $dailyAttendances); //Construye las attendances listos para insertar en la db

        Attendance::upsert( //se usa en conjunto con las restricciones agregados a la base de datos ($table->unique(['store_id', 'employee_id', 'date'])) en la tabla attendances
            $rowsToUpsert,
            ['store_id', 'employee_id', 'date'], // columnas únicas
            ['check_in', 'check_out', 'updated_at'] // columnas a actualizar
        );
    }

    private function filterRows(array $rows)
    {
        $dailyAttendances = [];

        foreach ($rows as $record) {

            $date = Carbon::createFromFormat('d/m/Y H:i', $record['datetime']); //no usamos parse() porque el formato que envia el huellero no es standar es '02/03/2026 10:44', el estandar es '2026-03-02 10:44:00'

            $dni = $record['dni'];
            $day = $date->format('Y-m-d'); //devuelve solo la parte del dia (sin horas)
            $time = $date->format('H:i:s'); //devuelve la hora de la fecha  (sin dias)

            $key = $dni . '_' . $day;

            if (!isset($dailyAttendances[$key])) {
                $dailyAttendances[$key] = [
                    'dni' => $dni,
                    'name' => $record['name'],
                    'date' => $day,
                    'entry' => $time,
                    'exit' => $time
                ];
            } else {

                // Si la hora es menor → es entrada más temprana
                if ($time < $dailyAttendances[$key]['entry']) {
                    $dailyAttendances[$key]['entry'] = $time;
                }

                // Si la hora es mayor → es salida más tarde
                if ($time > $dailyAttendances[$key]['exit']) {
                    $dailyAttendances[$key]['exit'] = $time;
                }
            }
        }


        return array_values($dailyAttendances);
    }

    private function prepareRows(Store $store, array $dailyAttendances)
    { //tipos nativos en minusculas

        $rowsToUpsert = [];

        foreach ($dailyAttendances as $attendance) {

            $rowsToUpsert[] = [
                'store_id' => $store->id,
                'employee_id' => $this->getEmployee($attendance['dni']),
                'date' => $attendance['date'],
                'check_in' => $attendance['entry'],
                'check_out' => $attendance['exit'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rowsToUpsert;
    }




    private function getEmployee(string $param): ?int

    {

        switch ($param) {
            case '1':
                //Ayin
                return 3;
                break;

            case '2':
                //Jenifer
                return 2;
                break;

            case '5':
                //Fiorella
                return 6;
                break;

            case '76540879':
                //Marina
                return 4;
                break;

            case '42412498':
                //Angel
                return 1;
                break;

            default:
                return null;
                break;
        }
    }


    // Recibe fechas y completa rango de fechas (por si hay faltas) para enviarlas al frontend (angular)
    public function completeRangeEmployee(Employee $employee, Collection $attendances)
    {

        $schedules = collect([]);

        $employee = $employee->load('schedules');
        // Log::info($employee->schedules);
        // Log::info($employee);

        $schedules = $employee->schedules;

        if ($schedules->isEmpty()) {
            // Log::info("no hay schedules");
            return;
        }

        $schedules = $employee->schedules->keyBy('day_of_week');

        // Log::info($schedules);

        $attendances =  $attendances
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->date)->format('Y-m-d');
            });

        if ($attendances->isEmpty()) {
            return collect([]);
        }

        $dates = $attendances
            ->pluck('date') //solo trae la columna date
            ->map(fn($d) => Carbon::parse($d));

        $start = $dates->min();
        $end   = $dates->max();

        $period = CarbonPeriod::create($start, $end);

        $completeAttendances = [];

        foreach ($period as $date) { //Recorre dia por dia

            // Log::info($date->dayOfWeek);

            if ($date->dayOfWeek == Carbon::SUNDAY) {
                continue;
            }

            $schedule = $schedules[$date->dayOfWeek] ?? null;

            Log::info($employee->id);
            Log::info($schedule->day_of_week);

            $key = $date->format('Y-m-d');

            //si hay registro

            if ($attendances->has($key)) { //Esto quiere decir que hay un registro en el huellero, tambien puede ser que el empleado le haya tocado home office pero igual vino

                //Si el empleado marca su asistencia quiere decir de todas maneras que es presencial (onsite) asi ese dia le haya tocado remoto o home_office

                $attendance = $attendances[$key];

                //si hay registro es porque el empleado vino presencialmente a oficina a trabajar

                Log::info("onsite");

                $checkIn  = Carbon::createFromFormat('H:i:s', $attendance->check_in);
                $checkOut = Carbon::createFromFormat('H:i:s', $attendance->check_out);

                //comprobamos si llego tarde
                $attendance->is_late = $this->isLate($employee, $attendance);
                $attendance->missing = false;

                //Calculamos los minutos trabajados
                $attendance->minutes = $checkIn->diffInMinutes($checkOut);

                //Calculamos el tiempo de trabajo
                $work_time_end = Carbon::createFromFormat('H:i:s', $employee->work_time_end);

                //remoto, presencial, home_office, etc
                $attendance->work_type = $schedule->work_type;

                $work_time_end->addMinutes($employee->tolerance_minutes);

                if ($work_time_end > $checkOut) {
                    $attendance->minutes_computed = $checkIn->diffInMinutes($checkOut);
                } else {
                    $attendance->minutes_computed = $checkIn->diffInMinutes($work_time_end);
                }
                //Calculamos el salario neto y el salario del dia

                $salary_day = round($employee->salary / 30, 0);


                if ($employee->type == "fulltime") {
                    $salary_neto = round(($salary_day * ($attendance->minutes - 60)) / 480, 2);
                } else {
                    $salary_neto = round(($salary_day * $attendance->minutes) / 480, 2); //480 es 8 horas
                }



                $completeAttendances[] = [
                    'id' => $attendance->id,
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'minutes' => $attendance->minutes,
                    'minutes_computed' => $attendance->minutes_computed,
                    'salary_day' => $salary_day,
                    'salary_neto' => $salary_neto,
                    'salary_extra' => round($salary_neto - $salary_day, 2),
                    'date' => $key,
                    'missing' => $attendance->missing,
                    'is_late' => $attendance->is_late,
                    'work_type' => 'onsite',
                    'employee_name' => $employee->user->name,
                    'employee_id' => $employee->id,
                    'employee_work_type' => $employee->type,
                    'comment' => $attendance->comment,
                    'missing_text' => null
                ];
            } else {

                switch (strtolower($schedule->work_type)) {

                    case 'home_office':

                        Log::info("home_office");

                        // $attendance->is_late = false;
                        // $attendance->missing = false;
                        // $attendance->work_type = $schedule->work_type;

                        $checkIn  = Carbon::createFromFormat('H:i:s', $schedule->start_time ?? "09:00:00");
                        $checkOut = Carbon::createFromFormat('H:i:s', $schedule->end_time ?? "19:00:00");

                        $completeAttendances[] = [
                            'id' => null,
                            'check_in' => null,
                            'check_out' => null,
                            'minutes' => $checkIn->diffInMinutes($checkOut),
                            'minutes_computed' => $checkIn->diffInMinutes($checkOut),
                            'salary_day' => round($employee->salary / 30, 0),
                            'salary_neto' => round($employee->salary / 30, 0),
                            'salary_extra' => 0,
                            'date' => $key,
                            'missing' => false,
                            'is_late' => false,
                            'work_type' => 'home_office',
                            'employee_name' => $employee->user->name,
                            'employee_id' => $employee->id,
                            'employee_work_type' => $employee->type,
                            // 'comment' => $attendance->comment,
                            'missing_text' => "REMOTO"
                        ];
                        break;

                    case 'rest': //descanzo
                        $completeAttendances[] = [
                            'id' => null,
                            'check_in' => null,
                            'check_out' => null,
                            'minutes' => null,
                            'minutes_computed' => null,
                            'salary_day' => round($employee->salary / 30, 0),
                            'salary_neto' => round($employee->salary / 30, 0),
                            'salary_extra' => 0,
                            'date' => $key,
                            'missing' => false,
                            'is_late' => false,
                            'work_type' => 'rest',
                            'employee_name' => $employee->user->name,
                            'employee_id' => $employee->id,
                            'employee_work_type' => $employee->type,
                            // 'comment' => $attendance->comment,
                            'missing_text' => "DESCANZO"
                        ];
                        break;

                    //Los demas casos se considera una falta
                    default:
                        $completeAttendances[] = [
                            'id' => null,
                            'check_in' => null,
                            'check_out' => null,
                            'minutes' => 0,
                            'minutes_computed' => 0,
                            'salary_day' => round($employee->salary / 30, 0),
                            'salary_neto' => -round($employee->salary / 30, 0),
                            'salary_extra' => -round($employee->salary / 30, 0),
                            'date' => $key,
                            'missing' => true,
                            'is_late' => null, //no llego, entnoces no se puede decir que llego tarde
                            'work_type' => null,
                            'employee_name' => $employee->user->name,
                            'employee_work_type' => $employee->type,
                            'employee_id' => $employee->id,
                            // 'comment' => $attendance->comment,
                            'missing_text' => "FALTO"
                        ];
                        break;
                }


                // $completeAttendances[] = $attendance;
            }
        }

        return $completeAttendances;

        // return collect($completeAttendances);
    }

    // Metodo que determina si llego tarde

    private function isLate(Employee $employee, Attendance $attendance)
    {

        //Tolerencia de cada usuario
        $toleranceMinutes = $employee->tolerance_minutes;

        //Creamos la hora de entrada con carbon para usar sus funciones 

        $scheduleStart = Carbon::createFromFormat('H:i:s', $employee->work_time_start); //Hora de entrada del usuario

        //Creamos el checkIn con carbon para tambien usar sus funciones

        $checkIn  = Carbon::createFromFormat('H:i:s', $attendance->check_in);

        $lateMinutes = $scheduleStart->diffInMinutes($checkIn, false); //El false es importante porque: Si llega antes → será negativo, Si llega después → será positivo

        return $lateMinutes > $toleranceMinutes;
    }

    private function minutesComputed() {}
}
