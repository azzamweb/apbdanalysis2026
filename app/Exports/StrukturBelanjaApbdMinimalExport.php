<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StrukturBelanjaApbdMinimalExport implements FromArray, WithHeadings
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
        $exportData = [];
        $rowNumber = 1;
        
        // Ambil data pendapatan
        $pendapatanData = $this->strukturData->where('is_pendapatan', true);
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
        
        // Ambil data belanja
        $belanjaData = $this->strukturData->where('is_pendapatan', '!=', true)->where('is_pembiayaan', '!=', true);
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
        
        // Ambil data pembiayaan
        $pembiayaanData = $this->strukturData->where('is_pembiayaan', true);
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
}
