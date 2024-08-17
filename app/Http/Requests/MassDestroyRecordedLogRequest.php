<?php

namespace App\Http\Requests;

use App\Models\RecordedLog;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyRecordedLogRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('recorded_log_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:recorded_logs,id',
        ];
    }
}
