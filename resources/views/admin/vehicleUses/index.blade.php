@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleUse.title') }} - {{ $vehicle_item->license_plate }}
                </div>
                <div class="panel-body">
                    <a href="/admin/vehicle-items" class="btn btn-success">Voltar às viaturas</a>

                    <h3>Ocupação da Viatura</h3>

                    

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
