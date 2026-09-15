<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DownloadLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = DownloadLog::with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($date = $request->input('date')) {
            $query->where('download_date', $date);
        }

        $logs = $query->latest('download_date')->latest('id')->paginate(20)->withQueryString();

        $todayTotal = DownloadLog::where('download_date', today())->sum('downloads_count');
        $allTimeTotal = DownloadLog::sum('downloads_count');
        $uniqueDevicesToday = DownloadLog::where('download_date', today())->distinct('device_id')->count('device_id');

        return view('admin.downloads.index', compact('logs', 'todayTotal', 'allTimeTotal', 'uniqueDevicesToday'));
    }

    public function resetToday(Request $request, DownloadLog $downloadLog): RedirectResponse
    {
        $downloadLog->update(['downloads_count' => 0]);

        return back()->with('success', 'Kuota unduhan hari ini untuk IP/Device tersebut berhasil di-reset ke 0.');
    }
}
