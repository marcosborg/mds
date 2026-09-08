<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Company;
use App\Models\TvdeActivity;
use Illuminate\Support\Collection;

class DriverRevenue
{
    public function activities(Driver $driver, $week, int $operator): Collection
    {
        $code = $operator === 1 ? $driver->uber_uuid : $driver->bolt_name;
        if ($code === null || trim($code) === '') {
            return collect();
        }
        return TvdeActivity::where('tvde_week_id', $week)
            ->where('tvde_operator_id', $operator)->where('driver_code', $code)->get();
    }

    public function breakdown(Collection $uber, Collection $bolt, float $percent, float $tipPercent): array
    {
        $activities = $uber->concat($bolt);
        $companies = Company::withTrashed()->whereIn('id', $activities->pluck('company_id'))->pluck('name', 'id');
        return $activities->groupBy('company_id')->map(function ($rows, $id) use ($percent, $tipPercent, $companies) {
            $tips = (float) $rows->sum('earnings_one');
            $gross = (float) $rows->sum('earnings_two');
            $earnings = $gross - $tips;
            return [
                'company_id' => $id,
                'company_name' => $companies[$id] ?? "Empresa #{$id}",
                'earnings' => $earnings,
                'tips' => $tips,
                'gross' => $gross,
                'earnings_after_discount' => $earnings * $percent / 100,
                'tips_after_discount' => $tips * $tipPercent / 100,
            ];
        })->sortBy('company_name')->values()->all();
    }
}
