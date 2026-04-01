@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Monitoring Kuesioner Mahasiswa</h4>
                    <a href="{{ route('gkm.monitoring-kuesioner.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Upload Kuesioner
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            @foreach($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama File</th>
                                    <th>Periode</th>
                                    <th>Matakuliah</th>
                                    <th>Kode MK</th>
                                    <th>Tingkat</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Program Studi</th>
                                    <th>Total Responden</th>
                                    <th>Status AI Analysis</th>
                                    <th>Tanggal Upload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kuesioners as $index => $kuesioner)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $kuesioner->nama_file }}</td>
                                        <td>{{ $kuesioner->periode }}</td>
                                        <td>{{ $kuesioner->nama_matakuliah ?? '-' }}</td>
                                        <td>{{ $kuesioner->kode_matakuliah ?? '-' }}</td>
                                        <td>{{ $kuesioner->tingkat ?? '-' }}</td>
                                        <td>{{ $kuesioner->dosen_pengampu ?? '-' }}</td>
                                        <td>{{ $kuesioner->user->prodi->nama_prodi ?? 'Unknown' }}</td>
                                        <td>{{ $kuesioner->total_responden }} responden</td>
                                        <td>
                                            @switch($kuesioner->status)
                                                @case('uploaded')
                                                    <span class="badge bg-info">Uploaded</span>
                                                    @break
                                                @case('processing')
                                                    <span class="badge bg-warning">Processing</span>
                                                    @break
                                                @case('completed')
                                                    <span class="badge bg-success">Completed</span>
                                                    @break
                                                @case('error')
                                                    <span class="badge bg-danger">Error</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>{{ $kuesioner->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('gkm.monitoring-kuesioner.show', $kuesioner->id) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if($kuesioner->status === 'completed')
                                                    <a href="{{ route('gkm.monitoring-kuesioner.report', $kuesioner->id) }}" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </a>
                                                @endif
                                                <form action="{{ route('gkm.monitoring-kuesioner.destroy', $kuesioner->id) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">Belum ada data kuesioner</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto refresh untuk status processing
setInterval(function() {
    if (document.querySelector('.badge.bg-warning')) {
        location.reload();
    }
}, 30000); // Refresh setiap 30 detik jika ada status processing
</script>
@endsection