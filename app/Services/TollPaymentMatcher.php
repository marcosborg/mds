<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\TollPayment;
use Illuminate\Validation\ValidationException;

class TollPaymentMatcher
{
    public function resolve(string $identifier): Driver
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            throw ValidationException::withMessages(['card' => 'Indique o código interno ou o nome do motorista.']);
        }

        // Include legacy card codes, but never prefer one matching field over
        // another: a code/name/card collision requires correcting the cadastro.
        $matches = Driver::where(function ($query) use ($identifier) {
            $query->whereRaw('LOWER(TRIM(code)) = LOWER(?)', [$identifier])
                ->orWhereRaw('LOWER(TRIM(name)) = LOWER(?)', [$identifier])
                ->orWhereHas('tool_card', function ($card) use ($identifier) {
                    $card->whereRaw('LOWER(TRIM(code)) = LOWER(?)', [$identifier]);
                });
        })->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages(['card' => $matches->isEmpty()
                ? 'Não foi encontrado um motorista para este código interno, nome ou cartão.'
                : 'Identificação ambígua: corresponde a vários motoristas. Use um código interno único ou corrija o cadastro.']);
        }

        return $matches->first();
    }

    public function forWeek(Driver $driver, $week): array
    {
        $payments = collect();
        $warnings = [];
        foreach (TollPayment::where('tvde_week_id', $week)->get() as $payment) {
            if ($payment->driver_id) {
                if ((int) $payment->driver_id === (int) $driver->id) {
                    $payments->push($payment);
                }
                continue;
            }
            try {
                if ($this->resolve((string) $payment->card)->id === $driver->id) {
                    $payments->push($payment);
                }
            } catch (ValidationException $exception) {
                $warnings[] = "Portagem #{$payment->id} por associar: corrija a identificação em Toll Payment antes de gravar o extrato.";
            }
        }
        return [$payments, array_values(array_unique($warnings))];
    }
}
