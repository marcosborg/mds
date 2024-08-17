<?php

namespace App\Http\Requests;

use App\Models\RecordedLog;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateRecordedLogRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('recorded_log_edit');
    }

    public function rules()
    {
        return [
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
            'company_id' => [
                'required',
                'integer',
            ],
            'value' => [
                'required',
            ],
            'balance' => [
                'required',
            ],
            'data' => [
                'required',
            ],
        ];
    }
}
