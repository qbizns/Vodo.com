<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    /**
     * Display system logs.
     */
    public function logs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            // Read last 1000 lines
            $content = File::get($logPath);
            // Simple parsing (could be improved with a log parser library)
            // This is a basic implementation to satisfy the route requirement
            $logs = explode("\n", $content);
            $logs = array_slice($logs, -100); // Last 100 lines
            $logs = array_reverse($logs);
        }

        return view('backend.system.logs', [
            'logs' => $logs,
            'logPath' => $logPath
        ]);
    }
}
