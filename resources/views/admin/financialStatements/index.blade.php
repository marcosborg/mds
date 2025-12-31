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
                    class="period-link {{ $tvde_week->id == $tvde_week_id ? 'is-active' : '' }}">Semana {{ isset($tvde_week->number) ? $tvde_week->number . ' - ' : '' }}de
                    {{ \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m') }} a
                    {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m') }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="board-main">
        <aside class="drivers-stack">
            <div class="drivers-title">Motoristas</div>
            @php
                $selectedDriverLabel = $driver ? strtoupper($driver->name) : 'Todos';
            @endphp
            <div class="driver-filter" data-driver-filter>
                <button class="driver-filter-toggle" type="button" aria-expanded="false" data-driver-toggle>
                    <span class="driver-filter-current">{{ $selectedDriverLabel }}</span>
                </button>
                <div class="driver-filter-panel" hidden data-driver-panel>
                    <input class="driver-filter-search" type="text" placeholder="Procurar" data-driver-search>
                    <div class="driver-filter-options" data-driver-options>
                        @if(auth()->user()->hasRole('admin'))
                        <a href="/admin/financial-statements/driver/0"
                            class="driver-option {{ $driver_id == null || $driver_id == 0 ? 'is-selected' : '' }}"
                            data-driver-label="Todos">Todos</a>
                        @endif
                        @foreach ($drivers as $d)
                        <a href="/admin/financial-statements/driver/{{ $d->id }}"
                            class="driver-option {{ $driver_id == $d->id ? 'is-selected' : '' }}"
                            data-driver-label="{{ strtoupper($d->name) }}">{{ strtoupper($d->name) }}</a>
                        @endforeach
                    </div>
                </div>
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
                                        <td>{{ $total_earnings_uber }}€</td>
                                        @if ($driver)
                                        <td>{{ $contract_type_rank ? $contract_type_rank->percent : '' }}%</td>
                                        <td>{{ $total_uber }}€</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>BOLT</th>
                                        <td>{{ $total_earnings_bolt }}€</td>
                                        @if ($driver)
                                        <td>{{ $contract_type_rank ? $contract_type_rank->percent : '' }}%</td>
                                        <td>{{ $total_bolt }}€</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjeta UBER</th>
                                        <td>{{ $total_tips_uber }}€</td>
                                        @if ($driver)
                                        <td>{{ $uber_tip_percent }}%</td>
                                        <td>{{ $uber_tip_after_vat }}€</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjeta BOLT</th>
                                        <td>{{ $total_tips_bolt }}€</td>
                                        @if ($driver)
                                        <td>{{ $bolt_tip_percent }}%</td>
                                        <td>{{ $bolt_tip_after_vat }}€</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Totais</th>
                                        <td>{{ $total_earnings }}€</td>
                                        @if ($driver)
                                        <td></td>
                                        <td>{{ number_format($total_after_vat, 2) }}€</td>
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
                                        <td>{{ number_format($total_earnings_no_tip, 2) }}€</td>
                                        @if ($driver)
                                        <td>- {{ number_format($total_earnings_no_tip - $total_earnings_after_vat, 2) }}€</td>
                                        <td>{{ number_format($total_earnings_after_vat, 2) }}€</td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Gorjetas</th>
                                        <td>{{ number_format($total_tips, 2) }}€</td>
                                        @if ($driver)
                                        <td>- {{ number_format($total_tips - $total_tip_after_vat, 2) }}€</td>
                                        <td>{{ number_format($total_tip_after_vat, 2) }}€</td>
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
                                        <td>- {{ number_format($toll_payments->sum('total'), 2) }}€</td>
                                        <td></td>
                                    </tr>
                                    @elseif ($toll_payments)
                                    <tr>
                                        <th>Portagens</th>
                                        <td></td>
                                        <td>- {{ number_format(array_sum(array_column($toll_payments, 'total')), 2) }}€</td>
                                        <td></td>
                                    </tr>
                                    @endif
                                    @foreach ($adjustments as $adjustment)
                                    <tr>
                                        <th>{{ $adjustment->name }}</th>
                                        <td>{{ $adjustment->type == 'refund' ? $adjustment->amount . '€' : '' }}</td>
                                        <td>{{ $adjustment->type == 'deduct' ? '- ' . $adjustment->amount . '€' : '' }}</td>
                                        <td></td>
                                    </tr>
                                    @endforeach
                                    @if ($txt_admin > 0)
                                    <tr>
                                        <th>Taxa administrativa</th>
                                        <td></td>
                                        <td>- {{ number_format($txt_admin, 2) }}€</td>
                                        <td></td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th>Totais</th>
                                        <th style="text-align: right;">{{ number_format($gross_credits, 2) }}€</th>
                                        @if ($driver)
                                        <th style="text-align: right;">- {{ number_format($gross_debts, 2) }}€</th>
                                        <th style="text-align: right;">{{ number_format($final_total, 2) }}€</th>
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
                    <h3 class="pay-text">Valor a pagar: <span class="pay-amount">{{ number_format($final_total, 2) }}</span>€</h3>
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
        overflow: hidden;
    }

    .period-label {
        font-size: 22px;
        font-weight: 700;
        text-transform: uppercase;
        color: inherit;
    }

    .period-links {
        margin-top: 8px;
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        justify-content: flex-start;
        width: 100%;
        white-space: nowrap;
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

    .driver-filter {
        position: relative;
    }

    .driver-filter-toggle {
        width: 100%;
        border: 1px solid #cdd3d8;
        background: #fff;
        padding: 10px 12px;
        border-radius: 4px;
        font-weight: 700;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
    }

    .driver-filter-toggle:hover {
        background: #f1f3f5;
    }

    .driver-filter-panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #cdd3d8;
        border-radius: 6px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        padding: 10px;
        z-index: 5;
    }

    .driver-filter-search {
        width: 100%;
        border: 1px solid #cdd3d8;
        border-radius: 4px;
        padding: 6px 8px;
        margin-bottom: 8px;
    }

    .driver-filter-options {
        max-height: 280px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .driver-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 600;
        color: inherit;
    }

    .driver-option:hover {
        background: #f1f3f5;
        text-decoration: none;
    }

    .driver-option::before {
        content: "•";
        font-size: 16px;
        line-height: 1;
        opacity: 0.25;
    }

    .driver-option.is-selected::before {
        content: "✓";
        opacity: 1;
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
    (function () {
        var filter = document.querySelector('[data-driver-filter]');
        if (!filter) {
            return;
        }
        var toggle = filter.querySelector('[data-driver-toggle]');
        var panel = filter.querySelector('[data-driver-panel]');
        var search = filter.querySelector('[data-driver-search]');
        var options = filter.querySelector('[data-driver-options]');

        function closePanel () {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var isHidden = panel.hidden;
            panel.hidden = !isHidden;
            toggle.setAttribute('aria-expanded', String(isHidden));
            if (!panel.hidden && search) {
                search.focus();
                search.select();
            }
        });

        document.addEventListener('click', function (event) {
            if (!filter.contains(event.target)) {
                closePanel();
            }
        });

        if (search) {
            search.addEventListener('input', function () {
                var term = search.value.toUpperCase();
                var items = options.querySelectorAll('[data-driver-label]');
                items.forEach(function (item) {
                    var label = item.getAttribute('data-driver-label') || '';
                    item.style.display = label.indexOf(term) !== -1 ? '' : 'none';
                });
            });
        }
    })();

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
