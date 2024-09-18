@extends('layouts.admin')
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os seus extratos.
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
    @if(auth()->user()->hasRole('admin'))
    <a href="/admin/financial-statements/driver/0"
        class="btn btn-default {{ $driver_id == null ? 'disabled selected' : '' }}" style="margin-top: 5px;">Todos</a>
    @endif
    @foreach ($drivers as $d)
    <a href="/admin/financial-statements/driver/{{ $d->id }}"
        class="btn btn-default {{ $driver_id == $d->id ? 'disabled selected' : '' }}" style="margin-top: 5px;">{{
        $d->name }}</a>
    @endforeach

    <div class="row" style="margin-top: 5px;">
        <div class="col-md-6">
            <div class="panel panel-default">
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
            <div class="panel panel-default">
                <div class="panel-heading">
                    Totais
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Créditos</th>
                                @if ($driver)
                                <th style="text-align: right;">Débitos</th>
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
                                <th>Abastecimento elétrico</th>
                                <td></td>
                                @if ($driver)
                                <td>- {{ $electric_expenses->total }}</td>
                                <td></td>
                                @endif
                            </tr>
                            @elseif (isset($electric_expenses) && !is_object($electric_expenses) && isset($electric_expenses['value']) && $electric_expenses['value'] > 0)
                            <tr>
                                <th>Abastecimento elétrico</th>
                                <td></td>
                                @if ($driver)
                                <td>- {{ $electric_expenses['total'] }}</td>
                                <td></td>
                                @endif
                            </tr>
                            @endif
                            @if ($combustion_expenses && is_object($combustion_expenses) &&
                            isset($combustion_expenses->value) && $combustion_expenses->value > 0)
                            <tr>
                                <th>Abastecimento combustivel</th>
                                <td></td>
                                @if ($driver)
                                <td>- {{ $combustion_expenses->total }}</td>
                                <td></td>
                                @endif
                            </tr>
                            @elseif ($combustion_expenses && !is_object($combustion_expenses) && $combustion_expenses['value'] > 0)
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
                                <td>{{ $adjustment->type == 'refund' ? '' . $adjustment->amount . '€' : '' }}</td>
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
            @if ($driver_id)
            <div class="panel panel-default">
                <div class="panel-body">
                    <h3 class="pull-left">Valor a pagar: <span style="font-weight: 800;">{{
                            number_format($final_total, 2) }}</span>€</h3>
                    <div class="pull-right">
                        <button class="btn btn-success"
                            onclick="recordLog({{ $tvde_week_id }}, {{ $driver_id }}, {{ $company_id }}, {{ number_format($final_total, 2) }})"><i
                                class="fa fa-floppy-o"></i></button>
                        @if ($recorded)
                        <a target="_new" href="/admin/financial-statements/pdf" class="btn btn-danger"><i
                                class="fa fa-file-pdf-o"></i></a>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div style="margin-bottom: 20px; text-align: right;">
                <a href="/admin/print-alls" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i> Imprimir todos</a>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
@section('styles')
<style>
    td {
        text-align: right;
    }

    table {
        font-size: 13px;
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
            text: "Os dados atuais vão se sobrepor aos anteriores!",
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