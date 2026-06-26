<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px;}
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        h2, h3 { margin: 10px 0; }
    </style>
</head>
<body>

<h2>Monitoring Perkuliahan</h2>
<p>
    Semester: {{ $semester }} |
    Tahun: {{ $tahun }} |
    Tingkat: {{ $tingkat }}
</p>

<p><b>Keterangan:</b> 1 = Sudah Upload, 0 = Belum Upload</p>

<!-- ================= TEORI ================= -->
<h3>Materi Teori</h3>
<table>
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama</th>
            <th>Dosen</th>
            @for ($i = 1; $i <= 16; $i++)
                <th>W{{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($materiTeori as $mk)
        <tr>
            <td>{{ $mk['kode'] }}</td>
            <td>{{ $mk['nama'] }}</td>
            <td>{{ $mk['dosen'] }}</td>
            @foreach($mk['weeks'] as $w)
                <td>
                    @if($w === 1 || $w === 2)
                        1
                    @elseif($w === 0)
                        0
                    @else
                        0
                    @endif
                </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<!-- ================= PRAKTIKUM ================= -->
<h3>Materi Praktikum</h3>
<table>
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama</th>
            <th>Dosen</th>
            @for ($i = 1; $i <= 16; $i++)
                <th>W{{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($materiPraktikum as $mk)
        <tr>
            <td>{{ $mk['kode'] }}</td>
            <td>{{ $mk['nama'] }}</td>
            <td>{{ $mk['dosen'] }}</td>
            @foreach($mk['weeks'] as $w)
                <td>
                    @if($w === 1 || $w === 2)
                        1
                    @elseif($w === 0)
                        0
                    @else
                        0
                    @endif
                </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>