<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
        $attendances = $store->attendances()->with('employee')->get();
        return responseOk($attendances, 'Asistencias obtenidas correctamente');
    }

    /**
     * Show the form for upload a new un lote.
     */

    public function upload(Store $store, Request $request)
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
            $datetime = trim($data[3] ?? '');

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

        // foreach ($rows as $row) {
        //     Log::info($row);
        // }

        $clean = [];

        foreach ($rows as $record) {

            $date = Carbon::createFromFormat('d/m/Y H:i', $record['datetime']);

            $dni = $record['dni'];
            $day = $date->format('Y-m-d');
            $time = $date->format('H:i:s');

            $key = $dni . '_' . $day;

            if (!isset($clean[$key])) {
                $clean[$key] = [
                    'dni' => $dni,
                    'name' => $record['name'],
                    'date' => $day,
                    'entry' => $time,
                    'exit' => $time
                ];
            } else {

                if ($time < $clean[$key]['entry']) {
                    $clean[$key]['entry'] = $time;
                }

                if ($time > $clean[$key]['exit']) {
                    $clean[$key]['exit'] = $time;
                }
            }
        }

        $result = array_values($clean);

        Log::info($result);

        foreach ($result as $record) {
            $attendances[] = Attendance::create([
                'store_id' => $store->id,
                'employee_id' => $this->getEmployee($record['dni']),
                'date' => $record['date'],
                'check_in' => $record['entry'],
                'check_out' => $record['exit'],
            ]);
        }

        return responseOk($attendances, 'Asistencias subidas correctamente');

    }

    public function getEmployee($param){

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
                //Marina
                return 4;
            break;
            
            default:
                return null;
                break;
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
