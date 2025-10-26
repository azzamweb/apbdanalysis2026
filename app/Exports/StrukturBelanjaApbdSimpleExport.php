<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class StrukturBelanjaApbdSimpleExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $strukturData;
    protected $tahapans;

    public function __construct($strukturData, $tahapans)
    {
        $this->strukturData = $strukturData;
        $this->tahapans = $tahapans;
    }

    public function array(): array
    {
        $data = $this->strukturData;
        
        // Pisahkan data berdasarkan jenis
        $pendapatanData = $data->where('is_pendapatan', true);
        $belanjaData = $data->where('is_pendapatan', '!=', true)->where('is_pembiayaan', '!=', true);
        $pembiayaanData = $data->where('is_pembiayaan', true);
        
        $exportData = [];
        $rowNumber = 1;
        
        // Tambahkan data pendapatan
        foreach ($pendapatanData as $item) {
            $row = [
                $rowNumber++,
                $item['kode_rekening'],
                $item['nama_rekening'],
                $item['level']
            ];
            
            foreach ($this->tahapans as $tahapan) {
                $row[] = number_format($item['pagu_per_tahapan'][$tahapan->id] ?? 0, 2, ',', '.');
            }
            
            $exportData[] = $row;
        }
        
        // Tambahkan total pendapatan
        if ($pendapatanData->count() > 0) {
            $row = ['', 'TOTAL PENDAPATAN (Level 2)', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $total = $pendapatanData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $row[] = number_format($total, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        // Tambahkan data belanja
        foreach ($belanjaData as $item) {
            $row = [
                $rowNumber++,
                $item['kode_rekening'],
                $item['nama_rekening'],
                $item['level']
            ];
            
            foreach ($this->tahapans as $tahapan) {
                $row[] = number_format($item['pagu_per_tahapan'][$tahapan->id] ?? 0, 2, ',', '.');
            }
            
            $exportData[] = $row;
        }
        
        // Tambahkan total belanja
        if ($belanjaData->count() > 0) {
            $row = ['', 'TOTAL BELANJA (Level 2)', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $total = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $row[] = number_format($total, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        // Tambahkan surplus/defisit
        if ($pendapatanData->count() > 0 && $belanjaData->count() > 0) {
            $row = ['', 'SURPLUS / DEFISIT', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $totalPendapatan = $pendapatanData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalBelanja = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $row[] = number_format($totalPendapatan - $totalBelanja, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        // Tambahkan data pembiayaan
        foreach ($pembiayaanData as $item) {
            $row = [
                $rowNumber++,
                $item['kode_rekening'],
                $item['nama_rekening'],
                $item['level']
            ];
            
            foreach ($this->tahapans as $tahapan) {
                $row[] = number_format($item['pagu_per_tahapan'][$tahapan->id] ?? 0, 2, ',', '.');
            }
            
            $exportData[] = $row;
        }
        
        // Tambahkan pembiayaan netto
        if ($pembiayaanData->count() > 0) {
            $row = ['', 'PEMBIAYAAN NETTO', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $totalPenerimaanPembiayaan = $pembiayaanData->where('level', 2)->where('is_penerimaan_pembiayaan', true)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalPengeluaranPembiayaan = $pembiayaanData->where('level', 2)->where('is_pengeluaran_pembiayaan', true)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $row[] = number_format($totalPenerimaanPembiayaan - $totalPengeluaranPembiayaan, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        // Tambahkan sisa lebih pembiayaan anggaran daerah tahun berkenaan
        if ($pendapatanData->count() > 0 && $belanjaData->count() > 0 && $pembiayaanData->count() > 0) {
            $row = ['', 'SISA LEBIH PEMBIAYAAN ANGGARAN DAERAH TAHUN BERKENAAN', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $totalPendapatan = $pendapatanData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalBelanja = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalPenerimaanPembiayaan = $pembiayaanData->where('level', 2)->where('is_penerimaan_pembiayaan', true)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalPengeluaranPembiayaan = $pembiayaanData->where('level', 2)->where('is_pengeluaran_pembiayaan', true)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $pembiayaanNetto = $totalPenerimaanPembiayaan - $totalPengeluaranPembiayaan;
                $row[] = number_format($totalPendapatan - $totalBelanja + $pembiayaanNetto, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        // Tambahkan total APBD
        if ($belanjaData->count() > 0 && $pembiayaanData->count() > 0) {
            $row = ['', 'TOTAL APBD', '', ''];
            foreach ($this->tahapans as $tahapan) {
                $totalBelanja = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $totalPengeluaranPembiayaan = $pembiayaanData->where('level', 2)->where('is_pengeluaran_pembiayaan', true)->sum(function($item) use ($tahapan) {
                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                });
                $row[] = number_format($totalBelanja + $totalPengeluaranPembiayaan, 2, ',', '.');
            }
            $exportData[] = $row;
        }
        
        return $exportData;
    }

    public function headings(): array
    {
        $headings = [
            'No',
            'Kode Rekening',
            'Nama Rekening',
            'Level'
        ];
        
        foreach ($this->tahapans as $tahapan) {
            $headings[] = $tahapan->name;
        }
        
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '4472C4',
                    ],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Data rows
            'A:Z' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Number columns (right align) - mulai dari kolom E
            'E:Z' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 20,  // Kode Rekening
            'C' => 50,  // Nama Rekening
            'D' => 10,  // Level
            'E' => 20,  // Tahapan 1
            'F' => 20,  // Tahapan 2
            'G' => 20,  // Tahapan 3
            'H' => 20,  // Tahapan 4
            'I' => 20,  // Tahapan 5
            'J' => 20,  // Tahapan 6
        ];
    }

    public function title(): string
    {
        return 'Struktur Pendapatan Belanja Pembiayaan APBD';
    }
}
