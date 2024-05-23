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
    <div class="row" style="margin-top: 20px;">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-md-6">
                            {{ trans('cruds.printAll.title') }}
                        </div>
                        <div class="col-md-6" style="text-align: right;">
                            <a onclick="return confirm('Este processo demora vários minutos. Espere pacientemente.')"
                                href="/admin/print-alls/alldrivers" target="_new" class="btn btn-primary btn-sm"><i
                                    class="fa fa-file-pdf-o"></i> Imprimir todos</a>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Matrícula</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drivers as $driver)
                            <tr>
                                <th>{{ $driver->id }}</th>
                                <td>{{ $driver->name }}</td>
                                <td>{{ $driver->email }}</td>
                                <td>{{ $driver->license_plate }}</td>
                                <td><a href="/admin/print-alls/driver/{{ $driver->id }}" target="_new"
                                        class="btn btn-primary btn-sm"><i class="fa fa-file-pdf-o"></i></a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>



        </div>
    </div>
</div>
@endif
@endsection
<script>
    console.log({!! $drivers !!})
</script>