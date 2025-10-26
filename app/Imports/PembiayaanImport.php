<?php

namespace App\Imports;

use App\Models\Pembiayaan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class PembiayaanImport implements ToModel, WithHeadingRow
{
    use Importable;

    protected $tahapan_id;
    protected $tanggal_upload;

    public function __construct($tahapan_id, $tanggal_upload)
    {
        $this->tahapan_id = $tahapan_id;
        $this->tanggal_upload = $tanggal_upload;
    }

    public function model(array $row)
    {
        // Cek jika kode_akun kosong, maka abaikan baris ini
        if (empty($row['kode_akun']) || empty($row['nama_akun'])) {
            return null;
        }

        // Cek apakah data sudah ada berdasarkan unique constraint
        $existingPembiayaan = Pembiayaan::where('tahapan_id', $this->tahapan_id)
            ->where('tahun', $row['tahun'] ?? null)
            ->where('kode_akun', $row['kode_akun'] ?? null)
            ->first();

        if ($existingPembiayaan) {
            // Update data yang sudah ada
            $existingPembiayaan->update([
                'nama_akun' => $row['nama_akun'] ?? null,
                'kode_opd' => $row['kode_opd'] ?? null,
                'nama_opd' => $row['nama_opd'] ?? null,
                'uraian' => $row['uraian'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
                'pagu' => $row['pagu'] ?? 0,
                'tanggal_upload' => $this->tanggal_upload,
            ]);
            return null; // Return null karena sudah diupdate
        } else {
            // Insert data baru
            return new Pembiayaan([
                'tahun' => $row['tahun'] ?? null,
                'kode_akun' => $row['kode_akun'] ?? null,
                'nama_akun' => $row['nama_akun'] ?? null,
                'kode_opd' => $row['kode_opd'] ?? null,
                'nama_opd' => $row['nama_opd'] ?? null,
                'uraian' => $row['uraian'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
                'pagu' => $row['pagu'] ?? 0, // Jika kosong, set 0
                'tahapan_id' => $this->tahapan_id,
                'tanggal_upload' => $this->tanggal_upload,
            ]);
        }
    }
}
