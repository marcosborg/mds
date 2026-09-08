<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FinancialStatementController;
use App\Http\Controllers\Admin\PrintAllController;
use App\Http\Controllers\Admin\RecordedLogController;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Models\Driver;
use App\Models\TollPayment;
use App\Services\StatementSnapshot;
use App\Services\TollPaymentMatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DriverStatementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'panel.date_format' => 'Y-m-d']);
        DB::purge('sqlite');
        $tables = [
            'companies' => ['name', 'vat', 'address', 'zip', 'location', 'email'],
            'drivers' => ['name', 'code', 'uber_uuid', 'bolt_name', 'company_id', 'contract_type_id', 'contract_vat_id', 'tool_card_id', 'electric_id', 'card_id', 'state_id', 'user_id', 'address', 'zip', 'city', 'phone', 'email', 'brand', 'model', 'license_plate'],
            'tvde_years' => ['name'],
            'tvde_months' => ['name', 'number', 'year_id'],
            'tvde_weeks' => ['start_date', 'end_date', 'number', 'tvde_month_id'],
            'tvde_activities' => ['company_id', 'tvde_week_id', 'tvde_operator_id', 'driver_code', 'earnings_one', 'earnings_two'],
            'contract_types' => ['name'],
            'contract_vats' => ['tips', 'percent', 'fee'],
            'contract_type_ranks' => ['contract_type_id', 'from', 'to', 'percent'],
            'adjustments' => ['name', 'type', 'amount', 'start_date', 'end_date', 'company_id'],
            'adjustment_driver' => ['driver_id', 'adjustment_id'],
            'toll_cards' => ['code', 'company_id'],
            'toll_payments' => ['card', 'driver_id', 'tvde_week_id', 'total'],
            'recorded_logs' => ['tvde_week_id', 'driver_id', 'company_id', 'data', 'value', 'balance', 'vehicle_item_id'],
            'vehicle_items' => ['driver_id'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns, $name) {
                if ($name !== 'adjustment_driver') {
                    $table->id();
                }
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }
        DB::table('companies')->insert([['id' => 1, 'name' => 'Empresa Alfa'], ['id' => 2, 'name' => 'Empresa Beta']]);
        DB::table('tvde_years')->insert(['id' => 1, 'name' => '2026']);
        DB::table('tvde_months')->insert(['id' => 1, 'name' => 'Agosto', 'number' => 8, 'year_id' => 1]);
        DB::table('tvde_weeks')->insert(['id' => 1, 'start_date' => '2026-08-10', 'end_date' => '2026-08-16', 'number' => 33, 'tvde_month_id' => 1]);
        DB::table('contract_types')->insert(['id' => 1, 'name' => 'Contrato']);
        DB::table('contract_vats')->insert(['id' => 1, 'tips' => 10, 'percent' => 0, 'fee' => 5]);
        DB::table('contract_type_ranks')->insert(['id' => 1, 'contract_type_id' => 1, 'from' => 0, 'to' => 99999, 'percent' => 80]);
        DB::table('drivers')->insert(['id' => 1, 'name' => 'Motorista Teste', 'code' => '001', 'uber_uuid' => 'uber-1', 'bolt_name' => 'bolt-1', 'company_id' => 1, 'contract_type_id' => 1, 'contract_vat_id' => 1, 'state_id' => 1]);
    }

    private function activity($company, $operator, $gross, $tips = 0, $code = null): void
    {
        DB::table('tvde_activities')->insert(['company_id' => $company, 'tvde_week_id' => 1, 'tvde_operator_id' => $operator, 'driver_code' => $code ?? ($operator === 1 ? 'uber-1' : 'bolt-1'), 'earnings_one' => $tips, 'earnings_two' => $gross]);
    }

    private function report($company = 1): array
    {
        return app(FinancialStatementController::class)->getWeekResults(1, 1, $company);
    }

    public function test_single_company_keeps_contract_tip_and_fee_rules(): void
    {
        $this->activity(1, 1, 110, 10);
        $result = $this->report();
        $this->assertEqualsWithDelta(83.5, $result['final_total'], 0.001);
        $this->assertCount(1, $result['revenue_by_company']);
        $this->assertEquals(110, $result['revenue_by_company'][0]['gross']);
    }

    public function test_changing_company_does_not_drop_previous_revenue_or_period(): void
    {
        $this->activity(1, 1, 110, 10);
        $before = $this->report();
        Driver::find(1)->update(['company_id' => 2]);
        $after = $this->report(2);
        $this->assertEquals($before['final_total'], $after['final_total']);
        $this->assertEquals(1, $after['revenue_by_company'][0]['company_id']);
        session(['company_id' => 2, 'tvde_year_id' => 1, 'tvde_month_id' => 1, 'tvde_week_id' => 1]);
        $filter = app(FinancialStatementController::class)->filter(true);
        $this->assertEquals(1, $filter['tvde_week_id']);
        $this->assertCount(1, $filter['tvde_weeks']);
    }

    public function test_multiple_companies_are_counted_once_and_large_amounts_remain_numeric(): void
    {
        $this->activity(1, 1, 1210, 10);
        $this->activity(2, 2, 220, 20);
        $this->activity(2, 1, 500, 0, 'unrelated');
        $result = $this->report();
        $this->assertEquals(1430, $result['total_earnings']);
        $this->assertEqualsWithDelta(1075.5, $result['final_total'], 0.001);
        $this->assertCount(2, $result['revenue_by_company']);
        $this->assertEquals(1430, array_sum(array_column($result['revenue_by_company'], 'gross')));
        $companyOnly = app(FinancialStatementController::class)->getWeekResults(1, 0, 1);
        $this->assertEquals(1210, $companyOnly['total_earnings']);
    }

    public function test_blank_operator_identifiers_never_match_other_blank_rows(): void
    {
        Driver::find(1)->update(['uber_uuid' => '', 'bolt_name' => null]);
        $this->activity(1, 1, 500, 0, '');
        $this->assertEquals(0, $this->report()['total_earnings']);
    }

    public function test_tolls_match_internal_code_name_and_legacy_card_and_survive_rename(): void
    {
        DB::table('toll_cards')->insert(['id' => 1, 'code' => 'CARD-1']);
        Driver::find(1)->update(['tool_card_id' => 1]);
        foreach (['001', ' motorista teste ', 'CARD-1'] as $identifier) {
            $payment = TollPayment::create(['tvde_week_id' => 1, 'card' => $identifier, 'total' => 5]);
            $this->assertEquals(1, $payment->driver_id);
        }
        Driver::find(1)->update(['name' => 'Nome alterado', 'tool_card_id' => null, 'company_id' => 2]);
        $this->assertEquals(15, $this->report(2)['toll_payments']->sum('total'));
    }

    public function test_ambiguous_names_and_cross_field_collisions_are_rejected(): void
    {
        DB::table('drivers')->insert(['id' => 2, 'name' => 'Motorista Teste', 'code' => '002']);
        try {
            app(TollPaymentMatcher::class)->resolve('Motorista Teste');
            $this->fail('Ambiguous name accepted');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('ambígua', $exception->getMessage());
        }
        Driver::find(2)->update(['name' => '001']);
        $this->expectException(ValidationException::class);
        app(TollPaymentMatcher::class)->resolve('001');
    }

    public function test_unresolved_legacy_tolls_produce_visible_warning_without_misattribution(): void
    {
        DB::table('toll_payments')->insert(['card' => 'unknown', 'tvde_week_id' => 1, 'total' => 9]);
        $result = $this->report();
        $this->assertCount(0, $result['toll_payments']);
        $this->assertStringContainsString('por associar', $result['statement_warnings'][0]);
    }

    public function test_csv_is_atomic_when_one_driver_is_unknown(): void
    {
        $importer = new class {
            use CsvImportTrait;
            public function run($path) { return $this->importCsvFile($path, true, ['card' => 0, 'tvde_week_id' => 1, 'total' => 2], TollPayment::class); }
        };
        $path = tempnam(sys_get_temp_dir(), 'toll');
        file_put_contents($path, "card,tvde_week_id,total\n001,1,5\nunknown,1,7\n");
        try {
            $importer->run($path);
            $this->fail('Invalid import accepted');
        } catch (ValidationException $exception) {
            $this->assertSame(0, TollPayment::count());
        } finally {
            unlink($path);
        }
    }

    public function test_manual_adjustments_are_preserved_and_warned_about(): void
    {
        $this->activity(1, 1, 100);
        $this->activity(2, 2, 100);
        DB::table('adjustments')->insert(['id' => 1, 'name' => 'Faturação manual', 'type' => 'refund', 'amount' => 20]);
        DB::table('adjustment_driver')->insert(['driver_id' => 1, 'adjustment_id' => 1]);
        $result = $this->report();
        $this->assertEquals(170, $result['final_total']);
        $this->assertStringContainsString('créditos manuais', implode(' ', $result['statement_warnings']));
        $this->assertEquals(20, DB::table('adjustments')->value('amount'));
    }

    public function test_saved_snapshots_stay_unchanged_and_batch_pdf_uses_them(): void
    {
        $this->activity(1, 1, 110, 10);
        $snapshot = $this->report();
        DB::table('recorded_logs')->insert(['tvde_week_id' => 1, 'driver_id' => 1, 'company_id' => 1, 'data' => json_encode($snapshot)]);
        $this->activity(2, 2, 100);
        Driver::find(1)->update(['company_id' => 2]);
        $log = app(StatementSnapshot::class)->find(1, 1, 2);
        $this->assertEquals(110, json_decode($log->data)->total_earnings);
        $html = app(PrintAllController::class)->pdf(2, 1, 1);
        $this->assertStringContainsString('Empresa Alfa', $html);
        $this->assertStringContainsString('Faturação por empresa', $html);
    }

    public function test_contract_tier_is_selected_on_combined_revenue(): void
    {
        DB::table('contract_type_ranks')->where('id', 1)->update(['to' => 199, 'percent' => 50]);
        DB::table('contract_type_ranks')->insert(['contract_type_id' => 1, 'from' => 200, 'to' => 99999, 'percent' => 80]);
        $this->activity(1, 1, 150);
        $this->activity(2, 2, 150);
        $result = $this->report();
        $this->assertEquals(80, $result['contract_type_rank']->percent);
        $this->assertEquals(225, $result['final_total']);
        $html = app(PrintAllController::class)->pdf(1, 1, 1);
        $this->assertStringContainsString('Empresa Beta', $html);
        $this->assertStringContainsString('225.00', $html);
    }

    public function test_old_company_snapshot_blocks_duplicate_payment_and_is_preserved(): void
    {
        Gate::before(fn ($user = null) => true);
        DB::table('recorded_logs')->insert(['tvde_week_id' => 1, 'driver_id' => 1, 'company_id' => 2, 'data' => '{"final_total":45}']);
        try {
            app(RecordedLogController::class)->recordLog(1, 1, 1, 9999);
            $this->fail('Duplicate statement accepted');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('extrato antigo', $exception->getMessage());
            $this->assertEquals('{"final_total":45}', DB::table('recorded_logs')->value('data'));
        }
    }

    public function test_recording_uses_calculated_amount_and_replaces_one_consolidated_snapshot(): void
    {
        Gate::before(fn ($user = null) => true);
        $this->activity(1, 1, 110, 10);
        $log = app(RecordedLogController::class)->recordLog(1, 1, 1, 9999);
        $this->assertEquals(83.5, $log->value);
        Driver::find(1)->update(['company_id' => 2]);
        app(RecordedLogController::class)->recordLog(1, 1, 2, 9999);
        $this->assertEquals(1, \App\Models\RecordedLog::count());
    }

    public function test_csv_accepts_both_driver_identifiers(): void
    {
        $importer = new class {
            use CsvImportTrait;
            public function run($path) { return $this->importCsvFile($path, true, ['card' => 0, 'tvde_week_id' => 1, 'total' => 2], TollPayment::class); }
        };
        $path = tempnam(sys_get_temp_dir(), 'toll');
        file_put_contents($path, "card,tvde_week_id,total\n001,1,5\nMotorista Teste,1,7\n");
        try {
            $this->assertEquals(2, $importer->run($path));
            $this->assertEquals(12, $this->report()['toll_payments']->sum('total'));
        } finally {
            unlink($path);
        }
    }
}
