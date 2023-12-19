<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCurrentAccountRequest;
use App\Http\Requests\StoreCurrentAccountRequest;
use App\Http\Requests\UpdateCurrentAccountRequest;
use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrentAccountController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('current_account_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currentAccounts = CurrentAccount::with(['tvde_week', 'driver'])->get();

        return view('admin.currentAccounts.index', compact('currentAccounts'));
    }

    public function create()
    {
        abort_if(Gate::denies('current_account_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.currentAccounts.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreCurrentAccountRequest $request)
    {
        $currentAccount = CurrentAccount::create($request->all());

        return redirect()->route('admin.current-accounts.index');
    }

    public function edit(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $currentAccount->load('tvde_week', 'driver');

        return view('admin.currentAccounts.edit', compact('currentAccount', 'drivers', 'tvde_weeks'));
    }

    public function update(UpdateCurrentAccountRequest $request, CurrentAccount $currentAccount)
    {
        $currentAccount->update($request->all());

        return redirect()->route('admin.current-accounts.index');
    }

    public function show(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currentAccount->load('tvde_week', 'driver');

        return view('admin.currentAccounts.show', compact('currentAccount'));
    }

    public function destroy(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currentAccount->delete();

        return back();
    }

    public function massDestroy(MassDestroyCurrentAccountRequest $request)
    {
        $currentAccounts = CurrentAccount::find(request('ids'));

        foreach ($currentAccounts as $currentAccount) {
            $currentAccount->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
