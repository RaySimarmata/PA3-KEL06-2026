@extends('layouts.app')

@section('content')
<div class="container">

    <h4 class="mb-4">📋 Daftar Kuesioner</h4>

    <p>
        <strong>Kode MK:</strong> {{ $kode_mk }} <br>
        <strong>Tahun:</strong> {{ $ta }}
    </p>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- ALERT VALIDATION --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            @if(count($list) > 0)

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Judul Kuesioner</th>
                                <th style="width: 250px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($list as $i => $item)
                            <tr>

                                <td>
                                    {{ $i + 1 }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $item['judul'] }}
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Kode MK:
                                        {{ $item['kode_mk'] }}

                                        |

                                        Semester:
                                        {{ $item['semester'] ?? '-' }}

                                        |

                                        ID:
                                        {{ $item['kuesioner_id'] ?? '-' }}
                                    </small>
                                </td>

                                <td>

    <div class="d-grid gap-2">

        {{-- LIHAT KUESIONER --}}
        <a
            href="{{ route('gkm.monitoring-kuesioner.showa', $item['kuesioner_id']) }}"
            class="btn btn-primary btn-sm"
        >
            👁 Lihat Kuesioner
        </a>

        {{-- ANALISIS --}}
        <form action="{{ route('gkm.monitoring-kuesioner.processFromApi') }}" method="POST">

            @csrf

            <input
                type="hidden"
                name="kode_mk"
                value="{{ $item['kode_mk'] }}"
            >

            <input
                type="hidden"
                name="ta"
                value="{{ $item['ta'] }}"
            >

            <input
                type="hidden"
                name="kuesioner_id"
                value="{{ $item['kuesioner_id'] }}"
            >

            <button
                type="submit"
                class="btn btn-success btn-sm w-100"
                onclick="return confirm('Analisis kuesioner ini?')"
            >
                🔍 Analisis
            </button>

        </form>

    </div>

</td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else

                <div class="text-center text-muted py-4">
                    <p class="mb-0">
                        📭 Tidak ada kuesioner ditemukan
                    </p>
                </div>

            @endif

        </div>
    </div>

    <a
        href="{{ route('gkm.monitoring-kuesioner.create-api') }}"
        class="btn btn-secondary mt-3"
    >
        ⬅ Kembali
    </a>

</div>
@endsection