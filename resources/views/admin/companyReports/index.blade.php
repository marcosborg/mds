@extends('layouts.admin')
@section('styles')
<style>
    table {
        width: 100%;
    }

    tr {
        line-height: 25px;
    }

    tr:nth-child(even) {
        background-color: #eeeeee;
    }

    tr:nth-child(odd) {
        background-color: #ffffff;
    }

</style>
@endsection
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os extratos.
    </div>
    @else
    <div class="btn-group btn-group-justified" role="group">
        @foreach ($tvde_years as $tvde_year)
        <a href="/admin/financial-statements/year/{{ $tvde_year->id }}"
            class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_months as $tvde_month)
        <a href="/admin/financial-statements/month/{{ $tvde_month->id }}"
            class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{
            $tvde_month->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_weeks as $tvde_week)
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}"
            class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
        @endforeach
    </div>

    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading">
            Faturação
        </div>
        <div class="panel-body">
            <table>
                <thead>
                    <tr>
                        <th>Condutor</th>
                        <th style="text-align: right;">Uber</th>
                        <th style="text-align: right;">Bolt</th>
                        <th style="text-align: right;">Operadores</th>
                        <th style="text-align: right;">Ganhos</th>
                        <th style="text-align: right;">Gorjetas</th>
                        <th style="text-align: right;">Abastecimento</th>
                        <th style="text-align: right;">Ajustes</th>
                        <th style="text-align: right;">Txt. Admin.</th>
                        <th style="text-align: right">A pagar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drivers as $driver)
                    @if ($driver->earnings)
                    <tr>
                        <td>{{ $driver->name }}</td>
                        <td style="text-align: right;">{{ number_format($driver->earnings['uber']['total_earnings'] ??
                            0, 2) }} <small>€</small></td>
                        <td style="text-align: right;">{{ number_format($driver->earnings['bolt']['total_earnings'] ??
                            0, 2) }} <small>€</small></td>
                        <td style="text-align: right;">{{ number_format($driver->earnings['total'] ?? 0, 2) }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right;"><small>({{ $driver->earnings['percent'] ?? 0 }}%)</small> {{
                            number_format($driver->earnings['earnings_after_discount'] ??
                            0, 2) }} <small>€</small></td>
                        <td style="text-align: right;"><small>({{ $driver->earnings['tips_percent'] ?? 0 }})%</small> {{
                            number_format($driver->earnings['tips_after_discount'] ?? 0, 2) }} <small>€</small></td>
                        <td style="text-align: right;">{{ number_format($driver->fuel, 2) }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right">{{ number_format($driver->adjustments, 2) }} <small>€</small></td>
                        <td style="text-align: right">{{ number_format($driver->earnings['txt_admin'], 2) }} <small>€</small>
                        </td>
                        <td style="text-align: right">{{ number_format($driver->total, 2) }} <small>€</small></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totais</th>
                        <th style="text-align: right;">{{ number_format($totals['total_uber'], 2) }} <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_bolt'], 2) }} <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_operators'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_earnings_after_discount'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_tips_after_discount'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_fuel_transactions'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_adjustments'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_fleet_management'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_drivers'], 2) }} <small>€</small>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endif
</div>
@endsection