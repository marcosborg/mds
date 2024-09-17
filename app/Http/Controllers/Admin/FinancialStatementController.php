<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver;
use App\Models\TvdeActivity;
use App\Models\TvdeMonth;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Traits\Reports;
use Auth;
use Carbon\Carbon;
use App\Models\TvdeYear;
use App\Models\RecordedLog;

class FinancialStatementController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('financial_statement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //MANAGE SESSION

        $driver_id = session()->get('driver_id') ? session()->get('driver_id') : $driver_id = 0;

        if (!auth()->user()->hasRole('Admin') && $driver_id == 0) {
            $driver_id = Driver::where('user_id', auth()->user()->id)->first()->id;
            session()->put('driver_id', $driver_id);
        }

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_week = $filter['tvde_week'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $drivers = $filter['drivers'];

        $user = auth()->user();

        if ($user->hasRole('driver') && !$user->hasRole('owner')) {
            $drivers = Driver::where('driver_id', session()->get('user_id'));
        } else if ($user->hasRole('owner') && !$user->hasRole('admin')) {
            if (Gate::allows('owner_access')) {
                $drivers = Driver::where('company_id', $company_id)
                    ->where('state_id', 1)
                    ->whereHas('user', function ($user) {
                        $user->where('id', Auth::id());
                    })
                    ->orderBy('name')
                    ->get()->load('user');
            }
        }

        //START SCRIPT

        $recorded_log = RecordedLog::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id,
            'company_id' => $company_id,
        ])->first();

        if ($recorded_log) {
            $results = json_decode($recorded_log->data);
            $data = [
                'company_id' => $company_id,
                'tvde_year_id' => $tvde_year_id,
                'tvde_years' => $tvde_years,
                'tvde_months' => $tvde_months,
                'tvde_month_id' => $tvde_month_id,
                'tvde_weeks' => $tvde_weeks,
                'tvde_week_id' => $tvde_week_id,
                'drivers' => $drivers,
                'driver_id' => $driver_id,
                'bolt_activities' => $results->bolt_activities,
                'uber_activities' => $results->uber_activities,
                'total_earnings_uber' => $results->total_earnings_uber,
                'contract_type_rank' => $results->contract_type_rank,
                'total_uber' => $results->total_uber,
                'total_earnings_bolt' => $results->total_earnings_bolt,
                'total_bolt' => $results->total_bolt,
                'total_tips_uber' => $results->total_tips_uber,
                'uber_tip_percent' => $results->uber_tip_percent,
                'uber_tip_after_vat' => $results->uber_tip_after_vat,
                'total_tips_bolt' => $results->total_tips_bolt,
                'bolt_tip_percent' => $results->bolt_tip_percent,
                'bolt_tip_after_vat' => $results->bolt_tip_after_vat,
                'total_tips' => $results->total_tips,
                'total_tip_after_vat' => $results->total_tip_after_vat,
                'adjustments' => $results->adjustments,
                'total_earnings' => $results->total_earnings,
                'total_earnings_no_tip' => $results->total_earnings_no_tip,
                'total' => $results->total,
                'total_after_vat' => $results->total_after_vat,
                'gross_credits' => $results->gross_credits,
                'gross_debts' => $results->gross_debts,
                'final_total' => $results->final_total,
                'driver' => $results->driver,
                'electric_expenses' => $results->electric_expenses,
                'combustion_expenses' => $results->combustion_expenses,
                'combustion_racio' => $results->combustion_racio,
                'electric_racio' => $results->electric_racio,
                'total_earnings_after_vat' => $results->total_earnings_after_vat,
                'txt_admin' => $results->txt_admin,
                'toll_payments' => $results->toll_payments,
                'recorded' => true,
            ];
        } else {
            $results = $this->getWeekResults($tvde_week_id, $driver_id, $company_id);
            $data = [
                'company_id' => $company_id,
                'tvde_year_id' => $tvde_year_id,
                'tvde_years' => $tvde_years,
                'tvde_months' => $tvde_months,
                'tvde_month_id' => $tvde_month_id,
                'tvde_weeks' => $tvde_weeks,
                'tvde_week_id' => $tvde_week_id,
                'drivers' => $drivers,
                'driver_id' => $driver_id,
                'bolt_activities' => $results['bolt_activities'],
                'uber_activities' => $results['uber_activities'],
                'total_earnings_uber' => $results['total_earnings_uber'],
                'contract_type_rank' => $results['contract_type_rank'],
                'total_uber' => $results['total_uber'],
                'total_earnings_bolt' => $results['total_earnings_bolt'],
                'total_bolt' => $results['total_bolt'],
                'total_tips_uber' => $results['total_tips_uber'],
                'uber_tip_percent' => $results['uber_tip_percent'],
                'uber_tip_after_vat' => $results['uber_tip_after_vat'],
                'total_tips_bolt' => $results['total_tips_bolt'],
                'bolt_tip_percent' => $results['bolt_tip_percent'],
                'bolt_tip_after_vat' => $results['bolt_tip_after_vat'],
                'total_tips' => $results['total_tips'],
                'total_tip_after_vat' => $results['total_tip_after_vat'],
                'adjustments' => $results['adjustments'],
                'total_earnings' => $results['total_earnings'],
                'total_earnings_no_tip' => $results['total_earnings_no_tip'],
                'total' => $results['total'],
                'total_after_vat' => $results['total_after_vat'],
                'gross_credits' => $results['gross_credits'],
                'gross_debts' => $results['gross_debts'],
                'final_total' => $results['final_total'],
                'driver' => $results['driver'],
                'electric_expenses' => $results['electric_expenses'],
                'combustion_expenses' => $results['combustion_expenses'],
                'combustion_racio' => $results['combustion_racio'],
                'electric_racio' => $results['electric_racio'],
                'total_earnings_after_vat' => $results['total_earnings_after_vat'],
                'txt_admin' => $results['txt_admin'],
                'toll_payments' => $results['toll_payments'],
                'recorded' => false,
            ];
        }

        // END SCRIPT

        return view('admin.financialStatements.index')->with($data);
    }

    public function year($tvde_year_id)
    {

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $year = TvdeYear::find($tvde_year_id);

        session()->put('tvde_year_id', $tvde_year_id);

        if ($year->name == $currentYear) {
            $month = TvdeMonth::where([
                'year_id' => $tvde_year_id,
                'number' => $currentMonth
            ])->first();
        } else {
            $month = TvdeMonth::where([
                'year_id' => $tvde_year_id
            ])->orderBy('number', 'desc')->first();
        }

        session()->put('tvde_month_id', $month->id);
        session()->put('tvde_week_id', TvdeWeek::orderBy('number', 'desc')->where('tvde_month_id', session()->get('tvde_month_id'))->first()->id);
        return back();
    }

    public function month($tvde_month_id)
    {
        session()->put('tvde_month_id', $tvde_month_id);
        session()->put('tvde_week_id', TvdeWeek::orderBy('number', 'desc')->where('tvde_month_id', $tvde_month_id)->first()->id);
        return back();
    }

    public function week($tvde_week_id)
    {
        session()->put('tvde_week_id', $tvde_week_id);
        return back();
    }

    public function driver($driver_id)
    {
        session()->put('driver_id', $driver_id);
        return back();
    }

    public function pdf()
    {
        $driver_id = session()->get('driver_id');
        $company_id = session()->get('company_id');
        $tvde_week_id = session()->get('tvde_week_id');

        $all_html = '';

        $html = $this->return_pdf($company_id, $tvde_week_id, $driver_id);
        $all_html .= "<div class='page'>{$html}</div>";

        $all_html = "<html><head><style>.page { page-break-after: always; }</style></head><body>{$all_html}</body></html>";

        $pdf = Pdf::loadHtml($all_html);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('financial_statement.pdf');
    }

    public function return_pdf($company_id, $tvde_week_id, $driver_id)
    {

        $driver = Driver::find($driver_id);
        $company = Company::find($company_id);
        $tvde_week = TvdeWeek::find($tvde_week_id);

        $drivers = Driver::where('company_id', $company_id)->get();

        $team_earnings = collect();

        foreach ($drivers as $key => $d) {
            $team_driver_bolt_earnings = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 2,
                'driver_code' => $d->bolt_name
            ])
                ->get()->sum('earnings_two');

            $team_driver_uber_earnings = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 1,
                'driver_code' => $d->uber_uuid
            ])
                ->get()->sum('earnings_two');

            $team_driver_earnings = $team_driver_bolt_earnings + $team_driver_uber_earnings;
            if ($driver) {
                $entry = collect([
                    'driver' => $driver->uber_uuid == $d->uber_uuid || $driver->bolt_name == $d->bolt_name ? $driver->name : 'Motorista ' . $key + 1,
                    'earnings' => sprintf("%.2f", $team_driver_earnings),
                    'own' => $driver->uber_uuid == $d->uber_uuid || $driver->bolt_name == $d->bolt_name
                ]);
                $team_earnings->add($entry);
            }

            $labels = [];
            $earnings = [];
            $backgrounds = [];

            foreach ($team_earnings as $entry) {
                $labels[] = $entry['driver'];
                $earnings[] = $entry['earnings'];
                if ($entry['own']) {
                    $backgrounds[] = '#605ca8';
                } else {
                    $backgrounds[] = '#00a65a94';
                }
            }
        }

        //START SCRIPT

        $recorded_log = RecordedLog::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id,
            'company_id' => $company_id,
        ])->first();

        if ($recorded_log) {
            $results = json_decode($recorded_log->data);
            $chart1 = "https://quickchart.io/chart?c={type:'bar',data:{labels:" . json_encode($labels) . ",datasets:[{borderWidth: 1, label:'Valor faturado',data:" . json_encode($earnings) . "}]}}";
            $chart2 = "https://quickchart.io/chart?c={type:'doughnut',data:{labels:['UBER', 'BOLT', 'GORJETAS'],datasets:[{label: 'Valor faturado', data: [" . $results->total_earnings_uber . ", " . $results->total_earnings_bolt . ", " . $results->total_tips . "]}]}}";
            $data = [
                'company_id' => $company_id,
                'tvde_week_id' => $tvde_week_id,
                'drivers' => $drivers,
                'driver_id' => $driver_id,
                'bolt_activities' => $results->bolt_activities,
                'uber_activities' => $results->uber_activities,
                'total_earnings_uber' => $results->total_earnings_uber,
                'contract_type_rank' => $results->contract_type_rank,
                'total_uber' => $results->total_uber,
                'total_earnings_bolt' => $results->total_earnings_bolt,
                'total_bolt' => $results->total_bolt,
                'total_tips_uber' => $results->total_tips_uber,
                'uber_tip_percent' => $results->uber_tip_percent,
                'uber_tip_after_vat' => $results->uber_tip_after_vat,
                'total_tips_bolt' => $results->total_tips_bolt,
                'bolt_tip_percent' => $results->bolt_tip_percent,
                'bolt_tip_after_vat' => $results->bolt_tip_after_vat,
                'total_tips' => $results->total_tips,
                'total_tip_after_vat' => $results->total_tip_after_vat,
                'adjustments' => $results->adjustments,
                'total_earnings' => $results->total_earnings,
                'total_earnings_no_tip' => $results->total_earnings_no_tip,
                'total' => $results->total,
                'total_after_vat' => $results->total_after_vat,
                'gross_credits' => $results->gross_credits,
                'gross_debts' => $results->gross_debts,
                'final_total' => $results->final_total,
                'driver' => $results->driver,
                'electric_expenses' => $results->electric_expenses,
                'combustion_expenses' => $results->combustion_expenses,
                'combustion_racio' => $results->combustion_racio,
                'electric_racio' => $results->electric_racio,
                'total_earnings_after_vat' => $results->total_earnings_after_vat,
                'txt_admin' => $results->txt_admin,
                'toll_payments' => $results->toll_payments,
                'recorded' => true,
                'chart1' => $chart1,
                'chart2' => $chart2,
                'company' => $company,
                'tvde_week' => $tvde_week
            ];
        } else {
            $results = $this->getWeekResults($tvde_week_id, $driver_id, $company_id);
            $chart1 = "https://quickchart.io/chart?c={type:'bar',data:{labels:" . json_encode($labels) . ",datasets:[{borderWidth: 1, label:'Valor faturado',data:" . json_encode($earnings) . "}]}}";
            $chart2 = "https://quickchart.io/chart?c={type:'doughnut',data:{labels:['UBER', 'BOLT', 'GORJETAS'],datasets:[{label: 'Valor faturado', data: [" . $results['total_earnings_uber'] . ", " . $results['total_earnings_bolt'] . ", " . $results['total_tips'] . "]}]}}";
            $data = [
                'company_id' => $company_id,
                'tvde_week_id' => $tvde_week_id,
                'drivers' => $drivers,
                'driver_id' => $driver_id,
                'bolt_activities' => $results['bolt_activities'],
                'uber_activities' => $results['uber_activities'],
                'total_earnings_uber' => $results['total_earnings_uber'],
                'contract_type_rank' => $results['contract_type_rank'],
                'total_uber' => $results['total_uber'],
                'total_earnings_bolt' => $results['total_earnings_bolt'],
                'total_bolt' => $results['total_bolt'],
                'total_tips_uber' => $results['total_tips_uber'],
                'uber_tip_percent' => $results['uber_tip_percent'],
                'uber_tip_after_vat' => $results['uber_tip_after_vat'],
                'total_tips_bolt' => $results['total_tips_bolt'],
                'bolt_tip_percent' => $results['bolt_tip_percent'],
                'bolt_tip_after_vat' => $results['bolt_tip_after_vat'],
                'total_tips' => $results['total_tips'],
                'total_tip_after_vat' => $results['total_tip_after_vat'],
                'adjustments' => $results['adjustments'],
                'total_earnings' => $results['total_earnings'],
                'total_earnings_no_tip' => $results['total_earnings_no_tip'],
                'total' => $results['total'],
                'total_after_vat' => $results['total_after_vat'],
                'gross_credits' => $results['gross_credits'],
                'gross_debts' => $results['gross_debts'],
                'final_total' => $results['final_total'],
                'driver' => $results['driver'],
                'electric_expenses' => $results['electric_expenses'],
                'combustion_expenses' => $results['combustion_expenses'],
                'combustion_racio' => $results['combustion_racio'],
                'electric_racio' => $results['electric_racio'],
                'total_earnings_after_vat' => $results['total_earnings_after_vat'],
                'txt_admin' => $results['txt_admin'],
                'toll_payments' => $results['toll_payments'],
                'recorded' => false,
                'chart1' => $chart1,
                'chart2' => $chart2,
                'company' => $company,
                'tvde_week' => $tvde_week
            ];
        }

        //GRAFICOS


        $html = view('admin.financialStatements.pdf', $data)->render();

        return $html;
    }
}
