<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TestSimpleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [1, '4.1.01', 'Pendapatan Pajak Daerah', 2, '1000000', '2000000', '3000000'],
            [2, '4.1.02', 'Pendapatan Retribusi Daerah', 2, '500000', '1000000', '1500000'],
            [3, '5.1.01', 'Belanja Operasi', 2, '2000000', '4000000', '6000000'],
            [4, '5.1.02', 'Belanja Modal', 2, '1000000', '2000000', '3000000'],
            [5, '6.1.01', 'Penerimaan Pembiayaan', 2, '500000', '1000000', '1500000'],
            [6, '6.2.01', 'Pengeluaran Pembiayaan', 2, '300000', '600000', '900000'],
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Rekening',
            'Nama Rekening',
            'Level',
            'Tahapan 1',
            'Tahapan 2',
            'Tahapan 3'
        ];
    }
}
