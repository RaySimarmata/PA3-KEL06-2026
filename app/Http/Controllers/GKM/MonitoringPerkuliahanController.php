<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Helpers\EmailHelper;
use App\Services\AIAgentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MonitoringPerkuliahanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $reminders = Reminder::with('userPenerima')
            ->paginate(10);

        return view('gkm.monitoring-perkuliahan.index', compact('user', 'reminders'));
    }

    public function reminderPerwalian()
    {
        $user = Auth::user();
        
        // Ambil dosen wali dari prodi
        $dosenList = Dosen::where('prodi_id', $user->prodi_id)
            ->where('is_dosen_wali', true)
            ->with('matakuliah')
            ->get();

        return view('gkm.monitoring-perkuliahan.perwalian', compact('user', 'dosenList'));
    }

    public function reminderMateri()
    {
        $user = Auth::user();
        
        // Ambil semua dosen dari prodi
        $dosenList = Dosen::where('prodi_id', $user->prodi_id)
            ->with('matakuliah')
            ->get();

        return view('gkm.monitoring-perkuliahan.materi', compact('user', 'dosenList'));
    }

    public function reminderReviewSoal()
    {
        $user = Auth::user();
        
        // Ambil kaprodi dari prodi
        $dosenList = Dosen::where('prodi_id', $user->prodi_id)
            ->where('is_kaprodi', true)
            ->with(['matakuliah', 'prodi'])
            ->get();

        return view('gkm.monitoring-perkuliahan.soal', compact('user', 'dosenList'));
    }

    /**
     * Kirim Reminder Perwalian & Persiapan Perkuliahan
     */
    public function kirimReminderPerwalian(Request $request)
    {
        $validated = $request->validate([
            'subjek' => 'required|string|max:255',
            'pesan' => 'required|string',
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
        ]);

        $user = Auth::user();
        $prodi = Prodi::find($user->prodi_id);

        // Ambil dosen berdasarkan ID yang dipilih
        $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])->get();

        if ($dosenList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada dosen yang dipilih');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($dosenList as $dosen) {
            // Kirim email
            $result = EmailHelper::sendReminderPerwalian(
                $dosen->kontak_email,
                $dosen->nama_lengkap,
                $validated['subjek'],
                $validated['pesan'],
                $prodi->nama_prodi ?? 'TRPL',
                $prodi->kode_prodi ?? 'TRPL'
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        $message = "Reminder berhasil dikirim ke {$successCount} dosen";
        if ($failedCount > 0) {
            $message .= ", gagal {$failedCount} dosen";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Kirim Reminder Upload Materi di CIS
     */
    public function kirimReminderUploadMateri(Request $request)
    {
        $validated = $request->validate([
            'subjek' => 'required|string|max:255',
            'pesan' => 'required|string',
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
        ]);

        $user = Auth::user();
        $prodi = Prodi::find($user->prodi_id);

        // Ambil dosen berdasarkan ID yang dipilih
        $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])->get();

        if ($dosenList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada dosen yang dipilih');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($dosenList as $dosen) {
            // Kirim email
            $result = EmailHelper::sendReminderUploadMateri(
                $dosen->kontak_email,
                $dosen->nama_lengkap,
                $validated['subjek'],
                $validated['pesan'],
                $prodi->nama_prodi ?? 'TRPL',
                $prodi->kode_prodi ?? 'TRPL'
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        $message = "Reminder berhasil dikirim ke {$successCount} dosen";
        if ($failedCount > 0) {
            $message .= ", gagal {$failedCount} dosen";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Kirim Reminder Kaprodi Review Soal
     */
    public function kirimReminderReviewSoal(Request $request)
    {
        $validated = $request->validate([
            'subjek' => 'required|string|max:255',
            'pesan' => 'required|string',
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
        ]);

        $user = Auth::user();
        $prodi = Prodi::find($user->prodi_id);

        // Ambil kaprodi berdasarkan ID yang dipilih
        $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])->get();

        if ($dosenList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada kaprodi yang dipilih');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($dosenList as $dosen) {
            // Kirim email
            $result = EmailHelper::sendReminderReviewSoal(
                $dosen->kontak_email,
                $dosen->nama_lengkap,
                $validated['subjek'],
                $validated['pesan'],
                $prodi->nama_prodi ?? 'TRPL',
                $prodi->kode_prodi ?? 'TRPL'
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        $message = "Reminder berhasil dikirim ke {$successCount} kaprodi";
        if ($failedCount > 0) {
            $message .= ", gagal {$failedCount} kaprodi";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Generate message untuk reminder perwalian dengan AI Agent
     */
    public function generateMessagePerwalian(Request $request)
    {
        try {
            $validated = $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
            ]);

            $user = Auth::user();
            $prodi = Prodi::find($user->prodi_id);
            
            // Ambil dosen berdasarkan ID yang dipilih
            $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])
                ->with('matakuliah')
                ->get();

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dosen yang dipilih'
                ], 400);
            }

            $aiAgent = new AIAgentService();
            $message = $aiAgent->generateReminderMessage($dosenList, $prodi, 'perwalian');

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Generate Message Perwalian Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate message untuk reminder upload materi dengan AI Agent
     */
    public function generateMessageMateri(Request $request)
    {
        try {
            $validated = $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
            ]);

            $user = Auth::user();
            $prodi = Prodi::find($user->prodi_id);
            
            // Ambil dosen berdasarkan ID yang dipilih
            $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])
                ->with('matakuliah')
                ->get();

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dosen yang dipilih'
                ], 400);
            }

            $aiAgent = new AIAgentService();
            $message = $aiAgent->generateReminderMessage($dosenList, $prodi, 'materi');

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Generate Message Materi Error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate message untuk reminder review soal dengan AI Agent
     */
    public function generateMessageSoal(Request $request)
    {
        try {
            $validated = $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
            ]);

            $user = Auth::user();
            $prodi = Prodi::find($user->prodi_id);
            
            // Ambil kaprodi berdasarkan ID yang dipilih
            $dosenList = Dosen::whereIn('id', $validated['dosen_ids'])
                ->with(['matakuliah', 'prodi'])
                ->get();

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada kaprodi yang dipilih'
                ], 400);
            }

            $aiAgent = new AIAgentService();
            $message = $aiAgent->generateReminderMessage($dosenList, $prodi, 'soal');

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Generate Message Soal Error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }
}
