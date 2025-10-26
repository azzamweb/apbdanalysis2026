@extends('layouts.app')

@section('title', 'Struktur Belanja APBD')
@section('page-title', 'Struktur Belanja APBD')

@section('content')
<div class="card" data-aos="fade-up" data-aos-delay="300">
    <div class="card-header">
        <h4>Struktur Belanja APBD</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="btn-group">
                <button class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
                <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <a href="{{ route('simulasi.struktur-belanja-apbd.export-excel') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
        
        <div id="print-area">
            <div class="mb-2">
                <strong>Struktur Pendapatan & Belanja APBD - Semua Tahapan</strong>
            </div>
                
                <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover" style="table-layout: fixed; width: 100%;">
                        <thead class="table-primary">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th style="width: 120px;">Kode Rekening</th>
                                <th style="width: 450px; word-wrap: break-word;">Nama Rekening</th>
                                <th style="width: 80px;" class="text-center">Level</th>
                                @foreach($tahapans as $tahapan)
                                    <th style="width: 200px;" class="text-center" title="{{ $tahapan->name }}">
                                        <div style="word-wrap: break-word; white-space: normal; line-height: 1.2;">
                                            {{ $tahapan->name }}
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $pendapatanData = $strukturData->where('is_pendapatan', true);
                                $belanjaData = $strukturData->where('is_pendapatan', '!=', true)->where('is_pembiayaan', '!=', true);
                                $pembiayaanData = $strukturData->where('is_pembiayaan', true);
                                $rowNumber = 1;
                            @endphp
                            
                            {{-- Data Pendapatan --}}
                            @foreach($pendapatanData as $item)
                                <tr class="{{ 
                                    $item['is_2_segmen'] ? 'table-success fw-bold' : 
                                    ($item['is_3_segmen'] ? 'table-light' : '') 
                                }}">
                                    <td class="text-center">{{ $rowNumber++ }}</td>
                                    <td class="{{ $item['is_2_segmen'] ? 'fw-bold' : '' }}">
                                        {{ $item['kode_rekening'] }}
                                    </td>
                                    <td class="{{ $item['is_2_segmen'] ? 'fw-bold' : '' }}" style="padding-left: {{ $item['level'] * 20 }}px; white-space: normal;" title="{{ $item['nama_rekening'] }}">
                                        {{ $item['nama_rekening'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ 
                                            $item['is_2_segmen'] ? 'bg-success' :
                                            ($item['is_3_segmen'] ? 'bg-info' : 'bg-secondary') 
                                        }}">
                                            {{ $item['level'] }}
                                        </span>
                                    </td>
                                    @foreach($tahapans as $tahapan)
                                        <td class="text-end">
                                            {{ isset($item['pagu_per_tahapan'][$tahapan->id]) && $item['pagu_per_tahapan'][$tahapan->id] ? number_format($item['pagu_per_tahapan'][$tahapan->id], 2, ',', '.') : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            
                            {{-- Total Pendapatan --}}
                            @if($pendapatanData->count() > 0)
                                <tr class="table-success fw-bold">
                                    <th colspan="4" class="text-center">TOTAL PENDAPATAN (Level 2)</th>
                                    @foreach($tahapans as $tahapan)
                                        <th class="text-end">
                                            {{ number_format($pendapatanData->where('level', 2)->sum(function($item) use ($tahapan) {
                                                return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                            }), 2, ',', '.') }}
                                        </th>
                                    @endforeach
                                </tr>
                            @endif
                            
                            {{-- Data Belanja --}}
                            @foreach($belanjaData as $item)
                                <tr class="{{ 
                                    isset($item['is_pendapatan']) && $item['is_pendapatan'] ? 'table-success fw-bold' : 
                                    ($item['is_2_segmen'] ? 'table-warning fw-bold' : 
                                    ($item['is_3_segmen'] ? 'table-light' : '')) 
                                }}">
                                    <td class="text-center">{{ $rowNumber++ }}</td>
                                    <td class="{{ ($item['is_2_segmen'] || (isset($item['is_pendapatan']) && $item['is_pendapatan'])) ? 'fw-bold' : '' }}">
                                        {{ $item['kode_rekening'] }}
                                    </td>
                                    <td class="{{ ($item['is_2_segmen'] || (isset($item['is_pendapatan']) && $item['is_pendapatan'])) ? 'fw-bold' : '' }}" style="padding-left: {{ $item['level'] * 20 }}px; white-space: normal;" title="{{ $item['nama_rekening'] }}">
                                        {{ $item['nama_rekening'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ 
                                            isset($item['is_pendapatan']) && $item['is_pendapatan'] ? 'bg-success' :
                                            ($item['is_2_segmen'] ? 'bg-warning' : 
                                            ($item['is_3_segmen'] ? 'bg-info' : 'bg-secondary')) 
                                        }}">
                                            {{ $item['level'] }}
                                        </span>
                                    </td>
                                    @foreach($tahapans as $tahapan)
                                        <td class="text-end">
                                            {{ isset($item['pagu_per_tahapan'][$tahapan->id]) && $item['pagu_per_tahapan'][$tahapan->id] ? number_format($item['pagu_per_tahapan'][$tahapan->id], 2, ',', '.') : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            
                            {{-- Total Belanja --}}
                            @if($belanjaData->count() > 0)
                                <tr class="table-warning fw-bold">
                                    <th colspan="4" class="text-center">TOTAL BELANJA (Level 2)</th>
                                    @foreach($tahapans as $tahapan)
                                        <th class="text-end">
                                            {{ number_format($belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                                                return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                            }), 2, ',', '.') }}
                                        </th>
                                    @endforeach
                                </tr>
                            @endif
                            
                            {{-- Surplus/Defisit --}}
                            <tr class="table-secondary fw-bold">
                                <th colspan="4" class="text-center">SURPLUS / DEFISIT</th>
                                @foreach($tahapans as $tahapan)
                                    <th class="text-end">
                                        @php
                                            $totalPendapatan = $pendapatanData->where('level', 2)->sum(function($item) use ($tahapan) {
                                                return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                            });
                                            $totalBelanja = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                                                return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                            });
                                            $surplusDefisit = $totalPendapatan - $totalBelanja;
                                        @endphp
                                        <span class="{{ $surplusDefisit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($surplusDefisit, 2, ',', '.') }}
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                            
                            {{-- Data Pembiayaan --}}
                            @foreach($pembiayaanData as $item)
                                <tr class="{{ 
                                    $item['is_2_segmen'] ? 'table-info fw-bold' : 
                                    ($item['is_3_segmen'] ? 'table-light' : '') 
                                }}">
                                    <td class="text-center">{{ $rowNumber++ }}</td>
                                    <td class="{{ $item['is_2_segmen'] ? 'fw-bold' : '' }}">
                                        {{ $item['kode_rekening'] }}
                                    </td>
                                    <td class="{{ $item['is_2_segmen'] ? 'fw-bold' : '' }}" style="padding-left: {{ $item['level'] * 20 }}px; white-space: normal;" title="{{ $item['nama_rekening'] }}">
                                        {{ $item['nama_rekening'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ 
                                            $item['is_2_segmen'] ? 'bg-info' :
                                            ($item['is_3_segmen'] ? 'bg-secondary' : 'bg-secondary') 
                                        }}">
                                            {{ $item['level'] }}
                                        </span>
                                    </td>
                                    @foreach($tahapans as $tahapan)
                                        <td class="text-end">
                                            {{ isset($item['pagu_per_tahapan'][$tahapan->id]) && $item['pagu_per_tahapan'][$tahapan->id] ? number_format($item['pagu_per_tahapan'][$tahapan->id], 2, ',', '.') : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            
                            {{-- Pembiayaan Netto --}}
                            @if($pembiayaanData->count() > 0)
                                <tr class="table-info fw-bold">
                                    <th colspan="4" class="text-center">PEMBIAYAAN NETTO</th>
                                    @foreach($tahapans as $tahapan)
                                        <th class="text-end">
                                            @php
                                                $totalPenerimaanPembiayaan = $pembiayaanData->where('level', 2)->where('is_penerimaan_pembiayaan', true)->sum(function($item) use ($tahapan) {
                                                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                                });
                                                $totalPengeluaranPembiayaan = $pembiayaanData->where('level', 2)->where('is_pengeluaran_pembiayaan', true)->sum(function($item) use ($tahapan) {
                                                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                                });
                                                $pembiayaanNetto = $totalPenerimaanPembiayaan - $totalPengeluaranPembiayaan;
                                            @endphp
                                            <span class="{{ $pembiayaanNetto >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($pembiayaanNetto, 2, ',', '.') }}
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            @endif
                            
                            {{-- Sisa Lebih Pembiayaan Anggaran Daerah Tahun Berkenaan --}}
                            @if($pembiayaanData->count() > 0)
                                <tr class="table-primary fw-bold">
                                    <th colspan="4" class="text-center">SISA LEBIH PEMBIAYAAN ANGGARAN DAERAH TAHUN BERKENAAN</th>
                                    @foreach($tahapans as $tahapan)
                                        <th class="text-end">
                                            @php
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
                                                $sisaLebihPembiayaan = $totalPendapatan - $totalBelanja + $pembiayaanNetto;
                                            @endphp
                                            <span class="{{ $sisaLebihPembiayaan >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($sisaLebihPembiayaan, 2, ',', '.') }}
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            @endif
                            
                            {{-- Total APBD --}}
                            @if($belanjaData->count() > 0 && $pembiayaanData->count() > 0)
                                <tr class="table-dark fw-bold">
                                    <th colspan="4" class="text-center">TOTAL APBD</th>
                                    @foreach($tahapans as $tahapan)
                                        <th class="text-end">
                                            @php
                                                $totalBelanja = $belanjaData->where('level', 2)->sum(function($item) use ($tahapan) {
                                                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                                });
                                                $totalPengeluaranPembiayaan = $pembiayaanData->where('level', 2)->where('is_pengeluaran_pembiayaan', true)->sum(function($item) use ($tahapan) {
                                                    return $item['pagu_per_tahapan'][$tahapan->id] ?? 0;
                                                });
                                                $totalApbd = $totalBelanja + $totalPengeluaranPembiayaan;
                                            @endphp
                                            {{ number_format($totalApbd, 2, ',', '.') }}
                                        </th>
                                    @endforeach
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>

@push('styles')
<style>
    .table-sm th, .table-sm td {
        font-size: 10px !important;
        padding: 0.3rem !important;
    }

    /* Table styles for better PDF export */
    .table-responsive {
        margin-bottom: 1rem;
    }
    
    .table {
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    
    .table th,
    .table td {
        padding: 0.3rem !important;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }
    
    /* Force fixed column widths */
    .table {
        table-layout: fixed !important;
        width: 100% !important;
    }
    
    /* Ensure table cells can wrap text */
    .table td {
        white-space: normal !important;
    }
    
    /* Specific override for nama rekening column */
    .table td:nth-child(3) {
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    .table th:nth-child(1) { width: 40px !important; }
    .table th:nth-child(2) { width: 120px !important; }
    .table th:nth-child(3) { width: 450px !important; }
    .table th:nth-child(4) { width: 80px !important; }
    .table th:nth-child(n+5) { width: 200px !important; }
    
    /* Enhanced text wrapping for nama rekening column */
    .table td:nth-child(3) {
        max-width: 450px !important;
        width: 450px !important;
        line-height: 1.4;
        hyphens: auto !important;
        vertical-align: top !important;
        padding: 0.5rem !important;
        white-space: normal !important;
        word-wrap: break-word !important;
    }
    
    /* Keep other columns from wrapping except pagu columns */
    .table td:not(:nth-child(3)):not(:nth-child(n+5)) {
        white-space: nowrap;
    }
    
    /* Pagu columns (tahapan columns) - allow wrapping for long numbers */
    .table td:nth-child(n+5) {
        white-space: normal !important;
        word-wrap: break-word !important;
        text-align: right !important;
        padding: 0.4rem !important;
        font-size: 0.9rem !important;
        width: 200px !important;
        max-width: 200px !important;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        vertical-align: middle;
    }
    
    /* Tahapan header columns - allow wrapping */
    .table thead th:nth-child(n+5) {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        line-height: 1.2;
        max-width: 100px;
    }
    
    .table tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,.02);
    }
    
    .table tfoot th {
        background-color: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }

    /* Level styling */
    .table-warning {
        background-color: #fff3cd !important;
    }
    
    .table-light {
        background-color: #f8f9fa !important;
    }
    
    .table-success {
        background-color: #d1e7dd !important;
    }

    /* Print styles */
    @media print {
        body * {
            visibility: hidden !important;
        }
        #print-area, #print-area * {
            visibility: visible !important;
        }
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .table {
            page-break-inside: avoid;
        }
        .table-responsive {
            overflow: visible !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>

    // Fungsi untuk export ke PDF
    window.exportToPDF = async function() {
        try {
            // Tampilkan loading
            const loadingDiv = $('<div>')
                .addClass('position-fixed top-50 start-50 translate-middle')
                .css({
                    'z-index': '9999',
                    'background': 'rgba(255,255,255,0.8)',
                    'padding': '20px',
                    'border-radius': '5px',
                    'box-shadow': '0 0 10px rgba(0,0,0,0.1)'
                })
                .html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Mempersiapkan PDF...</div>');
            $('body').append(loadingDiv);

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape orientation
            const margin = 10;
            let firstTable = true;

            // Ambil semua tabel di halaman
            const tables = document.querySelectorAll('table');

            for (let i = 0; i < tables.length; i++) {
                const table = tables[i];

                // Ambil judul tabel dari <h5> terdekat di atas <table>
                let title = '';
                let prev = table.previousSibling;
                while (prev) {
                    if (prev.nodeType === 1 && prev.tagName && prev.tagName.toUpperCase() === 'H5') {
                        title = prev.innerText.trim();
                        break;
                    }
                    prev = prev.previousSibling;
                }
                if (!title) title = 'Tabel ' + (i + 1);

                // Ambil header dan data
                const headers = [];
                table.querySelectorAll('thead tr th').forEach(th => {
                    headers.push(th.innerText.trim());
                });

                const body = [];
                table.querySelectorAll('tbody tr').forEach(tr => {
                    const row = [];
                    tr.querySelectorAll('td').forEach(td => {
                        row.push(td.innerText.trim());
                    });
                    if (row.length) body.push(row);
                });

                // Ambil footer jika ada
                let foot = [];
                const tfoot = table.querySelector('tfoot');
                if (tfoot) {
                    tfoot.querySelectorAll('tr').forEach(tr => {
                        const row = [];
                        let colCount = headers.length;
                        let cells = tr.querySelectorAll('th,td');
                        for (let i = 0; i < colCount; i++) {
                            row[i] = cells[i] ? cells[i].innerText.trim() : '';
                        }
                        foot.push(row);
                    });
                }

                // Tambahkan judul tabel
                if (!firstTable) pdf.addPage();
                pdf.setFontSize(12);
                pdf.text(title, margin, 18);

                // Render tabel dengan autotable
                pdf.autoTable({
                    startY: 22,
                    head: [headers],
                    body: body,
                    foot: foot,
                    margin: { left: margin, right: margin },
                    styles: { fontSize: 6, cellPadding: 1 },
                    headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                    theme: 'grid',
                    showHead: 'everyPage',
                    showFoot: 'lastPage',
                    didDrawPage: function (data) {
                        if (data.pageNumber === 1) {
                            pdf.setFontSize(12);
                            pdf.text(title, margin, 18);
                        } else {
                            data.settings.margin.top = 16;
                        }
                    }
                });

                firstTable = false;
            }

            pdf.save('struktur-belanja-apbd.pdf');
            loadingDiv.remove();
        } catch (error) {
            alert('Terjadi kesalahan saat membuat PDF: ' + error.message);
            if (typeof loadingDiv !== 'undefined') loadingDiv.remove();
        }
    }
</script>
@endpush
@endsection
