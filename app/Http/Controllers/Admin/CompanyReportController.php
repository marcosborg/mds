<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use Illuminate\Http\Request;
use App\Models\TvdeWeek;

class CompanyReportController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $company_id = session()->get('company_id');

        if ($company_id) {
            $latestWeek = TvdeWeek::whereHas('tvdeActivities', function ($activity) use ($company_id) {
                $activity->where('company_id', $company_id);
            })
                ->with('tvde_month')
                ->orderBy('start_date', 'desc')
                ->orderBy('number', 'desc')
                ->first();

            if ($latestWeek) {
                session()->put('tvde_week_id', $latestWeek->id);
                session()->put('tvde_month_id', $latestWeek->tvde_month_id);
                session()->put('tvde_year_id', $latestWeek->tvde_month?->year_id);
            }
        }

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $results = $this->getWeekReport($company_id, $tvde_week_id);

        return view('admin.companyReports.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $results['drivers'],
            'totals' => $results['totals']
        ]);

    }

}
