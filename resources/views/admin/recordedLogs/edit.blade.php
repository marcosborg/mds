@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.recordedLog.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.recorded-logs.update", [$recordedLog->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.recordedLog.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $recordedLog->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.recordedLog.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') : $recordedLog->driver->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.recordedLog.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('company_id') ? old('company_id') : $recordedLog->company->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.recordedLog.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $recordedLog->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('balance') ? 'has-error' : '' }}">
                            <label class="required" for="balance">{{ trans('cruds.recordedLog.fields.balance') }}</label>
                            <input class="form-control" type="number" name="balance" id="balance" value="{{ old('balance', $recordedLog->balance) }}" step="0.01" required>
                            @if($errors->has('balance'))
                                <span class="help-block" role="alert">{{ $errors->first('balance') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.balance_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('data') ? 'has-error' : '' }}">
                            <label class="required" for="data">{{ trans('cruds.recordedLog.fields.data') }}</label>
                            <textarea class="form-control" name="data" id="data" required>{{ old('data', $recordedLog->data) }}</textarea>
                            @if($errors->has('data'))
                                <span class="help-block" role="alert">{{ $errors->first('data') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.recordedLog.fields.data_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection