<?php

namespace App\Services;

use App\Models\RecordedLog;

class StatementSnapshot
{
    public function find($week, $driver, $company): ?RecordedLog
    {
        $logs = RecordedLog::where('tvde_week_id', $week)->where('driver_id', $driver)->get();
        return $logs->first(function ($log) use ($driver) {
            return $driver && data_get(json_decode($log->data), 'statement_scope') === 'driver';
        }) ?? $logs->firstWhere('company_id', $company);
    }
}
