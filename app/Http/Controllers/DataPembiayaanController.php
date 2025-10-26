<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembiayaan;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PembiayaanImport;
use App\Models\Tahapan;
use Illuminate\Support\Facades\DB;

class DataPembiayaanController extends Controller
{
    public function index()
    {
        $pembiayaans = Pembiayaan::select('tahapan_id', DB::raw('DATE(tanggal_upload) as tanggal_upload'), DB::raw('TIME(tanggal_upload) as jam_upload'), DB::raw('count(*) as jumlah'))
                                 ->join('tahapan', 'pembiayaans.tahapan_id', '=', 'tahapan.id')
                                 ->groupBy('tahapan_id', 'tanggal_upload', 'jam_upload')
                                 ->orderBy('tahapan.created_at', 'desc')
                                 ->orderBy('tanggal_upload', 'desc')
                                 ->orderBy('jam_upload', 'desc')
                                 ->get();

        $tahapans = Tahapan::orderBy('created_at', 'desc')->get(); // Ambil data tahapan berdasarkan yang terbaru

        return view('data-pembiayaan.index', compact('pembiayaans', 'tahapans'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'tahapan_id' => 'required|exists:tahapan,id',
            'file' => 'required|mimes:xlsx,xls,csv',
            'tanggal_upload' => 'required|date_format:Y-m-d\TH:i', // Validasi tanggal dan waktu upload
        ]);

        try {
            Excel::import(new PembiayaanImport($request->tahapan_id, $request->tanggal_upload), $request->file('file'));

            return redirect()->route('pembiayaans.index')->with('success', 'Data pembiayaan berhasil diupload.');
        } catch (\Exception $e) {
            return redirect()->route('pembiayaans.index')->with('error', 'Terjadi kesalahan saat mengupload data: ' . $e->getMessage());
        }
    }

    public function destroy($tahapan_id, $tanggal_upload, $jam_upload)
    {
        Pembiayaan::where('tahapan_id', $tahapan_id)
                  ->whereDate('tanggal_upload', $tanggal_upload)
                  ->whereTime('tanggal_upload', $jam_upload)
                  ->delete();

        return redirect()->route('pembiayaans.index')->with('success', 'Data pembiayaan berhasil dihapus.');
    }
}