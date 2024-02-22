<?php

namespace App\Http\Controllers\Admin;

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

        if ($user->hasRole('admin')) {
            session()->forget('driver_id');
        }
        session()->put('company_id', $company_id);
    }
}