<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    </style>
</head>
<body>

<h2>Monitoring RPS</h2>
<p>
    Semester: {{ $semester }} |
    Tahun: {{ $tahun }} |
    Tingkat: {{ $tingkat }}
</p>

<table>
    <thead>
        <tr>
            <th>Kode MK</th>
            <th>Nama Matakuliah</th>
            <th>Dosen</th>
            <th>Status RPS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $mk)
        <tr>
            <td>{{ $mk['kode_mk'] }}</td>
            <td>{{ $mk['nama_matkul'] }}</td>
            <td>{{ $mk['dosen_pengampu'] }}</td>
            <td>
                {{ $mk['status_rps'] === 'SUDAH UPLOAD' ? '1' : '0' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<p><b>Keterangan:</b> 1 = Sudah Upload, 0 = Belum Upload</p>

</body>
</html>