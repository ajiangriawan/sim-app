<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\LaporanBulananExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        return Excel::download(
            new LaporanBulananExport(
                $request->start_date,
                $request->end_date
            ),
            'LAPORAN_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
