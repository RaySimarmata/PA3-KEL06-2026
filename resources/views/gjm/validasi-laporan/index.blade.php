@extends('layouts.app')

@section('page-title', 'Validasi & Verifikasi Laporan')

@section('content')
<div class="container-fluid">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0">
            <h5 class="mb-0">Laporan GKM Menunggu Validasi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Program Studi</th>
                            <th>Periode</th>
                            <th>Tanggal Kirim</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Teknologi Rekayasa Perangkat Lunak (TRPL)</td>
                            <td>Februari 2026</td>
                            <td>01-02-2026</td>
                            <td><span class="badge bg-warning">Menunggu Validasi</span></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">Lihat Laporan</a>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Teknologi Rekayasa Perangkat Lunak (TRPL)</td>
                            <td>Januari 2026</td>
                            <td>31-01-2026</td>
                            <td><span class="badge bg-success">Disetujui</span></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">Lihat Laporan</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Detail Laporan untuk Validasi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">Laporan Materi & RPS Bulanan</h6>
                            <p class="text-muted">Dikirimi oleh: GKM DTRPL</p>
                            <p class="text-muted">Tanggal Kirim: 01 Februari 2026</p>
                            <p class="text-muted">Tanggal Validasi: 02 Februari 2026</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">Checklist Validasi</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check1" checked>
                                <label class="form-check-label" for="check1">
                                    Keterangan sesuai laporan
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check2" checked>
                                <label class="form-check-label" for="check2">
                                    Data kuisioner valid
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check3" checked>
                                <label class="form-check-label" for="check3">
                                    Ada beberapa kelengkapan RPS perlu diperbaiki
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <label class="form-label">Tambahkan Catatan (Opsional):</label>
                            <textarea class="form-control" rows="3" placeholder="Berikan catatan tambahan untuk GKM..."></textarea>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <a href="#" class="btn btn-success me-2">
                                <i class="bi bi-check-circle"></i> Approve Laporan
                            </a>
                            <button class="btn btn-warning me-2">
                                <i class="bi bi-exclamation-circle"></i> Tambah Catatan
                            </button>
                            <button class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Kirim ke Dekan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
