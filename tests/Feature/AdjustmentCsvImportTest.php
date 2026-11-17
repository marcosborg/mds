<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\CsvImportTrait;
use App\Models\Adjustment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdjustmentCsvImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['companies' => [], 'drivers' => ['name','company_id'],
            'adjustments' => ['name','company_id','amount','type','start_date','end_date'],
            'adjustment_driver' => ['driver_id','adjustment_id']] as $table => $columns) {
            Schema::create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) $t->string($column)->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        DB::table('companies')->insert(['id' => 1]);
        foreach ([[1,'Ana',1],[2,'Ana',2],[3,'Rui',2],[4,'José',1],[5,'José',1]] as [$id,$name,$company]) {
            DB::table('drivers')->insert(['id'=>$id,'name'=>$name,'company_id'=>$company]);
        }
        session(['company_id' => 1]);
    }

    private function import(array $names, bool $mapped = true): void
    {
        $reader = new class {
            use CsvImportTrait { importCsvFile as public read; }
        };
        $path = sys_get_temp_dir() . '/adjustment-' . bin2hex(random_bytes(12)) . '.csv';
        $fields = ['name','type','amount','start_date','end_date'];
        if ($mapped) $fields[] = 'drivers';
        $file = fopen($path, 'w');
        fputcsv($file, $fields);
        foreach ($names as $name) {
            $row = ['Portagens','deduct','12.50','2026-11-09','2026-11-15'];
            if ($mapped) $row[] = $name;
            fputcsv($file, $row);
        }
        fclose($file);
        try { $reader->read($path, true, array_flip($fields), Adjustment::class); }
        finally { unlink($path); }
    }

    public function test_exact_name_in_company_links_only_intended_driver(): void
    {
        $this->import([' Ana ']);
        $a = Adjustment::firstOrFail();
        $this->assertSame('Portagens', $a->name);
        $this->assertSame('2026-11-09', $a->getRawOriginal('start_date'));
        $this->assertEquals(12.50, $a->amount);
        $this->assertEquals([1], $a->drivers->pluck('id')->all());
        $this->import([''], false);
        $this->assertDatabaseCount('adjustments', 2);
        $this->assertDatabaseCount('adjustment_driver', 1);
    }

    public function test_invalid_second_row_rolls_back_first(): void
    {
        foreach (['Rui','José','','ana'] as $name) {
            try { $this->import(['Ana', $name]); $this->fail('Expected a row error'); }
            catch (ValidationException $e) { $this->assertStringContainsString('Linha 3:', $e->errors()['drivers'][0]); }
            $this->assertDatabaseCount('adjustments', 0);
            $this->assertDatabaseCount('adjustment_driver', 0);
        }
    }
}
