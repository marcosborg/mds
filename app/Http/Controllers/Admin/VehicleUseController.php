<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleItem;
use App\Models\RecordedLog;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleUseController extends Controller
{
    public function index($vehicle_item_id)
    {
        abort_if(Gate::denies('vehicle_use_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle_item = VehicleItem::find($vehicle_item_id)->load('recorded_logs.tvde_week');

        // Preparar dados para o gráfico
        $weeks = $vehicle_item->recorded_logs->map(function ($log) {
            return [
                'week' => $log->tvde_week->number,
                'start_date' => $log->tvde_week->start_date,
                'end_date' => $log->tvde_week->end_date,
                'balance' => $log->balance
            ];
        });

        return view('admin.vehicleUses.index', compact('vehicle_item', 'weeks'));
    }
}
