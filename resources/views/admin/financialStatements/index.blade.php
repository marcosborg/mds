@extends('layouts.admin')
@section('content')
<div class="content financial-board">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os seus extratos.
    </div>
    @else
    <div class="period-bar">
        <div class="period-block">
            <div class="period-label">ANO</div>
            <div class="period-links">
                @foreach ($tvde_years as $tvde_year)
                <a href="/admin/financial-statements/year/{{ $tvde_year->id }}"
                    class="period-link {{ $tvde_year->id == $tvde_year_id ? 'is-active' : '' }}">{{ $tvde_year->name }}</a>
                @endforeach
            </div>
        </div>
        <div class="period-block">
            <div class="period-label">M&Ecirc;S</div>
            <div class="period-links">
                @foreach ($tvde_months as $tvde_month)
                <a href="/admin/financial-statements/month/{{ $tvde_month->id }}"
                    class="period-link {{ $tvde_month->id == $tvde_month_id ? 'is-active' : '' }}">{{ strtoupper($tvde_month->name) }}</a>
                @endforeach
            </div>
        </div>
        <div class="period-block">
            <div class="period-label">SEMANA</div>
            <div class="period-links">
                @foreach ($tvde_weeks as $tvde_week)
                <a href="/admin/financial-statements/week/{{ $tvde_week->id }}"
                    class="period-link {{ $tvde_week->id == $tvde_week_id ? 'is-active' : '' }}">Semana de
                    {{ \Carbon\Carbon::parse($tvde_week->start_date)->format('d') }} a
                    {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="board-main">
        <aside class="drivers-stack">
            <div class="drivers-title">Motoristas</div>
            <div class="drivers-list">
                @if(auth()->user()->hasRole('admin'))
                <a href="/admin/financial-statements/driver/0"
                    class="driver-btn {{ $driver_id == null ? 'is-active' : '' }}">Todos</a>
                @endif
                @foreach ($drivers as $d)
                <a href="/admin/financial-statements/driver/{{ $d->id }}"
                    class="driver-btn {{ $driver_id == $d->id ? 'is-active' : '' }}">{{ strtoupper($d->name) }}</a>
                @endforeach
            </div>
        </aside>

        <div class="board-right">
            <div class="row tables-row">
                <div class="col-md-6">
                    <div class="panel panel-default table-panel">
                        <div class="panel-heading">
                            Atividades por operador
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th>UBER</th>
                                        <td>{{ $total_earnings_uber }}&euro;</td>
                                        @if ($driver)
                                        <td>{{ $contract_type_rank ? $contract_type_rank->percent : '' }}%</td>
                                        <td>{{ $total_uber }}&euro;</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>BOLT</th>
                                        <td>{{ $total_earnings_bolt }}&euro;</td>
                                        @if ($driver)
                                        <td>{{ $contract_type_rank ? $contract_type_rank->percent : '' }}%</td>
                                        <td>{{ $total_bolt }}&euro;</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjeta UBER</th>
                                        <td>{{ $total_tips_uber }}&euro;</td>
                                        @if ($driver)
                                        <td>{{ $uber_tip_percent }}%</td>
                                        <td>{{ $uber_tip_after_vat }}&euro;</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjeta BOLT</th>
                                        <td>{{ $total_tips_bolt }}&euro;</td>
                                        @if ($driver)
                                        <td>{{ $bolt_tip_percent }}%</td>
                                        <td>{{ $bolt_tip_after_vat }}&euro;</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Totais</th>
                                        <td>{{ $total_earnings }}&euro;</td>
                                        @if ($driver)
                                        <td></td>
                                        <td>{{ number_format($total_after_vat, 2) }}&euro;</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default table-panel">
                        <div class="panel-heading">
                            Totais
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th></th>
                                        <th style="text-align: right;">Cr&eacute;ditos</th>
                                        @if ($driver)
                                        <th style="text-align: right;">D&eacute;bitos</th>
                                        <th style="text-align: right;">Totais</th>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Ganhos</th>
                                        <td>{{ number_format($total_earnings_no_tip, 2) }}&euro;</td>
                                        @if ($driver)
                                        <td>- {{ number_format($total_earnings_no_tip - $total_earnings_after_vat, 2) }}&euro;</td>
                                        <td>{{ number_format($total_earnings_after_vat, 2) }}&euro;</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjetas</th>
                                        <td>{{ number_format($total_tips, 2) }}&euro;</td>
                                        @if ($driver)
                                        <td>- {{ number_format($total_tips - $total_tip_after_vat, 2) }}&euro;</td>
                                        <td>{{ number_format($total_tip_after_vat, 2) }}&euro;</td>
                                        @endif
                                    </tr>
                                    @if (isset($electric_expenses) && is_object($electric_expenses) && isset($electric_expenses->value)
                                    && $electric_expenses->value > 0)
                                    <tr>
                                        <th>Abastecimento el&eacute;trico</th>
                                        <td></td>
                                        @if ($driver)
                                        <td>- {{ $electric_expenses->total }}</td>
                                        <td></td>
                                        @endif
                                    </tr>
                                    @elseif (isset($electric_expenses) && is_array($electric_expenses) && isset($electric_expenses['value']) && $electric_expenses['value'] > 0)
                                    <tr>
                                        <th>Abastecimento el&eacute;trico</th>
                                        <td></td>
                                        @if ($driver)
                                        <td>- {{ $electric_expenses['total'] }}</td>
                                        <td></td>
                                        @endif
                                    </tr>
                                    @endif
                                    @if ($combustion_expenses && is_object($combustion_expenses) &&
                                    isset($combustion_expenses->value))
                                    <tr>
                                        <th>Abastecimento combustivel</th>
                                        <td></td>
                                        @if ($driver)
                                        <td>- {{ $combustion_expenses->total }}</td>
                                        <td></td>
                                        @endif
                                    </tr>
                                    @elseif (isset($combustion_expenses) && is_array($combustion_expenses) && isset($combustion_expenses['value']) && $combustion_expenses['value'] > 0)
                                    <tr>
                                        <th>Abastecimento combustivel</th>
                                        <td></td>
                                        @if ($driver)
                                        <td>- {{ $combustion_expenses['total'] }}</td>
                                        <td></td>
                                        @endif
                                    </tr>
                                    @endif

                                    @if ($toll_payments && is_object($toll_payments))
                                    <tr>
                                        <th>Portagens</th>
                                        <td></td>
                                        <td>- {{ number_format($toll_payments->sum('total'), 2) }}&euro;</td>
                                        <td></td>
                                    </tr>
                                    @elseif ($toll_payments)
                                    <tr>
                                        <th>Portagens</th>
                                        <td></td>
                                        <td>- {{ number_format(array_sum(array_column($toll_payments, 'total')), 2) }}&euro;</td>
                                        <td></td>
                                    </tr>
                                    @endif
                                    @foreach ($adjustments as $adjustment)
                                    <tr>
                                        <th>{{ $adjustment->name }}</th>
                                        <td>{{ $adjustment->type == 'refund' ? $adjustment->amount . '&euro;' : '' }}</td>
                                        <td>{{ $adjustment->type == 'deduct' ? '- ' . $adjustment->amount . '&euro;' : '' }}</td>
                                        <td></td>
                                    </tr>
                                    @endforeach
                                    @if ($txt_admin > 0)
                                    <tr>
                                        <th>Taxa administrativa</th>
                                        <td></td>
                                        <td>- {{ number_format($txt_admin, 2) }}&euro;</td>
                                        <td></td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th>Totais</th>
                                        <th style="text-align: right;">{{ number_format($gross_credits, 2) }}&euro;</th>
                                        @if ($driver)
                                        <th style="text-align: right;">- {{ number_format($gross_debts, 2) }}&euro;</th>
                                        <th style="text-align: right;">{{ number_format($final_total, 2) }}&euro;</th>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if ($driver_id)
            <div class="panel panel-default pay-panel">
                <div class="panel-body">
                    <h3 class="pay-text">Valor a pagar: <span class="pay-amount">{{ number_format($final_total, 2) }}</span>&euro;</h3>
                    <div class="pay-actions">
                        <button class="btn btn-success"
                            onclick="recordLog({{ $tvde_week_id }}, {{ $driver_id }}, {{ $company_id }}, {{ number_format($final_total, 2, '.', '') }})"><i
                                class="fa fa-floppy-o"></i></button>
                        @if ($recorded)
                        <a target="_new" href="/admin/financial-statements/pdf" class="btn btn-danger"><i
                                class="fa fa-file-pdf-o"></i></a>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="panel panel-default pay-panel">
                <div class="panel-body">
                    <div class="pay-actions" style="margin-left: auto;">
                        <a href="/admin/print-alls" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i> Imprimir todos</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
@section('styles')
<style>
    .financial-board {
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-family: inherit;
        color: inherit;
    }

    .period-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .period-block {
        border: 1px solid #dcdfe3;
        padding: 12px 14px;
        background: #fff;
        text-align: center;
    }

    .period-label {
        font-size: 22px;
        font-weight: 700;
        text-transform: uppercase;
        color: inherit;
    }

    .period-links {
        margin-top: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }

    .period-link {
        border: 1px solid #cdd3d8;
        padding: 6px 10px;
        font-weight: 600;
        text-transform: uppercase;
        color: inherit;
        background: #f8f9fa;
        display: inline-block;
        border-radius: 4px;
    }

    .period-link:hover {
        text-decoration: none;
        background: #e9ecef;
    }

    .period-link.is-active {
        background: #428bca;
        color: #fff;
        border-color: #428bca;
        pointer-events: none;
    }

    .board-main {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 14px;
    }

    .drivers-stack {
        border: 1px solid #dcdfe3;
        padding: 12px;
        background: #fff;
    }

    .drivers-title {
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 12px;
        color: inherit;
    }

    .drivers-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .driver-btn {
        display: block;
        border: 1px solid #cdd3d8;
        padding: 10px;
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
        color: inherit;
        background: #fff;
        border-radius: 4px;
    }

    .driver-btn:hover {
        text-decoration: none;
        background: #f1f3f5;
    }

    .driver-btn.is-active {
        background: #428bca;
        color: #fff;
        border-color: #428bca;
        pointer-events: none;
    }

    .board-right {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .tables-row {
        margin: 0;
    }

    .tables-row .col-md-6 {
        padding-left: 0;
        padding-right: 12px;
    }

    .tables-row .col-md-6:last-child {
        padding-right: 0;
        padding-left: 12px;
    }

    .table-panel .panel-heading {
        font-weight: 700;
    }

    td {
        text-align: right;
    }

    table {
        font-size: 13px;
    }

    .pay-panel .panel-body {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .pay-text {
        margin: 0;
        font-weight: 600;
        color: inherit;
        text-align: right;
    }

    .pay-amount {
        font-weight: 800;
    }

    .pay-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    @media (max-width: 991px) {
        .board-main {
            grid-template-columns: 1fr;
        }

        .tables-row .col-md-6 {
            padding: 0 0 12px 0;
        }

        .tables-row .col-md-6:last-child {
            padding: 0;
        }
    }
</style>
@endsection
@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js">
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function recordLog (tvde_week_id, driver_id, company_id, value) {
        Swal.fire({
            title: "Tem a certeza?",
            text: "Os dados atuais vao se sobrepor aos anteriores!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sim, podes alterar!"
            }).then((result) => {
            if (result.isConfirmed) {
                $.LoadingOverlay('show');
                $.get('/admin/record-log/' + tvde_week_id + '/' + driver_id + '/' + company_id + '/' + value).then((resp) => {
                    $.LoadingOverlay('hide');
                    Swal.fire({
                        title: "Alterado!",
                        text: "Pode continuar.",
                        icon: "success"
                    }).then(() => {
                        location.reload();
                    });
                }, (err) => {
                    $.LoadingOverlay('hide');
                    console.log(err);
                });
            }
        });
    }
</script>
@endsection
