<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLog::with('user')->orderByDesc('logged_at');

        // Filter by channel
        if ($channel = $request->get('channel')) {
            $query->channel($channel);
        }

        // Filter by level
        if ($level = $request->get('level')) {
            $query->level($level);
        }

        // Filter by user
        if ($userId = $request->get('user_id')) {
            $query->byUser($userId);
        }

        // Filter by action
        if ($action = $request->get('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        // Filter by date range
        if ($from = $request->get('from')) {
            $query->where('logged_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->get('to')) {
            $query->where('logged_at', '<=', Carbon::parse($to)->endOfDay());
        }

        // Search in message
        if ($search = $request->get('search')) {
            $query->where('message', 'like', "%{$search}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        // Get filter options
        $channels = ['security', 'audit', 'error', 'info'];
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        $users = User::select('id', 'name')->orderBy('name')->get();

        // Stats for summary cards
        $todayStats = [
            'total'    => SystemLog::whereDate('logged_at', today())->count(),
            'security' => SystemLog::channel('security')->whereDate('logged_at', today())->count(),
            'errors'   => SystemLog::minLevel('error')->whereDate('logged_at', today())->count(),
            'audit'    => SystemLog::channel('audit')->whereDate('logged_at', today())->count(),
        ];

        return view('system_logs.index', compact(
            'logs',
            'channels',
            'levels',
            'users',
            'todayStats'
        ));
    }

    /**
     * Show a single log entry details
     */
    public function show(SystemLog $systemLog)
    {
        $systemLog->load('user');
        
        return response()->json([
            'id'         => $systemLog->id,
            'channel'    => $systemLog->channel,
            'level'      => $systemLog->level,
            'action'     => $systemLog->action,
            'message'    => $systemLog->message,
            'context'    => $systemLog->context,
            'ip_address' => $systemLog->ip_address,
            'user_agent' => $systemLog->user_agent,
            'url'        => $systemLog->url,
            'method'     => $systemLog->method,
            'user'       => $systemLog->user?->name ?? 'System',
            'logged_at'  => $systemLog->logged_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Export logs to CSV
     */
    public function export(Request $request)
    {
        $query = SystemLog::with('user')->orderByDesc('logged_at');

        // Apply same filters as index
        if ($channel = $request->get('channel')) {
            $query->channel($channel);
        }
        if ($level = $request->get('level')) {
            $query->level($level);
        }
        if ($from = $request->get('from')) {
            $query->where('logged_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->get('to')) {
            $query->where('logged_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $logs = $query->limit(10000)->get();

        $filename = 'system_logs_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'ID', 'Timestamp', 'Channel', 'Level', 'Action', 'Message',
                'User', 'IP Address', 'Method', 'URL'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->logged_at->format('Y-m-d H:i:s'),
                    $log->channel,
                    $log->level,
                    $log->action ?? '—',
                    $log->message,
                    $log->user?->name ?? 'System',
                    $log->ip_address,
                    $log->method,
                    $log->url,
                ]);
            }

            fclose($file);
        };

        // Log the export action
        SystemLog::audit(
            'System logs exported',
            'logs.export',
            ['filters' => $request->only(['channel', 'level', 'from', 'to']), 'count' => $logs->count()]
        );

        return response()->stream($callback, 200, $headers);
    }
}
