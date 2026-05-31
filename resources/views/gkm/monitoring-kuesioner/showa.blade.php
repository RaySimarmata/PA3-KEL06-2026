@extends('layouts.app')

@section('content')

<div class="container">

    <h4 class="mb-4">
        👁 Detail Kuesioner
    </h4>

    <div class="card mb-3">
        <div class="card-body">

            <p>
                <strong>Judul:</strong>
                {{ $kuesioner->judul_kuesioner }}
            </p>

            <p>
                <strong>Kode MK:</strong>
                {{ $kuesioner->kode_mk }}
            </p>

            <p>
                <strong>Kuesioner ID:</strong>
                {{ $kuesioner->kuesioner_id }}
            </p>

            <p>
                <strong>Tahun:</strong>
                {{ $kuesioner->periode }}
            </p>

            <p>
                <strong>Semester:</strong>
                {{ $kuesioner->semester }}
            </p>

            <p>
                <strong>Jenis:</strong>
                {{ $kuesioner->jenis_kuesioner ?? '-' }}
            </p>

        </div>
    </div>

    {{-- LIST PERTANYAAN --}}
    <div class="card">
        <div class="card-body">

            <h5 class="mb-3">
                📋 Pertanyaan
            </h5>

            @php
                $rekapitulasi =
                    $kuesioner->raw_data['statistik']['rekapitulasi']
                    ?? [];
            @endphp

            @forelse($rekapitulasi as $i => $item)

                <div class="border rounded p-3 mb-3">

                    <strong>
                        {{ $i + 1 }}.
                        {!! $item['pertanyaan'] ?? '-' !!}
                    </strong>

                    <hr>

                    @foreach(($item['rincian_jawaban'] ?? []) as $jawaban)

                        <div class="d-flex justify-content-between">

                            <span>
                                Jawaban:
                                {{ $jawaban['jawaban'] ?? '-' }}
                            </span>

                            <span>
                                Jumlah:
                                {{ $jawaban['jumlah'] ?? 0 }}
                            </span>

                        </div>

                    @endforeach

                </div>

            @empty

                <div class="text-muted">
                    Tidak ada pertanyaan
                </div>

            @endforelse

        </div>
    </div>

    <a
        href="{{ url()->previous() }}"
        class="btn btn-secondary mt-3"
    >
        ⬅ Kembali
    </a>

</div>

@endsection