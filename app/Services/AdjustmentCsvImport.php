<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustmentCsvImport
{
    public function save(array $rows, int $company, int $firstLine): int
    {
        if (!$company || !Company::whereKey($company)->exists()) {
            throw ValidationException::withMessages(['drivers' => 'Selecione uma empresa antes de importar.']);
        }

        return DB::transaction(function () use ($rows, $company, $firstLine) {
            $drivers = Driver::where('company_id', $company)->get(['id', 'name'])
                ->groupBy(fn ($driver) => trim($driver->name));

            foreach ($rows as $i => $row) {
                $name = trim($row['drivers'] ?? '');
                $matches = $drivers->get($name, collect());
                $line = 'Linha ' . ($firstLine + $i) . ': ';
                if ($name === '' || $matches->count() !== 1) {
                    $reason = $name === '' ? 'nome do motorista em falta' : ($matches->isEmpty()
                        ? "motorista «{$name}» não encontrado nesta empresa" : "nome de motorista ambíguo: «{$name}»");
                    throw ValidationException::withMessages(['drivers' => $line . $reason . '.']);
                }
                if (isset($row['company_id']) && trim($row['company_id']) !== '' && trim($row['company_id']) !== (string) $company) {
                    throw ValidationException::withMessages(['company_id' => $line . 'empresa diferente da selecionada.']);
                }
                $validator = validator($row, [
                    'name' => 'required|string', 'type' => 'required|in:deduct,refund',
                    'amount' => 'nullable|numeric', 'percent' => 'nullable|numeric',
                    'start_date' => 'nullable|date_format:Y-m-d', 'end_date' => 'nullable|date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['row' => $line . $validator->errors()->first()]);
                }
                unset($row['drivers']);
                $row['company_id'] = $company;
                foreach (['amount', 'percent'] as $field) {
                    if (($row[$field] ?? null) === '') $row[$field] = null;
                }
                Adjustment::create($row)->drivers()->attach($matches->first()->id);
            }
            return count($rows);
        });
    }
}
