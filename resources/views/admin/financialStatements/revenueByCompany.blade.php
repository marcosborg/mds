@if (!empty($revenue_by_company))
<div class="panel panel-default">
    <div class="panel-heading"><strong>Faturação por empresa</strong></div>
    <div class="panel-body">
        <p>Valores já incluídos nos ganhos e gorjetas do extrato.</p>
        <table class="table table-striped bordered">
            <thead><tr><th>Empresa</th><th>Ganhos</th><th>Gorjetas</th><th>Total bruto</th></tr></thead>
            <tbody>
                @foreach ($revenue_by_company as $revenue)
                <tr>
                    <td>{{ data_get($revenue, 'company_name') }}</td>
                    <td>{{ number_format(data_get($revenue, 'earnings'), 2, ',', ' ') }} €</td>
                    <td>{{ number_format(data_get($revenue, 'tips'), 2, ',', ' ') }} €</td>
                    <td>{{ number_format(data_get($revenue, 'gross'), 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@foreach (($statement_warnings ?? []) as $warning)
<div class="alert alert-warning" role="alert">{{ $warning }}</div>
@endforeach
