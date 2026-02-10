<?php

namespace App\Http\Controllers\Traits;

use \SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait CsvImportTrait
{
    public function processCsvImport(Request $request)
    {
        try {
            $hasHeader = $request->input('hasHeader', false);
            $fields = $request->input('fields', false);
            $fields = array_flip(array_filter($fields));

            $modelName = $request->input('modelName', false);
            $model     = 'App\\Models\\' . $modelName;

            $filenames = $request->input('filenames', []);
            if (!is_array($filenames) || count($filenames) === 0) {
                $singleFilename = $request->input('filename', false);
                $filenames      = $singleFilename ? [$singleFilename] : [];
            }

            $importedFiles = 0;
            $failedFiles   = 0;

            foreach ($filenames as $filename) {
                $path = storage_path('app/csv_import/' . $filename);

                try {
                    if (!File::exists($path)) {
                        throw new \RuntimeException('CSV file not found at path: ' . $path);
                    }

                    $this->importCsvFile($path, $hasHeader, $fields, $model);
                    $importedFiles++;
                } catch (\Exception $ex) {
                    $failedFiles++;
                    Log::error('CSV import failed for file.', [
                        'filename'  => $filename,
                        'modelName' => $modelName,
                        'message'   => $ex->getMessage(),
                    ]);
                } finally {
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
            }

            if ($failedFiles > 0) {
                session()->flash('message', $importedFiles . ' ficheiros CSV importados com sucesso. Alguns ficheiros nao foram importados. Ver logs.');
            } else {
                session()->flash('message', $importedFiles . ' ficheiros CSV importados com sucesso');
            }

            return redirect($request->input('redirect'));
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function parseCsvImport(Request $request)
    {
        $csvRule = [
            'required',
            'file',
            'mimes:csv,txt',
            'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
            function ($attribute, $value, $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'csv') {
                    $fail('O :attribute deve ser um arquivo do tipo csv.');
                }
            },
        ];

        if ($request->hasFile('csv_files')) {
            $request->validate([
                'csv_files'   => 'required|array|min:1',
                'csv_files.*' => $csvRule,
            ]);
        } else {
            $request->validate([
                'csv_file' => $csvRule,
            ]);
        }

        $files = $request->file('csv_files', []);
        if (!is_array($files) || count($files) === 0) {
            $singleFile = $request->file('csv_file');
            $files      = $singleFile ? [$singleFile] : [];
        }

        $file      = $files[0];
        $path      = $file->path();
        $hasHeader = $request->input('header', false) ? true : false;

        $reader  = new SpreadsheetReader($path);
        $headers = $reader->current();
        $lines   = [];

        $i = 0;
        while ($reader->next() !== false && $i < 5) {
            $lines[] = $reader->current();
            ++$i;
        }

        $filenames = [];
        foreach ($files as $csvFile) {
            $storedFilename = Str::random(10) . '.csv';
            $csvFile->storeAs('csv_import', $storedFilename);
            $filenames[] = $storedFilename;
        }

        $filename = $filenames[0];

        $modelName     = $request->input('model', false);
        $fullModelName = 'App\\Models\\' . $modelName;

        $model     = new $fullModelName();
        $fillables = $model->getFillable();

        $redirect = url()->previous();

        $routeName = 'admin.' . strtolower(Str::plural(Str::kebab($modelName))) . '.processCsvImport';

        return view('csvImport.parseInput', compact('headers', 'filename', 'filenames', 'fillables', 'hasHeader', 'modelName', 'lines', 'redirect', 'routeName'));
    }

    protected function importCsvFile($path, $hasHeader, array $fields, $model)
    {
        $reader = new SpreadsheetReader($path);
        $insert = [];

        foreach ($reader as $key => $row) {
            if ($hasHeader && $key == 0) {
                continue;
            }

            $tmp = [];
            foreach ($fields as $header => $k) {
                if (isset($row[$k])) {
                    $tmp[$header] = $row[$k];
                }
            }

            if (count($tmp) > 0) {
                $insert[] = $tmp;
            }
        }

        $for_insert = array_chunk($insert, 100);

        foreach ($for_insert as $insert_item) {
            $model::insert($insert_item);
        }

        return count($insert);
    }
}
