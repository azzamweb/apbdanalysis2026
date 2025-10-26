<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CalculatorAnggaranExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $tahapanName;
    protected $opdName;

    public function __construct(array $data, $tahapanName, $opdName)
    {
        $this->data = $data;
        $this->tahapanName = $tahapanName;
        $this->opdName = $opdName;
    }

    public function array(): array
    {
        $exportData = [];
        
        foreach ($this->data as $item) {
            $exportData[] = [
                $item['kode_sub_kegiatan'] ?? '-',
                $item['nama_sub_kegiatan'] ?? '-',
                $item['kode_rekening'] ?? '-',
                $item['nama_rekening'] ?? '-',
                $item['kode_standar_harga'] ?? '-',
                $item['nama_standar_harga'] ?? '-',
                $item['anggaran'] ?? 0,
            ];
        }
        
        // Tambahkan total di baris terakhir
        $total = array_sum(array_column($this->data, 'anggaran'));
        $exportData[] = [
            '', '', '', '', '', 'TOTAL',
            $total
        ];
        
        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Kode Sub Kegiatan',
            'Nama Sub Kegiatan',
            'Kode Rekening',
            'Nama Rekening',
            'Kode Standar Harga',
            'Uraian',
            'Pagu'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ]
            ],
            // Total row
            count($this->data) + 2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF3E0']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Kode Sub Kegiatan
            'B' => 40, // Nama Sub Kegiatan
            'C' => 15, // Kode Rekening
            'D' => 30, // Nama Rekening
            'E' => 15, // Kode Standar Harga
            'F' => 40, // Uraian
            'G' => 15, // Pagu
        ];
    }
}
