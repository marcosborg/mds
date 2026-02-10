<?php

namespace App\Http\Controllers\Admin;

use App\Models\Driver;

class HomeController
{
    public function index()
    {

        if ($user = auth()->user()) {
            $user->load('driver');
            if ($user->driver->count() > 0) {
                session()->put('driver_id', $user->driver[0]->id);
                $user->driver->load('company');
                if ($user->driver[0]->company) {
                    session()->put('company_id', $user->driver[0]->company->id);
                }
            }
        }

        return redirect('admin/financial-statements');
    }

    public function selectCompany($company_id)
    {
        $user = auth()->user();
        $company_id = (int) $company_id;

        if ($user->hasRole('Admin') || $user->hasRole('admin')) {
            session()->forget('driver_id');
            session()->put('company_id', $company_id);
            return;
        }

        $allowedCompanyIds = Driver::where('user_id', $user->id)
            ->whereNotNull('company_id')
            ->distinct()
            ->pluck('company_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        if (count($allowedCompanyIds) === 0) {
            session()->put('company_id', 0);
            session()->put('driver_id', 0);
            return;
        }

        if (!in_array($company_id, $allowedCompanyIds, true)) {
            $company_id = $allowedCompanyIds[0];
        }

        session()->put('company_id', $company_id);
        session()->forget('driver_id');
    }
}
