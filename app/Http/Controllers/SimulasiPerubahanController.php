<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataAnggaran;
use App\Models\Tahapan;
use App\Models\KodeRekening;
use App\Models\SimulasiPenyesuaianAnggaran;
use App\Models\Realisasi;
use App\Exports\RekapitulasiStrukturOpdExport;
use App\Exports\RekapitulasiStrukturOpdModalExport;
use App\Exports\StrukturBelanjaApbdMinimalExport;
use Maatwebsite\Excel\Facades\Excel;

class SimulasiPerubahanController extends Controller
{
    public function index(Request $request)
    {
        $tahapans = Tahapan::all();
        $tahapanId = $request->input('tahapan_id');

        // Ambil daftar SKPD unik (kode_skpd & nama_skpd), urut berdasarkan kode_skpd
        $skpds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        // Ambil kode SKPD dari request (tidak ada default)
        $skpdKode = $request->input('skpd');

        // Ambil objek SKPD terpilih
        $skpdTerpilih = $skpds->where('kode_skpd', $skpdKode)->first();
        // Ambil objek tahapan terpilih
        $tahapanTerpilih = $tahapans->where('id', $tahapanId)->first();

        $rekap = collect();
        if ($tahapanId && $skpdKode) {
            $rekap = DataAnggaran::select('kode_rekening', 'nama_rekening')
                ->selectRaw('SUM(pagu) as total_pagu')
                ->where('tahapan_id', $tahapanId)
                ->where('kode_skpd', $skpdKode)
                ->groupBy('kode_rekening', 'nama_rekening')
                ->orderBy('kode_rekening')
                ->limit(200) // Limit untuk mencegah timeout
            ->get();
        }

        // Ambil semua kode rekening yang diawali angka 5 dan hanya 2 atau 3 segmen (misal: 5.1 dan 5.1.01)
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'") // 2 segmen, contoh: 5.1
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'"); // 3 segmen, contoh: 5.1.01
        })
        ->orderBy('kode_rekening')
        ->get();

        // Ambil semua data simulasi penyesuaian anggaran HANYA untuk OPD aktif
        $simulasiPenyesuaian = collect();
        if ($skpdKode) {
            $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::where('kode_opd', $skpdKode)
                ->orderBy('id', 'desc')
                ->limit(200) // Limit untuk mencegah timeout
            ->get();
        }

        // Ambil data realisasi untuk SKPD dan mapping berdasarkan kode rekening
        $realisasiMap = [];
        $realisasiSegmenMap = [];
        if ($skpdKode) {
            $realisasiRows = Realisasi::where('kode_opd', $skpdKode)->get();
            foreach ($realisasiRows as $row) {
                $realisasiMap[$row->kode_rekening] = $row->realisasi;
                $segments = explode('.', $row->kode_rekening);
                // Hanya rekap realisasi dari kode rekening dengan 6 segmen
                if (count($segments) === 6) {
                    $seg2 = $segments[0] . '.' . $segments[1];
                    $realisasiSegmenMap[$seg2] = ($realisasiSegmenMap[$seg2] ?? 0) + $row->realisasi;
                    $seg3 = $segments[0] . '.' . $segments[1] . '.' . $segments[2];
                    $realisasiSegmenMap[$seg3] = ($realisasiSegmenMap[$seg3] ?? 0) + $row->realisasi;
                }
            }
        }

        return view('simulasi-perubahan.index', [
            'tahapans' => $tahapans,
            'tahapanId' => $tahapanId,
            'rekap' => $rekap,
            'skpds' => $skpds,
            'skpdKode' => $skpdKode,
            'skpdTerpilih' => $skpdTerpilih,
            'tahapanTerpilih' => $tahapanTerpilih,
            'kodeRekenings' => $kodeRekenings,
            'simulasiPenyesuaian' => $simulasiPenyesuaian,
            'realisasiMap' => $realisasiMap,
            'realisasiSegmenMap' => $realisasiSegmenMap,
        ]);
    }

    public function simulasiBelanjaOpd(Request $request)
    {
        $tahapans = Tahapan::all();
        $tahapanId = $request->input('tahapan_id');

        // Ambil rekap pagu per OPD
        $rekapOpd = collect();
        $simulasiPenyesuaian = collect();
        if ($tahapanId) {
            $rekapOpd = DataAnggaran::select('kode_skpd', 'nama_skpd')
                ->selectRaw('SUM(pagu) as total_pagu')
                ->where('tahapan_id', $tahapanId)
                ->groupBy('kode_skpd', 'nama_skpd')
                ->orderBy('kode_skpd')
                ->limit(200) // Limit untuk mencegah timeout
            ->get();

            // Ambil semua penyesuaian untuk seluruh OPD
            $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::all();

            // Ambil total realisasi per OPD (hanya kode rekening 6 segmen)
            $realisasiPerOpd = \App\Models\Realisasi::select('kode_opd')
                ->whereRaw('LENGTH(kode_rekening) - LENGTH(REPLACE(kode_rekening, ".", "")) = 5')
                ->selectRaw('SUM(realisasi) as total_realisasi')
                ->groupBy('kode_opd')
                ->pluck('total_realisasi', 'kode_opd');

            // Tambahkan kolom total_pagu_setelah_penyesuaian dan realisasi ke setiap OPD
            foreach ($rekapOpd as $opd) {
                $penyesuaian = $simulasiPenyesuaian->where('kode_opd', $opd->kode_skpd);
                $totalPenyesuaian = 0;
                foreach ($penyesuaian as $adj) {
                    if ($adj->operasi == '+') {
                        $totalPenyesuaian += $adj->nilai;
                    } elseif ($adj->operasi == '-') {
                        $totalPenyesuaian -= $adj->nilai;
                    }
                }
                $opd->total_pagu_setelah_penyesuaian = $opd->total_pagu + $totalPenyesuaian;
                $opd->total_penyesuaian = $totalPenyesuaian;
                $opd->total_realisasi = $realisasiPerOpd[$opd->kode_skpd] ?? 0;
            }
        }

        return view('simulasi-perubahan.simulasi-belanja-opd', [
            'tahapans' => $tahapans,
            'tahapanId' => $tahapanId,
            'rekapOpd' => $rekapOpd,
        ]);
    }

    public function rekapitulasiStrukturOpd(Request $request)
    {
        $tahapans = Tahapan::all();
        $tahapanId = $request->input('tahapan_id');

        // Ambil semua kode rekening yang diawali angka 5 dan hanya 2 atau 3 segmen (misal: 5.1 dan 5.1.01)
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'") // 2 segmen, contoh: 5.1
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'"); // 3 segmen, contoh: 5.1.01
        })
        ->orderBy('kode_rekening')
        ->get();

        // Ambil daftar semua OPD
        $opds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        $rekapitulasiData = collect();
        
        if ($tahapanId) {
            // Ambil semua data simulasi penyesuaian anggaran
            $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::all();

            // Ambil data realisasi untuk semua OPD
            $realisasiMap = [];
            $realisasiRows = Realisasi::all();
            foreach ($realisasiRows as $row) {
                $realisasiMap[$row->kode_opd][$row->kode_rekening] = $row->realisasi;
            }

            // Proses data untuk setiap OPD
            foreach ($opds as $opd) {
                $opdData = [
                    'kode_skpd' => $opd->kode_skpd,
                    'nama_skpd' => $opd->nama_skpd,
                    'total_anggaran' => 0,
                    'total_realisasi' => 0,
                    'total_penyesuaian' => 0,
                    'total_proyeksi' => 0,
                    'struktur_belanja' => []
                ];

                // Ambil data anggaran per kode rekening untuk OPD ini
                $anggaranPerRekening = DataAnggaran::select('kode_rekening', 'nama_rekening')
                    ->selectRaw('SUM(pagu) as total_pagu')
                    ->where('tahapan_id', $tahapanId)
                    ->where('kode_skpd', $opd->kode_skpd)
                    ->groupBy('kode_rekening', 'nama_rekening')
                    ->limit(200) // Limit untuk mencegah timeout
            ->get();

                // Proses setiap kode rekening struktur
                foreach ($kodeRekenings as $kr) {
                    $is3Segmen = count(explode('.', $kr->kode_rekening)) === 3;
                    
                    // Hitung total pagu untuk kode rekening ini
                    $totalPagu = $anggaranPerRekening->where(function($item) use ($kr) {
                        return str_starts_with($item->kode_rekening, $kr->kode_rekening);
                    })->sum('total_pagu');

                    // Hitung total realisasi untuk kode rekening ini
                    $totalRealisasi = 0;
                    $realisasiOpd = $realisasiMap[$opd->kode_skpd] ?? [];
                    foreach ($realisasiOpd as $kodeRek => $realisasi) {
                        if (str_starts_with($kodeRek, $kr->kode_rekening)) {
                            $totalRealisasi += $realisasi;
                        }
                    }

                    // Hitung penyesuaian untuk kode rekening ini
                    $totalPenyesuaian = 0;
                    $penyesuaianOpd = $simulasiPenyesuaian->where('kode_opd', $opd->kode_skpd);
                    foreach ($penyesuaianOpd as $adj) {
                        if (str_starts_with($adj->kode_rekening, $kr->kode_rekening)) {
                            if ($adj->operasi == '+') {
                                $totalPenyesuaian += $adj->nilai;
                            } elseif ($adj->operasi == '-') {
                                $totalPenyesuaian -= $adj->nilai;
                            }
                        }
                    }

                    // Hitung proyeksi perubahan
                    $anggaranRealisasi = $totalPagu - $totalRealisasi;
                    $proyeksiPerubahan = $anggaranRealisasi + $totalPenyesuaian;

                    // Tambahkan ke struktur belanja
                    $opdData['struktur_belanja'][$kr->kode_rekening] = [
                        'nama_rekening' => $kr->uraian,
                        'anggaran' => $totalPagu,
                        'realisasi' => $totalRealisasi,
                        'anggaran_realisasi' => $anggaranRealisasi,
                        'penyesuaian' => $totalPenyesuaian,
                        'proyeksi' => $proyeksiPerubahan,
                        'is_3_segmen' => $is3Segmen
                    ];

                    // Tambahkan ke total jika 3 segmen
                    if ($is3Segmen) {
                        $opdData['total_anggaran'] += $totalPagu;
                        $opdData['total_realisasi'] += $totalRealisasi;
                        $opdData['total_penyesuaian'] += $totalPenyesuaian;
                        $opdData['total_proyeksi'] += $proyeksiPerubahan;
                    }
                }

                $rekapitulasiData->push($opdData);
            }
        }

        return view('simulasi-perubahan.rekapitulasi-struktur-opd', [
            'tahapans' => $tahapans,
            'tahapanId' => $tahapanId,
            'kodeRekenings' => $kodeRekenings,
            'rekapitulasiData' => $rekapitulasiData,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $tahapanId = $request->input('tahapan_id');
        
        if (!$tahapanId) {
            return redirect()->back()->with('error', 'Silakan pilih tahapan terlebih dahulu.');
        }

        // Ambil data yang sama seperti di method rekapitulasiStrukturOpd
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'")
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'");
        })
        ->orderBy('kode_rekening')
        ->get();

        $opds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        $rekapitulasiData = collect();
        
        // Ambil semua data simulasi penyesuaian anggaran
        $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::all();

        // Ambil data realisasi untuk semua OPD
        $realisasiMap = [];
        $realisasiRows = Realisasi::all();
        foreach ($realisasiRows as $row) {
            $realisasiMap[$row->kode_opd][$row->kode_rekening] = $row->realisasi;
        }

        // Proses data untuk setiap OPD
        foreach ($opds as $opd) {
            $opdData = [
                'kode_skpd' => $opd->kode_skpd,
                'nama_skpd' => $opd->nama_skpd,
                'total_anggaran' => 0,
                'total_realisasi' => 0,
                'total_penyesuaian' => 0,
                'total_proyeksi' => 0,
                'struktur_belanja' => []
            ];

            // Ambil data anggaran per kode rekening untuk OPD ini
            $anggaranPerRekening = DataAnggaran::select('kode_rekening', 'nama_rekening')
                ->selectRaw('SUM(pagu) as total_pagu')
                ->where('tahapan_id', $tahapanId)
                ->where('kode_skpd', $opd->kode_skpd)
                ->groupBy('kode_rekening', 'nama_rekening')
                ->limit(200) // Limit untuk mencegah timeout
            ->get();

            // Proses setiap kode rekening struktur
            foreach ($kodeRekenings as $kr) {
                $is3Segmen = count(explode('.', $kr->kode_rekening)) === 3;
                
                // Hitung total pagu untuk kode rekening ini
                $totalPagu = $anggaranPerRekening->where(function($item) use ($kr) {
                    return str_starts_with($item->kode_rekening, $kr->kode_rekening);
                })->sum('total_pagu');

                // Hitung total realisasi untuk kode rekening ini
                $totalRealisasi = 0;
                $realisasiOpd = $realisasiMap[$opd->kode_skpd] ?? [];
                foreach ($realisasiOpd as $kodeRek => $realisasi) {
                    if (str_starts_with($kodeRek, $kr->kode_rekening)) {
                        $totalRealisasi += $realisasi;
                    }
                }

                // Hitung penyesuaian untuk kode rekening ini
                $totalPenyesuaian = 0;
                $penyesuaianOpd = $simulasiPenyesuaian->where('kode_opd', $opd->kode_skpd);
                foreach ($penyesuaianOpd as $adj) {
                    if (str_starts_with($adj->kode_rekening, $kr->kode_rekening)) {
                        if ($adj->operasi == '+') {
                            $totalPenyesuaian += $adj->nilai;
                        } elseif ($adj->operasi == '-') {
                            $totalPenyesuaian -= $adj->nilai;
                        }
                    }
                }

                // Hitung proyeksi perubahan
                $anggaranRealisasi = $totalPagu - $totalRealisasi;
                $proyeksiPerubahan = $anggaranRealisasi + $totalPenyesuaian;

                // Tambahkan ke struktur belanja
                $opdData['struktur_belanja'][$kr->kode_rekening] = [
                    'nama_rekening' => $kr->uraian,
                    'anggaran' => $totalPagu,
                    'realisasi' => $totalRealisasi,
                    'anggaran_realisasi' => $anggaranRealisasi,
                    'penyesuaian' => $totalPenyesuaian,
                    'proyeksi' => $proyeksiPerubahan,
                    'is_3_segmen' => $is3Segmen
                ];

                // Tambahkan ke total jika 3 segmen
                if ($is3Segmen) {
                    $opdData['total_anggaran'] += $totalPagu;
                    $opdData['total_realisasi'] += $totalRealisasi;
                    $opdData['total_penyesuaian'] += $totalPenyesuaian;
                    $opdData['total_proyeksi'] += $proyeksiPerubahan;
                }
            }

            $rekapitulasiData->push($opdData);
        }

        $tahapanName = Tahapan::find($tahapanId)->name ?? 'Tahapan ' . $tahapanId;
        $filename = 'rekapitulasi-struktur-opd-' . $tahapanName . '-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new RekapitulasiStrukturOpdExport($rekapitulasiData, $kodeRekenings, $tahapanName), $filename);
    }

    public function rekapitulasiStrukturOpdModal(Request $request)
    {
        $tahapans = Tahapan::all();
        $tahapanId = $request->input('tahapan_id');

        // Ambil semua kode rekening yang diawali angka 5 dan hanya 2 atau 3 segmen (misal: 5.1 dan 5.1.01)
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'") // 2 segmen, contoh: 5.1
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'"); // 3 segmen, contoh: 5.1.01
        })
        ->orderBy('kode_rekening')
        ->get();

        // Ambil daftar semua OPD
        $opds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        $rekapitulasiData = collect();
        
        if ($tahapanId) {
            // Ambil semua data simulasi penyesuaian anggaran
            $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::all();

            // Ambil data realisasi untuk semua OPD
            $realisasiMap = [];
            $realisasiRows = Realisasi::all();
            foreach ($realisasiRows as $row) {
                $realisasiMap[$row->kode_opd][$row->kode_rekening] = $row->realisasi;
            }

            // Proses data untuk setiap OPD
            foreach ($opds as $opd) {
                $opdData = [
                    'kode_skpd' => $opd->kode_skpd,
                    'nama_skpd' => $opd->nama_skpd,
                    'total_anggaran' => 0,
                    'total_realisasi' => 0,
                    'total_penyesuaian' => 0,
                    'total_proyeksi' => 0,
                    'struktur_belanja' => []
                ];

                // Ambil data anggaran per kode rekening untuk OPD ini
                $anggaranPerRekening = DataAnggaran::select('kode_rekening', 'nama_rekening')
                    ->selectRaw('SUM(pagu) as total_pagu')
                    ->where('tahapan_id', $tahapanId)
                    ->where('kode_skpd', $opd->kode_skpd)
                    ->groupBy('kode_rekening', 'nama_rekening')
                    ->limit(200) // Limit untuk mencegah timeout
            ->get();

                // Proses setiap kode rekening struktur
                foreach ($kodeRekenings as $kr) {
                    $is3Segmen = count(explode('.', $kr->kode_rekening)) === 3;
                    
                    // Hitung total pagu untuk kode rekening ini
                    $totalPagu = $anggaranPerRekening->where(function($item) use ($kr) {
                        return str_starts_with($item->kode_rekening, $kr->kode_rekening);
                    })->sum('total_pagu');

                    // Hitung total realisasi untuk kode rekening ini
                    $totalRealisasi = 0;
                    $realisasiOpd = $realisasiMap[$opd->kode_skpd] ?? [];
                    foreach ($realisasiOpd as $kodeRek => $realisasi) {
                        if (str_starts_with($kodeRek, $kr->kode_rekening)) {
                            $totalRealisasi += $realisasi;
                        }
                    }

                    // Hitung penyesuaian untuk kode rekening ini
                    $totalPenyesuaian = 0;
                    $penyesuaianOpd = $simulasiPenyesuaian->where('kode_opd', $opd->kode_skpd);
                    foreach ($penyesuaianOpd as $adj) {
                        if (str_starts_with($adj->kode_rekening, $kr->kode_rekening)) {
                            if ($adj->operasi == '+') {
                                $totalPenyesuaian += $adj->nilai;
                            } elseif ($adj->operasi == '-') {
                                $totalPenyesuaian -= $adj->nilai;
                            }
                        }
                    }

                    // Hitung proyeksi perubahan
                    $anggaranRealisasi = $totalPagu - $totalRealisasi;
                    $proyeksiPerubahan = $anggaranRealisasi + $totalPenyesuaian;

                    // Tambahkan ke struktur belanja
                    $opdData['struktur_belanja'][$kr->kode_rekening] = [
                        'nama_rekening' => $kr->uraian,
                        'anggaran' => $totalPagu,
                        'realisasi' => $totalRealisasi,
                        'anggaran_realisasi' => $anggaranRealisasi,
                        'penyesuaian' => $totalPenyesuaian,
                        'proyeksi' => $proyeksiPerubahan,
                        'is_3_segmen' => $is3Segmen
                    ];

                    // Tambahkan ke total jika 3 segmen
                    if ($is3Segmen) {
                        $opdData['total_anggaran'] += $totalPagu;
                        $opdData['total_realisasi'] += $totalRealisasi;
                        $opdData['total_penyesuaian'] += $totalPenyesuaian;
                        $opdData['total_proyeksi'] += $proyeksiPerubahan;
                    }
                }

                $rekapitulasiData->push($opdData);
            }
        }

        return view('simulasi-perubahan.rekapitulasi-struktur-opd-modal', [
            'tahapans' => $tahapans,
            'tahapanId' => $tahapanId,
            'kodeRekenings' => $kodeRekenings,
            'rekapitulasiData' => $rekapitulasiData,
        ]);
    }

    public function exportExcelModal(Request $request)
    {
        $tahapanId = $request->input('tahapan_id');
        
        if (!$tahapanId) {
            return redirect()->back()->with('error', 'Silakan pilih tahapan terlebih dahulu.');
        }

        // Ambil data yang sama seperti di method rekapitulasiStrukturOpdModal
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'")
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'");
        })
        ->orderBy('kode_rekening')
        ->get();

        $opds = DataAnggaran::select('kode_skpd', 'nama_skpd')
            ->distinct()
            ->orderBy('kode_skpd')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        $rekapitulasiData = collect();
        
        // Ambil semua data simulasi penyesuaian anggaran
        $simulasiPenyesuaian = SimulasiPenyesuaianAnggaran::all();

        // Ambil data realisasi untuk semua OPD
        $realisasiMap = [];
        $realisasiRows = Realisasi::all();
        foreach ($realisasiRows as $row) {
            $realisasiMap[$row->kode_opd][$row->kode_rekening] = $row->realisasi;
        }

        // Proses data untuk setiap OPD
        foreach ($opds as $opd) {
            $opdData = [
                'kode_skpd' => $opd->kode_skpd,
                'nama_skpd' => $opd->nama_skpd,
                'total_anggaran' => 0,
                'total_realisasi' => 0,
                'total_penyesuaian' => 0,
                'total_proyeksi' => 0,
                'struktur_belanja' => []
            ];

            // Ambil data anggaran per kode rekening untuk OPD ini
            $anggaranPerRekening = DataAnggaran::select('kode_rekening', 'nama_rekening')
                ->selectRaw('SUM(pagu) as total_pagu')
                ->where('tahapan_id', $tahapanId)
                ->where('kode_skpd', $opd->kode_skpd)
                ->groupBy('kode_rekening', 'nama_rekening')
                ->limit(200) // Limit untuk mencegah timeout
            ->get();

            // Proses setiap kode rekening struktur
            foreach ($kodeRekenings as $kr) {
                $is3Segmen = count(explode('.', $kr->kode_rekening)) === 3;
                
                // Hitung total pagu untuk kode rekening ini
                $totalPagu = $anggaranPerRekening->where(function($item) use ($kr) {
                    return str_starts_with($item->kode_rekening, $kr->kode_rekening);
                })->sum('total_pagu');

                // Hitung total realisasi untuk kode rekening ini
                $totalRealisasi = 0;
                $realisasiOpd = $realisasiMap[$opd->kode_skpd] ?? [];
                foreach ($realisasiOpd as $kodeRek => $realisasi) {
                    if (str_starts_with($kodeRek, $kr->kode_rekening)) {
                        $totalRealisasi += $realisasi;
                    }
                }

                // Hitung penyesuaian untuk kode rekening ini
                $totalPenyesuaian = 0;
                $penyesuaianOpd = $simulasiPenyesuaian->where('kode_opd', $opd->kode_skpd);
                foreach ($penyesuaianOpd as $adj) {
                    if (str_starts_with($adj->kode_rekening, $kr->kode_rekening)) {
                        if ($adj->operasi == '+') {
                            $totalPenyesuaian += $adj->nilai;
                        } elseif ($adj->operasi == '-') {
                            $totalPenyesuaian -= $adj->nilai;
                        }
                    }
                }

                // Hitung proyeksi perubahan
                $anggaranRealisasi = $totalPagu - $totalRealisasi;
                $proyeksiPerubahan = $anggaranRealisasi + $totalPenyesuaian;

                // Tambahkan ke struktur belanja
                $opdData['struktur_belanja'][$kr->kode_rekening] = [
                    'nama_rekening' => $kr->uraian,
                    'anggaran' => $totalPagu,
                    'realisasi' => $totalRealisasi,
                    'anggaran_realisasi' => $anggaranRealisasi,
                    'penyesuaian' => $totalPenyesuaian,
                    'proyeksi' => $proyeksiPerubahan,
                    'is_3_segmen' => $is3Segmen
                ];

                // Tambahkan ke total jika 3 segmen
                if ($is3Segmen) {
                    $opdData['total_anggaran'] += $totalPagu;
                    $opdData['total_realisasi'] += $totalRealisasi;
                    $opdData['total_penyesuaian'] += $totalPenyesuaian;
                    $opdData['total_proyeksi'] += $proyeksiPerubahan;
                }
            }

            $rekapitulasiData->push($opdData);
        }

        $tahapanName = Tahapan::find($tahapanId)->name ?? 'Tahapan ' . $tahapanId;
        $filename = 'rekapitulasi-struktur-opd-modal-' . $tahapanName . '-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new RekapitulasiStrukturOpdModalExport($rekapitulasiData, $kodeRekenings, $tahapanName), $filename);
    }

    public function strukturBelanjaApbd(Request $request)
    {
        $tahapans = Tahapan::orderBy('id')->get();

        // Ambil semua kode rekening yang diawali angka 5 dan hanya 2 atau 3 segmen (level 1 dan 2)
        $kodeRekenings = KodeRekening::where(function($q) {
            $q->whereRaw("kode_rekening REGEXP '^5\\.[0-9]+$'") // 2 segmen, contoh: 5.1 (level 1)
              ->orWhereRaw("kode_rekening REGEXP '^5\\.[0-9]+\\.[0-9]{2}$'"); // 3 segmen, contoh: 5.1.01 (level 2)
        })
        ->orderBy('kode_rekening')
        ->get();

        $strukturData = collect();
        
        // Data anggaran diambil langsung dari tabel data_anggarans tanpa simulasi perubahan
        
        // Ambil semua data realisasi
        $realisasiData = Realisasi::all();

        // ===== TAMBAHAN DATA PENDAPATAN =====
        // Ambil semua kode akun pendapatan yang unik dari tabel pendapatans dengan join ke kode_rekenings
        $pendapatanKodeAkuns = \App\Models\Pendapatan::select('pendapatans.kode_akun', 'kode_rekenings.uraian as nama_akun')
            ->join('kode_rekenings', 'pendapatans.kode_akun', '=', 'kode_rekenings.kode_rekening')
            ->distinct()
            ->orderBy('pendapatans.kode_akun')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        // Buat struktur hierarkis pendapatan seperti belanja (level 1 dan level 2)
        $pendapatanLevel1 = collect();
        $pendapatanLevel2 = collect();

        foreach ($pendapatanKodeAkuns as $pendapatan) {
            $segments = explode('.', $pendapatan->kode_akun);
            
            if (count($segments) >= 2) {
                // Level 1: 2 segmen (contoh: 4.1)
                $level1Code = $segments[0] . '.' . $segments[1];
                if (!$pendapatanLevel1->contains('kode_akun', $level1Code)) {
                    // Cari uraian untuk level 1 dari kode_rekenings
                    $level1Uraian = \App\Models\KodeRekening::where('kode_rekening', $level1Code)->first();
                    $pendapatanLevel1->push([
                        'kode_akun' => $level1Code,
                        'nama_akun' => $level1Uraian ? $level1Uraian->uraian : 'Pendapatan ' . $level1Code
                    ]);
                }
                
                // Level 2: 3 segmen (contoh: 4.1.01)
                if (count($segments) >= 3) {
                    $level2Code = $segments[0] . '.' . $segments[1] . '.' . $segments[2];
                    if (!$pendapatanLevel2->contains('kode_akun', $level2Code)) {
                        // Cari uraian untuk level 2 dari kode_rekenings
                        $level2Uraian = \App\Models\KodeRekening::where('kode_rekening', $level2Code)->first();
                        $pendapatanLevel2->push([
                            'kode_akun' => $level2Code,
                            'nama_akun' => $level2Uraian ? $level2Uraian->uraian : $pendapatan->nama_akun
                        ]);
                    }
                }
            }
        }

        // Tambahkan Level 1 pendapatan
        foreach ($pendapatanLevel1 as $pendapatan) {
            $paguPerTahapan = [];
            foreach ($tahapans as $tahapan) {
                $totalPagu = \App\Models\Pendapatan::where('tahapan_id', $tahapan->id)
                    ->where('kode_akun', 'like', $pendapatan['kode_akun'] . '%')
                    ->sum('pagu');
                
                $paguPerTahapan[$tahapan->id] = $totalPagu;
            }

            $strukturData->push([
                'kode_rekening' => $pendapatan['kode_akun'],
                'nama_rekening' => $pendapatan['nama_akun'],
                'pagu_per_tahapan' => $paguPerTahapan,
                'realisasi' => 0,
                'penyesuaian' => 0,
                'is_2_segmen' => true,
                'is_3_segmen' => false,
                'level' => 1,
                'is_pendapatan' => true
            ]);
        }

        // Tambahkan Level 2 pendapatan
        foreach ($pendapatanLevel2 as $pendapatan) {
            $paguPerTahapan = [];
            foreach ($tahapans as $tahapan) {
                $totalPagu = \App\Models\Pendapatan::where('tahapan_id', $tahapan->id)
                    ->where('kode_akun', 'like', $pendapatan['kode_akun'] . '%')
                    ->sum('pagu');
                
                $paguPerTahapan[$tahapan->id] = $totalPagu;
            }

            $strukturData->push([
                'kode_rekening' => $pendapatan['kode_akun'],
                'nama_rekening' => $pendapatan['nama_akun'],
                'pagu_per_tahapan' => $paguPerTahapan,
                'realisasi' => 0,
                'penyesuaian' => 0,
                'is_2_segmen' => false,
                'is_3_segmen' => true,
                'level' => 2,
                'is_pendapatan' => true
            ]);
        }
        // ===== END TAMBAHAN DATA PENDAPATAN =====

        // Loop untuk setiap kode rekening belanja
        foreach ($kodeRekenings as $kr) {
            $is2Segmen = count(explode('.', $kr->kode_rekening)) === 2;
            $is3Segmen = count(explode('.', $kr->kode_rekening)) === 3;

            // Hitung total pagu untuk setiap tahapan
            $paguPerTahapan = [];
            foreach ($tahapans as $tahapan) {
                $totalPagu = DataAnggaran::where('tahapan_id', $tahapan->id)
                    ->where('kode_rekening', 'like', $kr->kode_rekening . '%')
                    ->sum('pagu');
                
                $paguPerTahapan[$tahapan->id] = $totalPagu;
            }

            // Hitung total realisasi
            $totalRealisasi = $realisasiData
                ->where('kode_rekening', $kr->kode_rekening)
                ->sum('realisasi');

            // Tidak ada penyesuaian - data diambil langsung dari data_anggarans
            $totalPenyesuaian = 0;

            // Tambahkan ke struktur data
            $strukturData->push([
                'kode_rekening' => $kr->kode_rekening,
                'nama_rekening' => $kr->uraian,
                'pagu_per_tahapan' => $paguPerTahapan,
                'realisasi' => $totalRealisasi,
                'penyesuaian' => $totalPenyesuaian,
                'is_2_segmen' => $is2Segmen,
                'is_3_segmen' => $is3Segmen,
                'level' => $is2Segmen ? 1 : 2,
                'is_pendapatan' => false // Flag untuk belanja
            ]);
        }

        // ===== TAMBAHAN DATA PEMBIAYAAN =====
        // Ambil semua kode akun pembiayaan yang unik dari tabel pembiayaans dengan join ke kode_rekenings
        $pembiayaanKodeAkuns = \App\Models\Pembiayaan::select('pembiayaans.kode_akun', 'kode_rekenings.uraian as nama_akun')
            ->join('kode_rekenings', 'pembiayaans.kode_akun', '=', 'kode_rekenings.kode_rekening')
            ->distinct()
            ->orderBy('pembiayaans.kode_akun')
            ->limit(200) // Limit untuk mencegah timeout
            ->get();

        // Buat struktur hierarkis pembiayaan seperti belanja (level 1 dan level 2)
        $pembiayaanLevel1 = collect();
        $pembiayaanLevel2 = collect();

        foreach ($pembiayaanKodeAkuns as $pembiayaan) {
            $segments = explode('.', $pembiayaan->kode_akun);
            
            if (count($segments) >= 2) {
                // Level 1: 2 segmen (contoh: 6.1)
                $level1Code = $segments[0] . '.' . $segments[1];
                if (!$pembiayaanLevel1->contains('kode_akun', $level1Code)) {
                    // Cari uraian untuk level 1 dari kode_rekenings
                    $level1Uraian = \App\Models\KodeRekening::where('kode_rekening', $level1Code)->first();
                    $pembiayaanLevel1->push([
                        'kode_akun' => $level1Code,
                        'nama_akun' => $level1Uraian ? $level1Uraian->uraian : 'Pembiayaan ' . $level1Code
                    ]);
                }
                
                // Level 2: 3 segmen (contoh: 6.1.01)
                if (count($segments) >= 3) {
                    $level2Code = $segments[0] . '.' . $segments[1] . '.' . $segments[2];
                    if (!$pembiayaanLevel2->contains('kode_akun', $level2Code)) {
                        // Cari uraian untuk level 2 dari kode_rekenings
                        $level2Uraian = \App\Models\KodeRekening::where('kode_rekening', $level2Code)->first();
                        $pembiayaanLevel2->push([
                            'kode_akun' => $level2Code,
                            'nama_akun' => $level2Uraian ? $level2Uraian->uraian : $pembiayaan->nama_akun
                        ]);
                    }
                }
            }
        }

        // Tambahkan Level 1 pembiayaan
        foreach ($pembiayaanLevel1 as $pembiayaan) {
            $paguPerTahapan = [];
            foreach ($tahapans as $tahapan) {
                $totalPagu = \App\Models\Pembiayaan::where('tahapan_id', $tahapan->id)
                    ->where('kode_akun', 'like', $pembiayaan['kode_akun'] . '%')
                    ->sum('pagu');
                
                $paguPerTahapan[$tahapan->id] = $totalPagu;
            }

            $strukturData->push([
                'kode_rekening' => $pembiayaan['kode_akun'],
                'nama_rekening' => $pembiayaan['nama_akun'],
                'pagu_per_tahapan' => $paguPerTahapan,
                'realisasi' => 0,
                'penyesuaian' => 0,
                'is_2_segmen' => true,
                'is_3_segmen' => false,
                'level' => 1,
                'is_pembiayaan' => true,
                'is_penerimaan_pembiayaan' => str_starts_with($pembiayaan['kode_akun'], '6.1'), // Penerimaan pembiayaan
                'is_pengeluaran_pembiayaan' => str_starts_with($pembiayaan['kode_akun'], '6.2') // Pengeluaran pembiayaan
            ]);
        }

        // Tambahkan Level 2 pembiayaan
        foreach ($pembiayaanLevel2 as $pembiayaan) {
            $paguPerTahapan = [];
            foreach ($tahapans as $tahapan) {
                $totalPagu = \App\Models\Pembiayaan::where('tahapan_id', $tahapan->id)
                    ->where('kode_akun', 'like', $pembiayaan['kode_akun'] . '%')
                    ->sum('pagu');
                
                $paguPerTahapan[$tahapan->id] = $totalPagu;
            }

            $strukturData->push([
                'kode_rekening' => $pembiayaan['kode_akun'],
                'nama_rekening' => $pembiayaan['nama_akun'],
                'pagu_per_tahapan' => $paguPerTahapan,
                'realisasi' => 0,
                'penyesuaian' => 0,
                'is_2_segmen' => false,
                'is_3_segmen' => true,
                'level' => 2,
                'is_pembiayaan' => true,
                'is_penerimaan_pembiayaan' => str_starts_with($pembiayaan['kode_akun'], '6.1'), // Penerimaan pembiayaan
                'is_pengeluaran_pembiayaan' => str_starts_with($pembiayaan['kode_akun'], '6.2') // Pengeluaran pembiayaan
            ]);
        }
        // ===== END TAMBAHAN DATA PEMBIAYAAN =====

        // Urutkan data: pendapatan dulu, kemudian belanja, kemudian pembiayaan
        $strukturData = $strukturData->sortBy(function($item) {
            if (isset($item['is_pendapatan']) && $item['is_pendapatan']) {
                return '0-' . $item['kode_rekening']; // Pendapatan di atas
            } elseif (isset($item['is_pembiayaan']) && $item['is_pembiayaan']) {
                return '2-' . $item['kode_rekening']; // Pembiayaan di bawah
            } else {
                return '1-' . $item['kode_rekening']; // Belanja di tengah
            }
        });

        return view('simulasi-perubahan.struktur-belanja-apbd', [
            'tahapans' => $tahapans,
            'kodeRekenings' => $kodeRekenings,
            'strukturData' => $strukturData,
        ]);
    }

    public function exportExcelStrukturApbd(Request $request)
    {
        // Set memory limit and execution time for large exports
        ini_set('memory_limit', '1024M');
        set_time_limit(600); // 10 minutes
        
        try {
            $tahapans = Tahapan::orderBy('id')->limit(3)->get(); // Limit tahapan untuk testing
            
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
                ],
                [
                    'kode_rekening' => '6.1.01',
                    'nama_rekening' => 'Penerimaan Pembiayaan',
                    'level' => 2,
                    'pagu_per_tahapan' => [1 => 500000, 2 => 1000000, 3 => 1500000],
                    'is_pembiayaan' => true,
                    'is_penerimaan_pembiayaan' => true
                ]
            ]);

            $filename = 'struktur-pendapatan-belanja-apbd-semua-tahapan-' . date('Y-m-d') . '.xlsx';

            return Excel::download(new StrukturBelanjaApbdMinimalExport($strukturData, $tahapans), $filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

}
