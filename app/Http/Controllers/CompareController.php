<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataAnggaran;
use App\Models\Tahapan;
use App\Models\KodeRekening;
use App\Exports\CompareRekExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;


class CompareController extends Controller
{
    public function compareOpd()
{
    // Ambil data rekap dari database
    $rekap = DataAnggaran::select(
            'kode_skpd', 
            'nama_skpd', 
            'tahapan_id', 
            // DB::raw('DATE(tanggal_upload) as tanggal_upload'), 
            // DB::raw('TIME(tanggal_upload) as jam_upload'), 
            DB::raw('SUM(pagu) as total_pagu')
        )
        ->groupBy('kode_skpd', 'nama_skpd', 'tahapan_id')
        ->get()
        ->groupBy('kode_skpd');

    // Ambil data tahapan dari database
    $tahapans = Tahapan::all();

    // Hitung total pagu untuk setiap kombinasi tahapan_id, tanggal_upload, dan jam_upload
    $totalPagu = [];
    $selisihPagu = [];
    $persentaseSelisihPagu = [];
    $totalSelisihPagu = 0;
    $totalPaguTahapanPertama = 0;
    $totalPaguTahapanTerakhir = 0;

    foreach ($rekap as $kode_skpd => $items) {
        $firstItem = $items->first();
        $lastItem = $items->last();
        $selisihPagu[$kode_skpd] = $lastItem->total_pagu - $firstItem->total_pagu;
        $totalSelisihPagu += $selisihPagu[$kode_skpd];

        // Hitung persentase selisih
        if ($firstItem->total_pagu != 0) {
            $persentaseSelisihPagu[$kode_skpd] = ($selisihPagu[$kode_skpd] / $firstItem->total_pagu) * 100;
        } else {
            $persentaseSelisihPagu[$kode_skpd] = 0;
        }

        $totalPaguTahapanPertama += $firstItem->total_pagu;
        $totalPaguTahapanTerakhir += $lastItem->total_pagu;

        foreach ($items as $item) {
            $key = $item->tahapan_id . '_' . str_replace('-', '_', $item->tanggal_upload) . '_' . str_replace(':', '_', $item->jam_upload);
            if (!isset($totalPagu[$key])) {
                $totalPagu[$key] = 0;
            }
            $totalPagu[$key] += $item->total_pagu;
        }
    }

    // Hitung total persentase selisih
    $totalPersentaseSelisihPagu = 0;
    if ($totalPaguTahapanPertama != 0) {
        $totalPersentaseSelisihPagu = ($totalSelisihPagu / $totalPaguTahapanPertama) * 100;
    }

    return view('compare.compare_opd', compact('rekap', 'tahapans', 'totalPagu', 'selisihPagu', 'persentaseSelisihPagu', 'totalSelisihPagu', 'totalPersentaseSelisihPagu'));
}


public function compareDataRek(Request $request)
{
    // Ambil filter dari request
    $tahapanId = $request->input('tahapan_id');
    $keyword = $request->input('keyword');
    $kodeRekeningInput = $request->input('kode_rekening', ''); // Bisa string atau array
    
    // Parse kode rekening dari input (handle string dan array)
    $kodeRekening = [];
    if ($kodeRekeningInput) {
        if (is_array($kodeRekeningInput)) {
            // Jika sudah array (dari form lama dengan checkbox)
            $kodeRekening = array_filter($kodeRekeningInput);
        } else {
            // Jika string (dari form baru dengan text input)
            $kodeRekening = array_filter(array_map('trim', explode(',', $kodeRekeningInput)));
        }
    }
    
    // Ambil filter pengecualian dari request
    $excludeKeyword = $request->input('exclude_keyword');
    $excludeKodeRekeningInput = $request->input('exclude_kode_rekening', '');
    
    // Parse exclude kode rekening dari input
    $excludeKodeRekening = [];
    if ($excludeKodeRekeningInput) {
        if (is_array($excludeKodeRekeningInput)) {
            $excludeKodeRekening = array_filter($excludeKodeRekeningInput);
        } else {
            $excludeKodeRekening = array_filter(array_map('trim', explode(',', $excludeKodeRekeningInput)));
        }
    }
    
    // Debug: Log input untuk troubleshooting
    \Illuminate\Support\Facades\Log::info('Compare Data Rek Input:', [
        'tahapan_id' => $tahapanId,
        'keyword' => $keyword,
        'kode_rekening_input' => $kodeRekeningInput,
        'kode_rekening_input_type' => gettype($kodeRekeningInput),
        'kode_rekening' => $kodeRekening,
        'kode_rekening_count' => count($kodeRekening),
        'exclude_keyword' => $excludeKeyword,
        'exclude_kode_rekening' => $excludeKodeRekening,
        'exclude_kode_rekening_count' => count($excludeKodeRekening)
    ]);
    
    // Ambil data tahapan dari database (urutkan dari yang terbaru)
    $tahapans = Tahapan::orderBy('created_at', 'desc')->get();
    
    // Set default tahapan ke yang terbaru jika belum ada pilihan
    if (!$tahapanId && $tahapans->count() > 0) {
        $tahapanId = $tahapans->first()->id;
    }
    
    // Ambil data kode rekening dari database
    $kodeRekenings = KodeRekening::orderBy('kode_rekening')->get();
    
    // Debug: Log kodeRekenings data
    \Illuminate\Support\Facades\Log::info('KodeRekenings Data:', [
        'count' => $kodeRekenings->count(),
        'first_item_type' => $kodeRekenings->count() > 0 ? gettype($kodeRekenings->first()) : 'empty',
        'first_item_class' => $kodeRekenings->count() > 0 ? get_class($kodeRekenings->first()) : 'empty'
    ]);
    
    // Query data rekap rekening belanja seluruh OPD
    $query = DataAnggaran::select(
        'kode_skpd',
        'nama_skpd', 
        'kode_rekening', 
        'nama_rekening',
        'nama_standar_harga',
        'tahapan_id',
        DB::raw('SUM(pagu) as total_pagu')
    );
    
    // Filter berdasarkan tahapan jika dipilih
    if ($tahapanId) {
        $query->where('tahapan_id', $tahapanId);
    }
    
    // Filter berdasarkan kata kunci pada nama rekening atau nama standar harga
    if ($keyword) {
        // Pisahkan kata kunci berdasarkan koma atau spasi
        $keywords = array_filter(array_map('trim', explode(',', $keyword)));
        if (empty($keywords)) {
            // Jika tidak ada koma, coba pisahkan berdasarkan spasi
            $keywords = array_filter(array_map('trim', explode(' ', $keyword)));
        }
        
        $query->where(function($q) use ($keywords) {
            foreach ($keywords as $kw) {
                if (!empty($kw)) {
                    $q->orWhere(function($subQ) use ($kw) {
                        // Gunakan pendekatan yang kompatibel dengan MySQL
                        // Cari kata yang diawali spasi atau di awal string, dan diakhiri spasi atau di akhir string
                        $subQ->where('nama_rekening', 'REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)')
                              ->orWhere('nama_standar_harga', 'REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)');
                    });
                }
            }
        });
    }
    
    // Filter berdasarkan kode rekening
    if ($kodeRekening) {
        // Handle array dari select multiple
        if (is_array($kodeRekening)) {
            $kodeRekeningFilter = array_filter($kodeRekening);
        } else {
            // Handle string (backward compatibility)
            $kodeRekeningFilter = array_filter(array_map('trim', explode(',', $kodeRekening)));
            if (empty($kodeRekeningFilter)) {
                $kodeRekeningFilter = array_filter(array_map('trim', explode(' ', $kodeRekening)));
            }
        }
        
        if (!empty($kodeRekeningFilter)) {
            $query->where(function($q) use ($kodeRekeningFilter) {
                foreach ($kodeRekeningFilter as $kode) {
                    if (!empty($kode)) {
                        $q->orWhere('kode_rekening', 'LIKE', '%' . $kode . '%');
                    }
                }
            });
        }
    }
    
    // Filter pengecualian berdasarkan kata kunci
    if ($excludeKeyword) {
        $excludeKeywords = array_filter(array_map('trim', explode(',', $excludeKeyword)));
        if (empty($excludeKeywords)) {
            $excludeKeywords = array_filter(array_map('trim', explode(' ', $excludeKeyword)));
        }
        
        if (!empty($excludeKeywords)) {
            $query->where(function($q) use ($excludeKeywords) {
                foreach ($excludeKeywords as $kw) {
                    if (!empty($kw)) {
                        $q->where(function($subQ) use ($kw) {
                            $subQ->where('nama_rekening', 'NOT REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)')
                                  ->where('nama_standar_harga', 'NOT REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)');
                        });
                    }
                }
            });
        }
    }
    
    // Filter pengecualian berdasarkan kode rekening
    if ($excludeKodeRekening && !empty($excludeKodeRekening)) {
        $query->where(function($q) use ($excludeKodeRekening) {
            foreach ($excludeKodeRekening as $kode) {
                if (!empty($kode)) {
                    $q->where('kode_rekening', 'NOT LIKE', '%' . $kode . '%');
                }
            }
        });
    }
    
    $rekap = $query->groupBy('kode_skpd', 'nama_skpd', 'kode_rekening', 'nama_rekening', 'nama_standar_harga', 'tahapan_id')
        ->orderByRaw('CAST(kode_skpd AS UNSIGNED) ASC, kode_skpd ASC')
        ->orderBy('kode_rekening', 'asc')
        ->get();
    
    // Pastikan data hanya ditampilkan jika ada filter yang diterapkan
    // Cek apakah ada filter selain tahapan default
    $hasActiveFilter = $keyword || (is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening);
    
    if (!$hasActiveFilter) {
        // Jika tidak ada filter aktif (hanya tahapan default), kirim data kosong
        $rekap = collect();
        $availableTahapans = collect();
        $totalPerTahapan = [];
        $grandTotal = 0;
    } else {
        // Jika ada filter tahapan, hanya tampilkan data untuk tahapan tersebut
        if ($tahapanId) {
            $rekap = $rekap->where('tahapan_id', $tahapanId);
            
            // Hanya tampilkan tahapan yang dipilih
            $availableTahapans = collect([$tahapanId]);
            
            // Hitung total untuk tahapan yang dipilih sesuai dengan filter kata kunci
            $totalPerTahapan = [];
            $totalPerTahapan[$tahapanId] = $rekap->sum('total_pagu');
            
            // Grand total sama dengan total tahapan yang dipilih
            $grandTotal = $totalPerTahapan[$tahapanId];
        } else {
            // Jika tidak ada filter tahapan, tampilkan semua tahapan
            $availableTahapans = DataAnggaran::select('tahapan_id')
                ->distinct()
                ->orderBy('tahapan_id')
                ->pluck('tahapan_id');
            
            // Hitung total per tahapan untuk footer sesuai dengan filter kata kunci
            $totalPerTahapan = [];
            foreach ($availableTahapans as $tahapanId) {
                $totalPerTahapan[$tahapanId] = $rekap->where('tahapan_id', $tahapanId)->sum('total_pagu');
            }
            
            // Hitung grand total
            $grandTotal = array_sum($totalPerTahapan);
        }
    }

    return view('compare.compare_rek', compact(
        'rekap', 
        'tahapans', 
        'kodeRekenings',
        'availableTahapans',
        'totalPerTahapan',
        'grandTotal',
        'tahapanId',
        'keyword',
        'kodeRekening',
        'excludeKeyword',
        'excludeKodeRekening'
    ));
}

public function exportExcel(Request $request)
{
    // Ambil filter dari request
    $tahapanId = $request->input('tahapan_id');
    $keyword = $request->input('keyword');
    $kodeRekeningInput = $request->input('kode_rekening', ''); // Bisa string atau array
    
    // Parse kode rekening dari input (handle string dan array)
    $kodeRekening = [];
    if ($kodeRekeningInput) {
        if (is_array($kodeRekeningInput)) {
            // Jika sudah array (dari form lama dengan checkbox)
            $kodeRekening = array_filter($kodeRekeningInput);
        } else {
            // Jika string (dari form baru dengan text input)
            $kodeRekening = array_filter(array_map('trim', explode(',', $kodeRekeningInput)));
        }
    }
    
    // Ambil data tahapan dari database (urutkan dari yang terbaru)
    $tahapans = Tahapan::orderBy('created_at', 'desc')->get();
    
    // Set default tahapan ke yang terbaru jika belum ada pilihan
    if (!$tahapanId && $tahapans->count() > 0) {
        $tahapanId = $tahapans->first()->id;
    }
    
    // Query data rekap rekening belanja seluruh OPD
    $query = DataAnggaran::select(
        'kode_skpd',
        'nama_skpd', 
        'kode_rekening', 
        'nama_rekening',
        'nama_standar_harga',
        'tahapan_id',
        DB::raw('SUM(pagu) as total_pagu')
    );
    
    // Filter berdasarkan tahapan jika dipilih
    if ($tahapanId) {
        $query->where('tahapan_id', $tahapanId);
    }
    
    // Filter berdasarkan kata kunci pada nama rekening atau nama standar harga
    if ($keyword) {
        // Pisahkan kata kunci berdasarkan koma atau spasi
        $keywords = array_filter(array_map('trim', explode(',', $keyword)));
        if (empty($keywords)) {
            // Jika tidak ada koma, coba pisahkan berdasarkan spasi
            $keywords = array_filter(array_map('trim', explode(' ', $keyword)));
        }
        
        $query->where(function($q) use ($keywords) {
            foreach ($keywords as $kw) {
                if (!empty($kw)) {
                    $q->orWhere(function($subQ) use ($kw) {
                        // Gunakan pendekatan yang kompatibel dengan MySQL
                        // Cari kata yang diawali spasi atau di awal string, dan diakhiri spasi atau di akhir string
                        $subQ->where('nama_rekening', 'REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)')
                              ->orWhere('nama_standar_harga', 'REGEXP', '(^|[[:space:]])' . preg_quote($kw, '/') . '([[:space:]]|$)');
                    });
                }
            }
        });
    }
    
    // Filter berdasarkan kode rekening
    if ($kodeRekening) {
        // Handle array dari select multiple
        if (is_array($kodeRekening)) {
            $kodeRekeningFilter = array_filter($kodeRekening);
        } else {
            // Handle string (backward compatibility)
            $kodeRekeningFilter = array_filter(array_map('trim', explode(',', $kodeRekening)));
            if (empty($kodeRekeningFilter)) {
                $kodeRekeningFilter = array_filter(array_map('trim', explode(' ', $kodeRekening)));
            }
        }
        
        if (!empty($kodeRekeningFilter)) {
            $query->where(function($q) use ($kodeRekeningFilter) {
                foreach ($kodeRekeningFilter as $kode) {
                    if (!empty($kode)) {
                        $q->orWhere('kode_rekening', 'LIKE', '%' . $kode . '%');
                    }
                }
            });
        }
    }
    
    $rekap = $query->groupBy('kode_skpd', 'nama_skpd', 'kode_rekening', 'nama_rekening', 'nama_standar_harga', 'tahapan_id')
        ->orderByRaw('CAST(kode_skpd AS UNSIGNED) ASC, kode_skpd ASC')
        ->orderBy('kode_rekening', 'asc')
        ->get();
    
    // Jika ada filter tahapan, hanya tampilkan data untuk tahapan tersebut
    if ($tahapanId) {
        $rekap = $rekap->where('tahapan_id', $tahapanId);
        
        // Hanya tampilkan tahapan yang dipilih
        $availableTahapans = collect([$tahapanId]);
        
        // Hitung total untuk tahapan yang dipilih sesuai dengan filter kata kunci
        $totalPerTahapan = [];
        $totalPerTahapan[$tahapanId] = $rekap->sum('total_pagu');
        
        // Grand total sama dengan total tahapan yang dipilih
        $grandTotal = $totalPerTahapan[$tahapanId];
    } else {
        // Jika tidak ada filter tahapan, tampilkan semua tahapan
        $availableTahapans = DataAnggaran::select('tahapan_id')
            ->distinct()
            ->orderBy('tahapan_id')
            ->pluck('tahapan_id');
        
        // Hitung total per tahapan untuk footer sesuai dengan filter kata kunci
        $totalPerTahapan = [];
        foreach ($availableTahapans as $tahapanId) {
            $totalPerTahapan[$tahapanId] = $rekap->where('tahapan_id', $tahapanId)->sum('total_pagu');
        }
        
        // Hitung grand total
        $grandTotal = array_sum($totalPerTahapan);
    }
    
    // Pastikan data hanya ditampilkan jika ada filter yang diterapkan
    if (!$keyword && !$tahapanId && !$kodeRekening) {
        $rekap = collect();
        $availableTahapans = collect();
    }
    
    $filename = 'Rekap_Rekening_Belanja_OPD';
    if ($tahapanId) {
        $tahapanName = $tahapans->find($tahapanId)->name ?? 'Tahapan_' . $tahapanId;
        $filename .= '_' . str_replace(' ', '_', $tahapanName);
    }
    if ($keyword) {
        $filename .= '_' . str_replace([' ', ','], '_', $keyword);
    }
    if ($kodeRekening) {
        // Convert array ke string untuk filename
        $kodeRekeningStr = is_array($kodeRekening) ? implode('-', $kodeRekening) : $kodeRekening;
        $filename .= '_' . str_replace([' ', ',', '.'], '_', $kodeRekeningStr);
    }
    $filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    return Excel::download(new CompareRekExport($rekap, $tahapans, $availableTahapans, $tahapanId, $keyword, $kodeRekening), $filename);
}


public function compareDataOpdRek(Request $request)
    {
        // Ambil daftar SKPD untuk dropdown
        $skpds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->get();

        // Ambil filter SKPD dari request
        $kodeSkpd = $request->input('kode_skpd');

        // Jika tidak ada filter SKPD, kirimkan view tanpa data
        if (empty($kodeSkpd)) {
            return view('compare.compare_opd_rek', [
                'rekap' => collect(),
                'tahapans' => Tahapan::all(),
                'totalPagu' => [],
                'selisihPagu' => [],
                'persentaseSelisihPagu' => [],
                'totalSelisihPagu' => 0,
                'totalPersentaseSelisihPagu' => 0,
                'skpds' => $skpds,
                'kodeSkpd' => $kodeSkpd
            ]);
        }

        // Query data berdasarkan kode rekening
        $query = DataAnggaran::select(
            'kode_skpd',
            'nama_skpd',
            'kode_rekening',
            'nama_rekening',
            'tahapan_id',
            DB::raw('DATE(tanggal_upload) as tanggal_upload'),
            DB::raw('TIME(tanggal_upload) as jam_upload'),
            DB::raw('SUM(pagu) as total_pagu')
        )
        ->groupBy('kode_skpd', 'nama_skpd', 'kode_rekening', 'nama_rekening', 'tahapan_id', 'tanggal_upload', 'jam_upload')
        ->orderBy('kode_rekening')
        ->orderBy('tahapan_id')
        ->orderBy('tanggal_upload')
        ->orderBy('jam_upload');

        // Jika ada filter SKPD, tambahkan kondisi
        if (!empty($kodeSkpd)) {
            $query->where('kode_skpd', $kodeSkpd);
        }

        $rekap = $query->get()->groupBy('kode_rekening');

        // Pastikan $rekap tidak null
        if ($rekap->isEmpty()) {
            $rekap = collect();
        }

        // Ambil data tahapan dari database
        $tahapans = Tahapan::all();

        // Hitung total pagu untuk setiap kombinasi tahapan_id, tanggal_upload, dan jam_upload
        $totalPagu = [];
        $selisihPagu = [];
        $persentaseSelisihPagu = [];
        $totalSelisihPagu = 0;
        $totalPaguTahapanPertama = 0;
        $totalPaguTahapanTerakhir = 0;

        foreach ($rekap as $kode_rekening => $items) {
            if ($items) {
                $firstItem = $items->first();
                $lastItem = $items->last();
                $selisihPagu[$kode_rekening] = $lastItem->total_pagu - $firstItem->total_pagu;
                $totalSelisihPagu += $selisihPagu[$kode_rekening];

                // Hitung persentase selisih
                if ($firstItem->total_pagu != 0) {
                    $persentaseSelisihPagu[$kode_rekening] = ($selisihPagu[$kode_rekening] / $firstItem->total_pagu) * 100;
                } else {
                    $persentaseSelisihPagu[$kode_rekening] = 0;
                }

                $totalPaguTahapanPertama += $firstItem->total_pagu;
                $totalPaguTahapanTerakhir += $lastItem->total_pagu;

                foreach ($items as $item) {
                    $key = $item->tahapan_id . '_' . str_replace('-', '_', $item->tanggal_upload) . '_' . str_replace(':', '_', $item->jam_upload);
                    if (!isset($totalPagu[$key])) {
                        $totalPagu[$key] = 0;
                    }
                    $totalPagu[$key] += $item->total_pagu;
                }
            }
        }

        // Hitung total persentase selisih
        $totalPersentaseSelisihPagu = 0;
        if ($totalPaguTahapanPertama != 0) {
            $totalPersentaseSelisihPagu = ($totalSelisihPagu / $totalPaguTahapanPertama) * 100;
        }

        return view('compare.compare_opd_rek', compact('rekap', 'tahapans', 'totalPagu', 'selisihPagu', 'persentaseSelisihPagu', 'totalSelisihPagu', 'totalPersentaseSelisihPagu', 'skpds', 'kodeSkpd'));
    }


