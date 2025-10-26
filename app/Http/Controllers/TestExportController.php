<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TestSimpleExport;

class TestExportController extends Controller
{
    public function testExport()
    {
        // Set memory limit and execution time
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes
        
        $filename = 'test-export-' . date('Y-m-d-H-i-s') . '.xlsx';
        
        try {
            return Excel::download(new TestSimpleExport(), $filename);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    public function testStrukturExport()
    {
        // Set memory limit and execution time
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes
        
        try {
            $tahapans = \App\Models\Tahapan::orderBy('id')->limit(3)->get();
            
            // Buat data sederhana untuk testing
            $strukturData = collect([
                [
                    'kode_rekening' => '4.1.01',
                    'nama_rekening' => 'Pendapatan Pajak Daerah',
                    'level' => 2,
                    'pagu_per_tahapan' => [1 => 1000000, 2 => 2000000, 3 => 3000000],
                    'is_pendapatan' => true
                ],
                [
                    'kode_rekening' => '5.1.01',
                    'nama_rekening' => 'Belanja Operasi',
                    'level' => 2,
                    'pagu_per_tahapan' => [1 => 2000000, 2 => 4000000, 3 => 6000000],
                    'is_pendapatan' => false,
                    'is_pembiayaan' => false
                ]
            ]);
            
            $filename = 'test-struktur-export-' . date('Y-m-d-H-i-s') . '.xlsx';
            
            return Excel::download(new \App\Exports\StrukturBelanjaApbdMinimalExport($strukturData, $tahapans), $filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
