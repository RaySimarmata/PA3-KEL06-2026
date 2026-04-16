<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PeriodeAkademik;

class PeriodeAkademikController extends Controller
{
    public function index()
    {
        $data = PeriodeAkademik::latest()->get();
        return view('gkm.data-master.periodeA', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran' => 'required',
            'semester' => 'required|in:1,2',
            'start_date' => 'required|date'
        ]);

        // jika set active, matikan yang lain
        if ($request->has('is_active')) {
            PeriodeAkademik::where('is_active', true)
                ->update(['is_active' => false]);
        }

        PeriodeAkademik::create([
            'tahun_ajaran' => $request->tahun_ajaran,
            'semester' => $request->semester,
            'semester_label' => $request->semester == 1 ? 'Ganjil' : 'Genap',
            'start_date' => $request->start_date,
            'is_active' => $request->has('is_active')
        ]);

        return back()->with('success', 'Periode berhasil ditambahkan');
    }

    public function getActive()
    {
        return response()->json(
            PeriodeAkademik::where('is_active', true)->first()
        );
    }
}