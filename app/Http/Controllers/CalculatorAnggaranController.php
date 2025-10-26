<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CalculatorAnggaranExport;

class CalculatorAnggaranController extends Controller
{
    public function index()
    {
        // Ambil data tahapan
        $tahapans = DB::table('tahapan')->get();
        
        // Ambil tahapan terakhir sebagai default (menggunakan id terbesar)
        $defaultTahapan = DB::table('tahapan')
            ->orderBy('id', 'desc')
            ->first();
        
        // Ambil data SKPD
        $skpds = DB::table('data_anggarans')
            ->select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->get();

        return view('simulasi-perubahan.calculator-anggaran', compact('tahapans', 'skpds', 'defaultTahapan'));
    }

    public function getData(Request $request)
    {
        try {
            // Query dasar
            $query = DB::table('data_anggarans as da')
                ->join('tahapan as t', 'da.tahapan_id', '=', 't.id');

            // Filter berdasarkan tahapan (wajib)
            if ($request->filled('tahapan')) {
                $query->where('da.tahapan_id', $request->tahapan);
            } else {
                // Jika tidak ada tahapan yang dipilih, ambil tahapan terakhir
                $query->where('da.tahapan_id', function($subquery) {
                    $subquery->select('id')
                        ->from('tahapan')
                        ->orderBy('id', 'desc')
                        ->limit(1);
                });
            }

            // Jika OPD dipilih, tampilkan detail per OPD
            if ($request->filled('opd')) {
                $query->where('da.kode_skpd', $request->opd);
                $query->select(
                    'da.kode_skpd',
                    'da.nama_skpd',
                    'da.kode_sub_kegiatan',
                    'da.nama_sub_kegiatan',
                    'da.kode_rekening',
                    'da.nama_rekening',
                    'da.kode_standar_harga',
                    'da.nama_standar_harga',
                    DB::raw('SUM(da.pagu) as anggaran')
                )
                ->groupBy(
                    'da.kode_skpd',
                    'da.nama_skpd',
                    'da.kode_sub_kegiatan',
                    'da.nama_sub_kegiatan',
                    'da.kode_rekening',
                    'da.nama_rekening',
                    'da.kode_standar_harga',
                    'da.nama_standar_harga'
                )
                ->orderBy('da.kode_sub_kegiatan')
                ->orderBy('da.kode_rekening')
                ->orderBy('da.kode_standar_harga');
                $data = $query->get()->map(function ($item) {
                    return [
                        'kode_sub_kegiatan' => $item->kode_sub_kegiatan,
                        'nama_sub_kegiatan' => $item->nama_sub_kegiatan,
                        'kode_rekening' => $item->kode_rekening,
                        'nama_rekening' => $item->nama_rekening,
                        'kode_standar_harga' => $item->kode_standar_harga,
                        'nama_standar_harga' => $item->nama_standar_harga,
                        'anggaran' => $item->anggaran,
                    ];
                });
            } else {
                $data = collect([]); // Data kosong jika OPD tidak dipilih
            }

            return response()->json([
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getData: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Terjadi kesalahan saat mengambil data'
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            // Set memory limit and execution time
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes

            // Query dasar
            $query = DB::table('data_anggarans as da')
                ->join('tahapan as t', 'da.tahapan_id', '=', 't.id');

            // Filter berdasarkan tahapan (wajib)
            if ($request->filled('tahapan')) {
                $query->where('da.tahapan_id', $request->tahapan);
            } else {
                return response()->json(['error' => 'Tahapan harus dipilih'], 400);
            }

            // Filter berdasarkan OPD (wajib)
            if ($request->filled('opd')) {
                $query->where('da.kode_skpd', $request->opd);
            } else {
                return response()->json(['error' => 'OPD harus dipilih'], 400);
            }

            // Ambil data tahapan dan OPD untuk nama file
            $tahapan = DB::table('tahapan')->where('id', $request->tahapan)->first();
            $opd = DB::table('data_anggarans')
                ->where('kode_skpd', $request->opd)
                ->select('nama_skpd')
                ->first();

            $tahapanName = $tahapan ? $tahapan->name : 'Unknown';
            $opdName = $opd ? $opd->nama_skpd : 'Unknown';

            // Query data untuk export
            $data = $query->select(
                'da.kode_skpd',
                'da.nama_skpd',
                'da.kode_sub_kegiatan',
                'da.nama_sub_kegiatan',
                'da.kode_rekening',
                'da.nama_rekening',
                'da.kode_standar_harga',
                'da.nama_standar_harga',
                DB::raw('SUM(da.pagu) as anggaran')
            )
            ->groupBy(
                'da.kode_skpd',
                'da.nama_skpd',
                'da.kode_sub_kegiatan',
                'da.nama_sub_kegiatan',
                'da.kode_rekening',
                'da.nama_rekening',
                'da.kode_standar_harga',
                'da.nama_standar_harga'
            )
            ->orderBy('da.kode_sub_kegiatan')
            ->orderBy('da.kode_rekening')
            ->orderBy('da.kode_standar_harga')
            ->get()
            ->map(function ($item) {
                return [
                    'kode_sub_kegiatan' => $item->kode_sub_kegiatan,
                    'nama_sub_kegiatan' => $item->nama_sub_kegiatan,
                    'kode_rekening' => $item->kode_rekening,
                    'nama_rekening' => $item->nama_rekening,
                    'kode_standar_harga' => $item->kode_standar_harga,
                    'nama_standar_harga' => $item->nama_standar_harga,
                    'anggaran' => $item->anggaran,
                ];
            })
            ->toArray();

            // Generate filename
            $filename = 'data-anggaran-' . 
                       str_replace(' ', '-', strtolower($tahapanName)) . '-' . 
                       str_replace(' ', '-', strtolower($opdName)) . '-' . 
                       date('Y-m-d') . '.xlsx';

            return Excel::download(new CalculatorAnggaranExport($data, $tahapanName, $opdName), $filename);

        } catch (\Exception $e) {
            Log::error('Error in exportExcel: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Terjadi kesalahan saat export data'
            ], 500);
        }
    }
} 