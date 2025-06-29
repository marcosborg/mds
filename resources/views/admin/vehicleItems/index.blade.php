@extends('layouts.admin')
@section('content')
<div class="content">
    @can('vehicle_item_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.vehicle-items.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.vehicleItem.title_singular') }}
            </a>
        </div>
    </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleItem.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-VehicleItem">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.driver') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.code') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.email') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.driver_license') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.vehicle_brand') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.vehicle_model') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.year') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.license_plate') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.documents') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicleItems as $key => $vehicleItem)
                                <tr data-entry-id="{{ $vehicleItem->id }}">
                                    <td>

                                    </td>
                                    <td>
                                        {{ $vehicleItem->id ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->driver->name ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->driver->code ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->driver->email ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->driver->driver_license ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->vehicle_brand->name ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->vehicle_model->name ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->year ?? '' }}
                                    </td>
                                    <td>
                                        {{ $vehicleItem->license_plate ?? '' }}
                                    </td>
                                    <td>
                                        @foreach($vehicleItem->documents as $key => $media)
                                        <a class="btn btn-success btn-sm" href="{{ $media->getUrl() }}" target="_blank">
                                            {{ ucfirst(str_replace('_', '', strstr($media->name, '_', false))) }}
                                        </a>
                                        @endforeach
                                    </td>
                                    <td>
                                        <a class="btn btn-xs btn-default"
                                            href="/admin/vehicle-uses/{{ $vehicleItem->id }}">
                                            Ocupação
                                        </a>
                                        @can('vehicle_item_show')
                                        <a class="btn btn-xs btn-primary"
                                            href="{{ route('admin.vehicle-items.show', $vehicleItem->id) }}">
                                            {{ trans('global.view') }}
                                        </a>
                                        @endcan

                                        @can('vehicle_item_edit')
                                        <a class="btn btn-xs btn-info"
                                            href="{{ route('admin.vehicle-items.edit', $vehicleItem->id) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                        @endcan

                                        @can('vehicle_item_delete')
                                        <form action="{{ route('admin.vehicle-items.destroy', $vehicleItem->id) }}"
                                            method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="submit" class="btn btn-xs btn-danger"
                                                value="{{ trans('global.delete') }}">
                                        </form>
                                        @endcan

                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('vehicle_item_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.vehicle-items.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-VehicleItem:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection