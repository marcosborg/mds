<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Driver;
use App\Models\Company;
use App\Models\TvdeWeek;
use App\Models\TvdeActivity;
use App\Models\Adjustment;
use App\Models\Electric;
use App\Models\ElectricTransaction;
use App\Models\Card;
use App\Models\CombustionTransaction;
use App\Models\TollCard;
use App\Models\TollPayment;
use App\Models\ContractTypeRank;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Traits\Reports;

class PrintAllController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('print_all_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        set_time_limit(600);

        $filter = $this->filter(true);
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_week = $filter['tvde_week'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $drivers = $filter['drivers'];

        return view('admin.printAlls.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $drivers
        ]);

    }

    public function driver($driver_id)
    {
        $company_id = session()->get('company_id');
        $tvde_week_id = session()->get('tvde_week_id');

        $all_html = '';

        $html = $this->pdf($company_id, $tvde_week_id, $driver_id);
        $all_html .= "<div class='page'>{$html}</div>";

        $all_html = "<html><head><style>.page { page-break-after: always; }</style></head><body>{$all_html}</body></html>";

        $pdf = Pdf::loadHtml($all_html);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('financial_statement.pdf');
    }

    public function alldrivers()
    {

        set_time_limit(600);

        $company_id = session()->get('company_id');
        $tvde_week_id = session()->get('tvde_week_id');

        $drivers = Driver::where([
            'company_id' => $company_id,
            'state_id' => 1
        ])->get();

        $all_html = '';

        foreach ($drivers as $driver) {
            $driver_id = $driver->id;
            $html = $this->pdf($company_id, $tvde_week_id, $driver_id);
            $all_html .= "<div class='page'>{$html}</div>";
        }

        $all_html = "<html><head><style>.page { page-break-after: always; }</style></head><body>{$all_html}</body></html>";

        $pdf = Pdf::loadHtml($all_html);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('financial_statement.pdf');
    }

    public function pdf($company_id, $tvde_week_id, $driver_id)
    {
        return app(FinancialStatementController::class)->return_pdf($company_id, $tvde_week_id, $driver_id);
    }
}