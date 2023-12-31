<?php

namespace App\Http\Requests;

use App\Models\DriversBalance;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyDriversBalanceRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('drivers_balance_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:drivers_balances,id',
        ];
    }
}