    public function comparePerSubKegiatan(Request $request)
    {
        $opds = DataAnggaran::select('kode_skpd', 'nama_skpd')->distinct()->orderBy('kode_skpd')->get();
    
        $kodeOpd = $request->input('kode_opd');
        $tahapan1 = $request->input('data1') ?? 1;
        $tahapan2 = $request->input('data2') ?? 1;
    
        $data1 = Tahapan::select('id', 'name')->distinct()->orderBy('id')->get();
        $data2 = Tahapan::select('id', 'name')->distinct()->orderBy('id')->get();
    
        // Query utama: ambil semua data dari kedua tahapan (yang match by kode_sub_kegiatan + kode_skpd)
        $baseQuery = DataAnggaran::select(
            'kode_sub_kegiatan',
            'nama_sub_kegiatan',
            'nama_sub_unit',
            'kode_skpd as kode_opd',
            'nama_skpd as nama_opd',
            DB::raw('SUM(CASE WHEN tahapan_id = ' . $tahapan1 . ' THEN pagu ELSE 0 END) as pagu_original'),
            DB::raw('SUM(CASE WHEN tahapan_id = ' . $tahapan2 . ' THEN pagu ELSE 0 END) as pagu_revisi')
        )
        ->groupBy('kode_sub_kegiatan', 'nama_sub_kegiatan', 'kode_sub_unit', 'nama_sub_unit','kode_skpd', 'nama_skpd')
        ->orderBy('kode_skpd', 'asc')
        ->orderBy('kode_sub_unit', 'asc')
        ->orderBy('kode_sub_kegiatan', 'asc');
    
        if (!empty($kodeOpd)) {
            $baseQuery->where('kode_skpd', $kodeOpd);
        }
    
        // Subquery: ambil semua kode_sub_kegiatan yang ada di tahapan1
        $subQueryTahapan1 = DataAnggaran::where('tahapan_id', $tahapan1)
            ->select(DB::raw('DISTINCT kode_sub_kegiatan'));
    
        // Query tambahan: ambil kegiatan baru yang hanya ada di tahapan2
        $newDataQuery = DataAnggaran::select(
            'kode_sub_kegiatan',
            'nama_sub_kegiatan',
            'nama_sub_unit',
            'kode_skpd as kode_opd',
            'nama_skpd as nama_opd',
            DB::raw('0 as pagu_original'),
            DB::raw('SUM(pagu) as pagu_revisi')
        )
        ->where('tahapan_id', $tahapan2)
        ->whereNotIn('kode_sub_kegiatan', $subQueryTahapan1)
        ->groupBy('kode_sub_kegiatan', 'nama_sub_kegiatan', 'kode_sub_unit', 'nama_sub_unit','kode_skpd', 'nama_skpd')
        ->orderBy('kode_skpd', 'asc')
        ->orderBy('kode_sub_unit', 'asc')
        ->orderBy('kode_sub_kegiatan', 'asc');
    
        if (!empty($kodeOpd)) {
            $newDataQuery->where('kode_skpd', $kodeOpd);
        }
    
        // Gabungkan kedua query dan urutkan berdasarkan kode_skpd, kode_sub_unit, dan kode_sub_kegiatan
        $rekap = $baseQuery->union($newDataQuery)
            ->orderBy('kode_opd', 'asc')
            ->orderBy('nama_sub_unit', 'asc')
            ->orderBy('kode_sub_kegiatan', 'asc')
            ->get();
    
        // Hitung selisih dan persentase perubahan
        foreach ($rekap as $item) {
            $item->selisih = $item->pagu_revisi - $item->pagu_original;
            $item->persentase = $item->pagu_original > 0 ? ($item->selisih / $item->pagu_original) * 100 : 100;
        }
    
        return view('compare.compare-sub-kegiatan', compact('rekap', 'opds', 'data1','data2', 'tahapan1', 'tahapan2'));
    }
    
}