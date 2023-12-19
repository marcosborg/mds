@extends('layouts.admin')
@section('content')
<div class="content">
    @can('current_account_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.current-accounts.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.currentAccount.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.currentAccount.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-CurrentAccount">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.currentAccount.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.currentAccount.fields.tvde_week') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.currentAccount.fields.driver') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.email') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.currentAccount.fields.data') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currentAccounts as $key => $currentAccount)
                                    <tr data-entry-id="{{ $currentAccount->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $currentAccount->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $currentAccount->tvde_week->start_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $currentAccount->driver->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $currentAccount->driver->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $currentAccount->driver->email ?? '' }}
                                        </td>
                                        <td>
                                            {{ $currentAccount->data ?? '' }}
                                        </td>
                                        <td>
                                            @can('current_account_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.current-accounts.show', $currentAccount->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('current_account_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.current-accounts.edit', $currentAccount->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('current_account_delete')
                                                <form action="{{ route('admin.current-accounts.destroy', $currentAccount->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
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
@can('current_account_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.current-accounts.massDestroy') }}",
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
  let table = $('.datatable-CurrentAccount:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection