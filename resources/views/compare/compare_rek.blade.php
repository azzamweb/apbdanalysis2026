@extends('layouts.app')

@section('title', 'Rekap Rekening Belanja Seluruh OPD')
@section('page-title', 'Rekap Rekening Belanja Seluruh OPD')

@section('content')

<style>
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        overflow: hidden;
    }

    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 10px;
    }

    th {
        background-color: #0056b3!important;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    .total-container {
        margin-top: 20px;
        font-size: 16px;
        font-weight: bold;
        text-align: right;
    }

    .dt-buttons {
        margin-bottom: 10px;
    }

    .dt-buttons .btn {
        margin-right: 5px;
    }

    /* Compact table styling */
    #rekapTable {
        font-size: 0.875rem; /* Smaller font */
    }
    
    #rekapTable th,
    #rekapTable td {
        padding: 0.25rem 0.5rem !important; /* Reduced padding */
        vertical-align: top !important;
    }
    
    #rekapTable th {
        padding: 0.5rem !important; /* Slightly more padding for headers */
    }
    
    /* Fixed width columns with wrap text */
    #rekapTable td:nth-child(3), /* Nama SKPD */
    #rekapTable th:nth-child(3) {
        width: 150px !important;
        max-width: 150px !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    #rekapTable td:nth-child(5), /* Nama Rekening */
    #rekapTable th:nth-child(5) {
        width: 180px !important;
        max-width: 180px !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    #rekapTable td:nth-child(6), /* Nama Standar Harga */
    #rekapTable th:nth-child(6) {
        width: 200px !important;
        max-width: 200px !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    /* Other columns */
    #rekapTable td:nth-child(1), /* No */
    #rekapTable th:nth-child(1) {
        width: 40px !important;
    }
    
    #rekapTable td:nth-child(2), /* Kode SKPD */
    #rekapTable th:nth-child(2) {
        width: 80px !important;
    }
    
    #rekapTable td:nth-child(4), /* Kode Rekening */
    #rekapTable th:nth-child(4) {
        width: 100px !important;
    }
    
    #rekapTable td:nth-child(7), /* Pagu */
    #rekapTable th:nth-child(7) {
        width: 120px !important;
    }
    
    /* Total rows */
    #rekapTable .table-warning td {
        padding: 0.35rem 0.5rem !important;
    }
</style>

<!-- DataTables CSS - Removed to avoid conflicts -->
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css"> -->

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Fallback CSS untuk Select2 jika CDN gagal -->
<style>
.select2-container {
    width: 100% !important;
}
.select2-selection {
    min-height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-selection--multiple {
    min-height: 38px;
}

/* Fallback styling jika Select2 tidak dimuat */
#kodeRekeningSelect.form-control {
    height: auto;
    min-height: 38px;
}

/* Debug styling */
.select2-loading {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
}

/* Custom styling untuk kode rekening reference */
.kode-rekening-item {
    transition: all 0.2s ease;
}
.kode-rekening-item:hover {
    background-color: #e7f3ff !important;
    padding-left: 0.5rem !important;
    border-left: 3px solid #0d6efd !important;
}
.kode-rekening-item:last-child {
    border-bottom: none !important;
}

/* Custom styling untuk exclude kode rekening reference */
.exclude-kode-rekening-item {
    transition: all 0.2s ease;
}
.exclude-kode-rekening-item:hover {
    background-color: #ffe7e7 !important;
    padding-left: 0.5rem !important;
    border-left: 3px solid #dc3545 !important;
}
.exclude-kode-rekening-item:last-child {
    border-bottom: none !important;
}

#kodeRekeningReference,
#excludeKodeRekeningReference {
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 400px;
    }
}
#kodeRekeningInput:focus,
#excludeKodeRekeningInput:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Custom styling untuk select multiple */
#kodeRekeningSelect[multiple] {
    min-height: 100px;
    max-height: 200px;
    overflow-y: auto;
}
</style>

<div class="card" data-aos="fade-up" data-aos-delay="800">
    <div class="card-header d-flex justify-content-between align-items-center">
        
        <div>
            <form method="GET" action="{{ route('compare-rek') }}">
                <!-- Filter Tahapan -->
                <div class="mb-2 row">
                    <div class="col-md-3">
                        <label class="form-label">Filter Tahapan:</label>
                    </div>
                    <div class="col-md-9">
                        <select name="tahapan_id" class="form-select form-select-sm">
                        @foreach($tahapans as $tahapan)
                            <option value="{{ $tahapan->id }}" {{ $tahapanId == $tahapan->id ? 'selected' : '' }}>
                                {{ $tahapan->name }}
                            </option>
                        @endforeach
                    </select>
                    </div>
                </div>
                
                <!-- Filter Kata Kunci -->
                <div class="mb-2 row">
                    <div class="col-md-3">
                        <label class="form-label">Kata Kunci:</label>
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="keyword" value="{{ $keyword }}" 
                           placeholder="Contoh: printer, atk, meubelier atau printer atk meubelier" 
                               class="form-control form-control-sm">
                        <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Pisahkan dengan koma atau spasi
                    </small>
                    </div>
                </div>
                
                <!-- Filter Kode Rekening -->
                <div class="mb-3 row">
                    <div class="col-md-3">
                        <label class="form-label">Kode Rekening:</label>
                    </div>
                    <div class="col-md-9">
                        <!-- Input Kode Rekening -->
                        <input type="text" name="kode_rekening" id="kodeRekeningInput" 
                               value="{{ is_array($kodeRekening) ? implode(', ', $kodeRekening) : $kodeRekening }}" 
                               class="form-control form-control-sm" 
                               placeholder="Contoh: 5.1.01, 5.1.02, 5.2.01">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Pisahkan dengan koma untuk beberapa kode rekening
                            @if(isset($kodeRekenings))
                                | <strong>{{ is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0 }}</strong> kode tersedia
                            @endif
                        </small>
                        
                        <!-- Toggle Reference Button -->
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-info" id="toggleReferenceBtn">
                                <i class="bi bi-list-ul"></i> Tampilkan Referensi Kode Rekening 
                                @if(isset($kodeRekenings))
                                    <span class="badge bg-secondary">{{ is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0 }}</span>
                                @endif
                            </button>
                        </div>
                        
                        <!-- Reference Container (Hidden by default) -->
                        <div id="kodeRekeningReference" class="mt-2" style="display: none;">
                            <!-- Search Reference -->
                            <div class="mb-2">
                                <input type="text" id="kodeRekeningSearch" class="form-control form-control-sm" 
                                       placeholder="Cari referensi kode rekening..." autocomplete="off">
                            </div>
                            
                            <!-- Reference List -->
                            <div id="kodeRekeningContainer" class="p-2 rounded border" style="max-height: 250px; overflow-y: auto; background-color: #f8f9fa;">
                                @php
                                    // Debug: Check kodeRekenings data
                                    $kodeRekeningsCount = 0;
                                    if (isset($kodeRekenings)) {
                                        if (is_countable($kodeRekenings)) {
                                            $kodeRekeningsCount = is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count();
                                        }
                                    }
                                    \Log::info('KodeRekenings in view:', [
                                        'exists' => isset($kodeRekenings),
                                        'type' => isset($kodeRekenings) ? gettype($kodeRekenings) : 'not_set',
                                        'count' => $kodeRekeningsCount
                                    ]);
                                @endphp
                                @if(isset($kodeRekenings) && $kodeRekeningsCount > 0)
                                    @foreach($kodeRekenings as $kode)
                                        @if(is_object($kode) && isset($kode->kode_rekening))
                                            <div class="py-1 kode-rekening-item border-bottom" 
                                                 data-kode="{{ $kode->kode_rekening }}" 
                                                 data-text="{{ strtolower($kode->kode_rekening . ' ' . $kode->uraian) }}"
                                                 style="cursor: pointer;">
                                                <small>
                                                    <strong class="text-primary">{{ $kode->kode_rekening }}</strong> - 
                                                    <span class="text-muted">{{ $kode->uraian }}</span>
                                                    <i class="bi bi-plus-circle float-end text-success"></i>
                                                </small>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="py-3 text-center">
                                        <small class="text-muted">Tidak ada data kode rekening</small>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-hand-index"></i> Klik pada kode rekening untuk menambahkan ke filter
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Divider -->
                <hr class="my-3">
                
                <!-- Filter Pengecualian -->
                <div class="mb-2">
                    <h6 class="text-muted">
                        <i class="bi bi-funnel-fill"></i> Filter Pengecualian
                        <small class="text-muted">(Data yang akan dikecualikan dari hasil)</small>
                    </h6>
                </div>
                
                <!-- Filter Kata Kunci Pengecualian -->
                <div class="mb-2 row">
                    <div class="col-md-3">
                        <label class="form-label">Kecualikan Kata Kunci:</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" name="exclude_keyword" value="{{ $excludeKeyword ?? '' }}" 
                               placeholder="Contoh: honor, perjalanan, pakaian dinas" 
                               class="form-control form-control-sm">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Pisahkan dengan koma atau spasi - data yang mengandung kata kunci ini akan dikecualikan
                        </small>
                    </div>
                </div>
                
                <!-- Filter Kode Rekening Pengecualian -->
                <div class="mb-3 row">
                    <div class="col-md-3">
                        <label class="form-label">Kecualikan Kode Rekening:</label>
                    </div>
                    <div class="col-md-9">
                        <!-- Input Kode Rekening Pengecualian -->
                        <input type="text" name="exclude_kode_rekening" id="excludeKodeRekeningInput" 
                               value="{{ is_array($excludeKodeRekening ?? '') ? implode(', ', $excludeKodeRekening ?? []) : ($excludeKodeRekening ?? '') }}" 
                               class="form-control form-control-sm" 
                               placeholder="Contoh: 5.2.01, 5.2.02, 5.3.01">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Pisahkan dengan koma untuk beberapa kode rekening
                            @if(isset($kodeRekenings))
                                | <strong>{{ is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0 }}</strong> kode tersedia
                            @endif
                        </small>
                        
                        <!-- Toggle Reference Button untuk Pengecualian -->
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="toggleExcludeReferenceBtn">
                                <i class="bi bi-list-ul"></i> Tampilkan Referensi Kode Rekening 
                                @if(isset($kodeRekenings))
                                    <span class="badge bg-secondary">{{ is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0 }}</span>
                                @endif
                            </button>
                        </div>
                        
                        <!-- Reference Container untuk Pengecualian (Hidden by default) -->
                        <div id="excludeKodeRekeningReference" class="mt-2" style="display: none;">
                            <!-- Search Reference -->
                            <div class="mb-2">
                                <input type="text" id="excludeKodeRekeningSearch" class="form-control form-control-sm" 
                                       placeholder="Cari referensi kode rekening..." autocomplete="off">
                            </div>
                            
                            <!-- Reference List -->
                            <div id="excludeKodeRekeningContainer" class="p-2 rounded border" style="max-height: 250px; overflow-y: auto; background-color: #fff3cd;">
                                @if(isset($kodeRekenings) && (is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0) > 0)
                                    @foreach($kodeRekenings as $kode)
                                        @if(is_object($kode) && isset($kode->kode_rekening))
                                            <div class="py-1 exclude-kode-rekening-item border-bottom" 
                                                 data-kode="{{ $kode->kode_rekening }}" 
                                                 data-text="{{ strtolower($kode->kode_rekening . ' ' . $kode->uraian) }}"
                                                 style="cursor: pointer;">
                                                <small>
                                                    <strong class="text-danger">{{ $kode->kode_rekening }}</strong> - 
                                                    <span class="text-muted">{{ $kode->uraian }}</span>
                                                    <i class="bi bi-dash-circle float-end text-danger"></i>
                                                </small>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="py-3 text-center">
                                        <small class="text-muted">Tidak ada data kode rekening</small>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-hand-index"></i> Klik pada kode rekening untuk mengecualikan dari hasil
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tombol Aksi -->
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm me-2" id="searchBtn" disabled>
                            <i class="bi bi-search"></i> Cari
                        </button>
                
                        @if($keyword || $tahapanId || (is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening))
                            <a href="{{ route('compare-rek') }}" class="btn btn-secondary btn-sm me-2">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                            <a href="{{ route('compare-rek.export-excel', ['tahapan_id' => $tahapanId, 'keyword' => $keyword, 'kode_rekening' => $kodeRekening]) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-excel"></i> Export Excel
                    </a>
                @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body">
        @if($keyword || $tahapanId || (is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening))
            <div class="mb-3 alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Filter Aktif:</strong>
                @if($tahapanId)
                    <span class="badge bg-primary me-2">Tahapan: {{ $tahapans->find($tahapanId)->name ?? 'Tahapan ' . $tahapanId }}</span>
                @endif
                @if($keyword)
                    @php
                        $keywords = array_filter(array_map('trim', explode(',', $keyword)));
                        if (empty($keywords)) {
                            $keywords = array_filter(array_map('trim', explode(' ', $keyword)));
                        }
                        $keywordCount = count($keywords);
                    @endphp
                    <span class="badge bg-success me-2">
                        Kata Kunci: "{{ $keyword }}" 
                        <small>({{ $keywordCount }} kata)</small>
                    </span>
                @endif
                @if((is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening))
                    @php
                        $kodeRekeningArray = is_array($kodeRekening) ? $kodeRekening : [$kodeRekening];
                        $kodeRekeningCount = count($kodeRekeningArray);
                        $kodeRekeningDisplay = is_array($kodeRekening) ? implode(', ', $kodeRekening) : $kodeRekening;
                    @endphp
                    <span class="badge bg-warning me-2">
                        Kode Rekening: "{{ $kodeRekeningDisplay }}" 
                        <small>({{ $kodeRekeningCount }} kode)</small>
                    </span>
                @endif
                @if(isset($excludeKeyword) && $excludeKeyword)
                    <span class="badge bg-danger me-2">
                        <i class="bi bi-x-circle"></i> Kecualikan Kata Kunci: "{{ $excludeKeyword }}"
                    </span>
                @endif
                @if(isset($excludeKodeRekening) && !empty($excludeKodeRekening))
                    @php
                        $excludeKodeRekeningDisplay = is_array($excludeKodeRekening) ? implode(', ', $excludeKodeRekening) : $excludeKodeRekening;
                    @endphp
                    <span class="badge bg-danger me-2">
                        <i class="bi bi-x-circle"></i> Kecualikan Kode: "{{ $excludeKodeRekeningDisplay }}"
                    </span>
                @endif
                <span class="badge bg-secondary">Total Data: {{ $rekap->count() }}</span>
            </div>
        @else
            <div class="mb-3 alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Silakan tentukan filter terlebih dahulu!</strong>
                <br>
                <span id="filterMessage">Masukkan kata kunci pencarian atau kode rekening untuk menampilkan data.</span>
                <br>
                <small class="text-muted">
                    <strong>Tips pencarian:</strong> 
                    • Kata kunci: Gunakan koma untuk memisahkan (contoh: "printer, atk, meubelier") 
                    • Kode rekening: Pilih dari dropdown yang dapat dicari
                </small>
            </div>
        @endif
        
        <div class="table-container">
            @if($rekap->isNotEmpty() && ($keyword || $tahapanId || (is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening)))
                <table id="rekapTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th style="text-align: center;">No</th>
                            <th style="text-align: center;">Kode SKPD</th>
                            <th style="text-align: center;">Nama SKPD</th>
                            <th style="text-align: center;">Kode Rekening</th>
                            <th style="text-align: center;">Nama Rekening</th>
                            <th style="text-align: center;">Nama Standar Harga</th>
                            @if($tahapanId)
                                <th style="text-align: center;">
                                    {{ $tahapans->find($tahapanId)->name ?? 'Tahapan ' . $tahapanId }}
                                </th>
                            @else
                                @foreach($availableTahapans as $tahapanId)
                                    <th style="text-align: center;">
                                        {{ $tahapans->find($tahapanId)->name ?? 'Tahapan ' . $tahapanId }}
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                                        <tbody>
                        @php 
                            $no = 1;
                            $currentSkpd = null;
                            $skpdTotal = 0;
                        @endphp
                        @foreach($rekap as $item)
                            @if($currentSkpd !== null && $currentSkpd !== $item->kode_skpd)
                                <!-- Tampilkan total SKPD sebelumnya -->
                                <tr class="table-warning fw-bold">
                                    <td class="text-center"></td>
                                    <td colspan="5" class="text-start ps-3">
                                        <strong>TOTAL {{ strtoupper($rekap->where('kode_skpd', $currentSkpd)->first()->nama_skpd) }}</strong>
                                    </td>
                                    @if($tahapanId)
                                        <td class="text-end table-warning">
                                            <strong>{{ number_format($skpdTotal, 2, ',', '.') }}</strong>
                                        </td>
                                    @else
                                        @foreach($availableTahapans as $tahapanIdLoop)
                                            @php
                                                $totalPerTahapanSkpd = $rekap->where('kode_skpd', $currentSkpd)
                                                    ->where('tahapan_id', $tahapanIdLoop)
                                                    ->sum('total_pagu');
                                            @endphp
                                            <td class="text-end table-warning">
                                                <strong>{{ number_format($totalPerTahapanSkpd, 2, ',', '.') }}</strong>
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                                @php $skpdTotal = 0; @endphp
                            @endif
                            
                            <tr>
                                <td class="text-center">{{ $no++ }}</td>
                                <td class="text-center">{{ $item->kode_skpd }}</td>
                                <td>{{ $item->nama_skpd }}</td>
                                <td>{{ $item->kode_rekening }}</td>
                                <td>{{ $item->nama_rekening }}</td>
                                <td>{{ $item->nama_standar_harga }}</td>
                                @if($tahapanId)
                                    <td class="text-end">
                                        {{ number_format($item->total_pagu, 2, ',', '.') }}
                                    </td>
                                    @php $skpdTotal += $item->total_pagu; @endphp
                                @else
                                    @foreach($availableTahapans as $tahapanIdLoop)
                                        @php
                                            $nilai = ($item->tahapan_id == $tahapanIdLoop) ? $item->total_pagu : 0;
                                        @endphp
                                        <td class="text-end">
                                            {{ $nilai ? number_format($nilai, 2, ',', '.') : '-' }}
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                            @php $currentSkpd = $item->kode_skpd; @endphp
                        @endforeach
                        
                        <!-- Tampilkan total SKPD terakhir -->
                        @if($currentSkpd !== null)
                            <tr class="table-warning fw-bold">
                                <td class="text-center"></td>
                                <td colspan="5" class="text-start ps-3">
                                    <strong>TOTAL {{ strtoupper($rekap->where('kode_skpd', $currentSkpd)->first()->nama_skpd) }}</strong>
                                </td>
                                @if($tahapanId)
                                    <td class="text-end table-warning">
                                        <strong>{{ number_format($skpdTotal, 2, ',', '.') }}</strong>
                                    </td>
                                @else
                                    @foreach($availableTahapans as $tahapanId)
                                        @php
                                            $totalPerTahapanSkpd = $rekap->where('kode_skpd', $currentSkpd)
                                                ->where('tahapan_id', $tahapanId)
                                                ->sum('total_pagu');
                                        @endphp
                                        <td class="text-end table-warning">
                                            <strong>{{ number_format($totalPerTahapanSkpd, 2, ',', '.') }}</strong>
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <th colspan="{{ $tahapanId ? 6 : 6 }}" class="text-center">TOTAL</th>
                            @if($tahapanId)
                                <th class="text-end">
                                    {{ number_format($totalPerTahapan[$tahapanId] ?? 0, 2, ',', '.') }}
                                </th>
                            @else
                                @foreach($availableTahapans as $tahapanId)
                                    <th class="text-end">
                                        {{ number_format($totalPerTahapan[$tahapanId] ?? 0, 2, ',', '.') }}
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                        <tr class="table-dark fw-bold">
                            <th colspan="{{ $tahapanId ? 6 : 6 }}" class="text-center">GRAND TOTAL</th>
                            @if($tahapanId)
                                <th class="text-end">
                                    {{ number_format($grandTotal, 2, ',', '.') }}
                                </th>
                            @else
                                <th colspan="{{ count($availableTahapans) }}" class="text-end">
                                    {{ number_format($grandTotal, 2, ',', '.') }}
                                </th>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="text-center alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    @if($keyword || $tahapanId || (is_array($kodeRekening) ? !empty($kodeRekening) : $kodeRekening))
                        Tidak ada data yang sesuai dengan filter yang diterapkan. 
                        Silakan coba kata kunci lain atau reset filter.
                    @else
                        Data tidak ditampilkan karena belum ada filter yang diterapkan.
                    @endif
                </div>
            @endif
        </div>

       

    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS - Removed to avoid conflicts -->
<!-- <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script> -->

<script>
    $(document).ready(function() {
        console.log('Kode rekening filter initialized');
        
        // Function untuk filter reference list
        function filterReferenceList() {
            var searchTerm = $('#kodeRekeningSearch').val() ? $('#kodeRekeningSearch').val().toLowerCase() : '';
            console.log('Search term:', searchTerm);
            
            var totalItems = 0;
            var visibleItems = 0;
            
            $('.kode-rekening-item').each(function() {
                totalItems++;
                var itemText = $(this).data('text') || '';
                if (searchTerm === '' || itemText.indexOf(searchTerm) !== -1) {
                    $(this).show();
                    visibleItems++;
                } else {
                    $(this).hide();
                }
            });
            
            console.log('Filter results - Total items:', totalItems, 'Visible items:', visibleItems);
        }
        
        // Function untuk add kode rekening ke input
        function addKodeRekeningToInput(kodeRekening) {
            console.log('Adding kode rekening:', kodeRekening);
            
            var $input = $('#kodeRekeningInput');
            if ($input.length === 0) {
                console.error('Input kode rekening not found!');
                return;
            }
            
            var currentValue = $input.val().trim();
            var kodeArray = currentValue ? currentValue.split(',').map(function(k) { return k.trim(); }) : [];
            
            // Cek apakah kode sudah ada
            if (!kodeArray.includes(kodeRekening)) {
                kodeArray.push(kodeRekening);
                $input.val(kodeArray.join(', '));
                console.log('Added kode rekening:', kodeRekening);
                console.log('Current value:', $input.val());
                validateForm();
            } else {
                console.log('Kode rekening already exists:', kodeRekening);
            }
        }
        
        // Event listener untuk toggle reference (delegated)
        $(document).on('click', '#toggleReferenceBtn', function() {
            var $reference = $('#kodeRekeningReference');
            console.log('Toggle reference button clicked');
            
            if ($reference.is(':visible')) {
                $reference.slideUp(300);
                $(this).html('<i class="bi bi-list-ul"></i> Tampilkan Referensi Kode Rekening');
            } else {
                $reference.slideDown(300);
                $(this).html('<i class="bi bi-eye-slash"></i> Sembunyikan Referensi');
                filterReferenceList();
            }
        });
        
        // Event listener untuk click pada kode rekening item (delegated)
        $(document).on('click', '.kode-rekening-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var kodeRekening = $(this).data('kode');
            console.log('Kode rekening item clicked:', kodeRekening);
            
            if (!kodeRekening) {
                console.error('Kode rekening is empty!');
                return;
            }
            
            addKodeRekeningToInput(kodeRekening);
            
            // Visual feedback
            var $item = $(this);
            $item.css('background-color', '#d1e7dd');
            setTimeout(function() {
                $item.css('background-color', '');
            }, 500);
        });
        
        // Event listener untuk search reference (delegated)
        $(document).on('input', '#kodeRekeningSearch', function() {
            filterReferenceList();
        });
        
        // Event listener untuk clear search dengan Escape (delegated)
        $(document).on('keyup', '#kodeRekeningSearch', function(e) {
            if (e.key === 'Escape') {
                $(this).val('');
                filterReferenceList();
            }
        });
        
        // Event listener untuk kode rekening input change (delegated)
        $(document).on('input', '#kodeRekeningInput', function() {
            validateForm();
        });
        
        // ========== EXCLUDE FILTER FUNCTIONS ==========
        
        // Function untuk filter exclude reference list
        function filterExcludeReferenceList() {
            var searchTerm = $('#excludeKodeRekeningSearch').val() ? $('#excludeKodeRekeningSearch').val().toLowerCase() : '';
            console.log('Exclude search term:', searchTerm);
            
            var totalItems = 0;
            var visibleItems = 0;
            
            $('.exclude-kode-rekening-item').each(function() {
                totalItems++;
                var itemText = $(this).data('text') || '';
                if (searchTerm === '' || itemText.indexOf(searchTerm) !== -1) {
                    $(this).show();
                    visibleItems++;
                } else {
                    $(this).hide();
                }
            });
            
            console.log('Exclude filter results - Total items:', totalItems, 'Visible items:', visibleItems);
        }
        
        // Function untuk add exclude kode rekening ke input
        function addExcludeKodeRekeningToInput(kodeRekening) {
            console.log('Adding exclude kode rekening:', kodeRekening);
            
            var $input = $('#excludeKodeRekeningInput');
            if ($input.length === 0) {
                console.error('Exclude input kode rekening not found!');
                return;
            }
            
            var currentValue = $input.val().trim();
            var kodeArray = currentValue ? currentValue.split(',').map(function(k) { return k.trim(); }) : [];
            
            // Cek apakah kode sudah ada
            if (!kodeArray.includes(kodeRekening)) {
                kodeArray.push(kodeRekening);
                $input.val(kodeArray.join(', '));
                console.log('Added exclude kode rekening:', kodeRekening);
                console.log('Current exclude value:', $input.val());
            } else {
                console.log('Exclude kode rekening already exists:', kodeRekening);
            }
        }
        
        // Event listener untuk toggle exclude reference (delegated)
        $(document).on('click', '#toggleExcludeReferenceBtn', function() {
            var $reference = $('#excludeKodeRekeningReference');
            console.log('Toggle exclude reference button clicked');
            
            if ($reference.is(':visible')) {
                $reference.slideUp(300);
                $(this).html('<i class="bi bi-list-ul"></i> Tampilkan Referensi Kode Rekening <span class="badge bg-secondary">{{ is_countable($kodeRekenings) ? (is_array($kodeRekenings) ? count($kodeRekenings) : $kodeRekenings->count()) : 0 }}</span>');
            } else {
                $reference.slideDown(300);
                $(this).html('<i class="bi bi-eye-slash"></i> Sembunyikan Referensi');
                filterExcludeReferenceList();
            }
        });
        
        // Event listener untuk click pada exclude kode rekening item (delegated)
        $(document).on('click', '.exclude-kode-rekening-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var kodeRekening = $(this).data('kode');
            console.log('Exclude kode rekening item clicked:', kodeRekening);
            
            if (!kodeRekening) {
                console.error('Exclude kode rekening is empty!');
                return;
            }
            
            addExcludeKodeRekeningToInput(kodeRekening);
            
            // Visual feedback
            var $item = $(this);
            $item.css('background-color', '#f8d7da');
            setTimeout(function() {
                $item.css('background-color', '');
            }, 500);
        });
        
        // Event listener untuk search exclude reference (delegated)
        $(document).on('input', '#excludeKodeRekeningSearch', function() {
            filterExcludeReferenceList();
        });
        
        // Event listener untuk clear search dengan Escape (delegated)
        $(document).on('keyup', '#excludeKodeRekeningSearch', function(e) {
            if (e.key === 'Escape') {
                $(this).val('');
                filterExcludeReferenceList();
            }
        });
        
        // Validasi form filter
        function validateForm() {
            var keyword = $('input[name="keyword"]').val().trim();
            var kodeRekening = $('#kodeRekeningInput').val().trim();
            var tahapanId = $('select[name="tahapan_id"]').val();
            var searchBtn = $('#searchBtn');
            var filterMessage = $('#filterMessage');
            
            // Cek apakah minimal ada satu filter yang diisi
            var hasFilter = keyword !== '' || kodeRekening !== '' || tahapanId !== '';
            
            console.log('Validation check:', {
                keyword: keyword,
                kodeRekening: kodeRekening,
                tahapanId: tahapanId,
                hasFilter: hasFilter
            });
            
            if (hasFilter) {
                searchBtn.prop('disabled', false);
                searchBtn.removeClass('btn-secondary').addClass('btn-primary');
                if (filterMessage.length) {
                    filterMessage.text('Filter sudah diisi. Klik tombol Cari untuk menampilkan data.');
                }
            } else {
                searchBtn.prop('disabled', true);
                searchBtn.removeClass('btn-primary').addClass('btn-secondary');
                if (filterMessage.length) {
                    filterMessage.text('Masukkan kata kunci pencarian atau kode rekening untuk menampilkan data.');
                }
            }
        }
        
        // Event listener untuk input fields
        $('input[name="keyword"], select[name="tahapan_id"]').on('input change', function() {
            validateForm();
        });
        
        // Validasi saat form submit
        $('form').on('submit', function(e) {
            var keyword = $('input[name="keyword"]').val().trim();
            var kodeRekening = $('#kodeRekeningInput').val().trim();
            var tahapanId = $('select[name="tahapan_id"]').val();
            
            console.log('Form submit validation:', {
                keyword: keyword,
                kodeRekening: kodeRekening,
                tahapanId: tahapanId
            });
            
            if (keyword === '' && kodeRekening === '' && tahapanId === '') {
                e.preventDefault();
                alert('Silakan isi minimal satu filter (kata kunci, kode rekening, atau tahapan) sebelum melakukan pencarian.');
                return false;
            }
        });
        
        // Inisialisasi validasi saat halaman dimuat
        validateForm();
        
        // Debug: Log initial state
        console.log('Initial state check:');
        console.log('Toggle button exists:', $('#toggleReferenceBtn').length > 0);
        console.log('Reference container exists:', $('#kodeRekeningReference').length > 0);
        console.log('Kode rekening input exists:', $('#kodeRekeningInput').length > 0);
        console.log('Kode rekening container exists:', $('#kodeRekeningContainer').length > 0);
        console.log('Reference items count:', $('.kode-rekening-item').length);
        console.log('Reference items HTML:', $('#kodeRekeningContainer').html().substring(0, 200));
        
        // Re-initialize after page load (for form persistence)
        $(window).on('load', function() {
            setTimeout(function() {
                console.log('Re-initializing after page load');
                filterReferenceList();
                validateForm();
                
                console.log('After load - Reference items count:', $('.kode-rekening-item').length);
                console.log('After load - Toggle button exists:', $('#toggleReferenceBtn').length > 0);
                console.log('After load - Reference container exists:', $('#kodeRekeningReference').length > 0);
            }, 500);
        });
        
        // Initialize reference list
        filterReferenceList();
        
        // Simple table styling
        $('#rekapTable').addClass('table table-striped table-bordered table-hover');
        
        // Add row numbers to the table (skip total rows)
        var rowNumber = 1;
        $('#rekapTable tbody tr').each(function() {
            // Skip baris total (yang memiliki class table-warning)
            if (!$(this).hasClass('table-warning')) {
                $(this).find('td:first').text(rowNumber);
                rowNumber++;
            }
        });
    });
</script>

@endsection
