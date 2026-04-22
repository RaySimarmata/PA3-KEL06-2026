@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Analisis Kuesioner dari API</h4>

    <form method="POST" action="{{ route('gkm.monitoring-kuesioner.process-from-api') }}">
        @csrf

        <div class="mb-3">
            <label>Tahun Ajaran</label>
            <input type="text" name="ta" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Kode MK</label>
            <input type="text" name="kode_mk" class="form-control" required>
        </div>

        <button class="btn btn-primary">Proses Analisis</button>
    </form>
</div>
@endsection