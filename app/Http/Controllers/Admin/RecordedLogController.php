<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyRecordedLogRequest;
use App\Http\Requests\StoreRecordedLogRequest;
use App\Http\Requests\UpdateRecordedLogRequest;
use App\Models\Company;
use App\Models\Driver;
use App\Models\RecordedLog;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Traits\Reports;


class RecordedLogController extends Controller
{

    use Reports;

    public function recordLog($tvde_week_id, $driver_id, $company_id, $value)
    {
        $recorded_logs = RecordedLog::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id,
            'company_id' => $company_id,
        ])->get();

        // Check if any logs exist and delete them
        if ($recorded_logs->isNotEmpty()) {
            foreach ($recorded_logs as $log) {
                $log->delete();
            }
        }

        $lastRecord = RecordedLog::orderBy('id', 'desc')->first();

        if ($lastRecord) {
            $balance = $lastRecord->balance + $value;
        } else {
            $balance = $value;
        }

        //Check if has vehicle item

        $vehicle_item = VehicleItem::where('driver_id', $driver_id)->first();

        $recorded_log = new RecordedLog;
        $recorded_log->tvde_week_id = $tvde_week_id;
        $recorded_log->driver_id = $driver_id;
        $recorded_log->company_id = $company_id;
        $recorded_log->vehicle_item_id = $vehicle_item ? $vehicle_item->id : null;
        $recorded_log->value = $value;
        $recorded_log->balance = $balance;
        $recorded_log->data = json_encode($this->getWeekResults($tvde_week_id, $driver_id, $company_id), true);
        $recorded_log->save();

        return $recorded_log;
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('recorded_log_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = RecordedLog::with(['tvde_week', 'driver', 'company', 'vehicle_item'])->select(sprintf('%s.*', (new RecordedLog)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'recorded_log_show';
                $editGate = 'recorded_log_edit';
                $deleteGate = 'recorded_log_delete';
                $crudRoutePart = 'recorded-logs';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('company_name', function ($row) {
                return $row->company ? $row->company->name : '';
            });

            $table->addColumn('vehicle_item_license_plate', function ($row) {
                return $row->vehicle_item ? $row->vehicle_item->license_plate : '';
            });

            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });

            $table->editColumn('balance', function ($row) {
                return $row->balance ? $row->balance : '';
            });
            $table->editColumn('data', function ($row) {
                return $row->data ? $row->data : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'tvde_week', 'driver', 'company', 'vehicle_item']);

            return $table->make(true);
        }

        $tvde_weeks = TvdeWeek::get();
        $drivers = Driver::get();
        $companies = Company::get();
        $vehicle_items = VehicleItem::get();

        return view('admin.recordedLogs.index', compact('tvde_weeks', 'drivers', 'companies', 'vehicle_items'));
    }

    public function create()
    {
        abort_if(Gate::denies('recorded_log_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.recordedLogs.create', compact('companies', 'drivers', 'tvde_weeks', 'vehicle_items'));
    }

    public function store(StoreRecordedLogRequest $request)
    {
        $recordedLog = RecordedLog::create($request->all());

        return redirect()->route('admin.recorded-logs.index');
    }

    public function edit(RecordedLog $recordedLog)
    {
        abort_if(Gate::denies('recorded_log_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $recordedLog->load('tvde_week', 'driver', 'company');

        $recordedLog->load('tvde_week', 'driver', 'company', 'vehicle_item');

        return view('admin.recordedLogs.edit', compact('companies', 'drivers', 'recordedLog', 'tvde_weeks', 'recordedLog'));
    }

    public function update(UpdateRecordedLogRequest $request, RecordedLog $recordedLog)
    {
        $recordedLog->update($request->all());

        return redirect()->route('admin.recorded-logs.index');
    }

    public function show(RecordedLog $recordedLog)
    {
        abort_if(Gate::denies('recorded_log_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $recordedLog->load('tvde_week', 'driver', 'company', 'vehicle_item');

        return view('admin.recordedLogs.show', compact('recordedLog'));
    }

    public function destroy(RecordedLog $recordedLog)
    {
        abort_if(Gate::denies('recorded_log_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $recordedLog->delete();

        return back();
    }

    public function massDestroy(MassDestroyRecordedLogRequest $request)
    {
        $recordedLogs = RecordedLog::find(request('ids'));

        foreach ($recordedLogs as $recordedLog) {
            $recordedLog->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
