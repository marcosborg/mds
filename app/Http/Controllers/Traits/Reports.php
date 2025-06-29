<?php

namespace App\Http\Controllers\Traits;

use App\Models\Adjustment;
use App\Models\CombustionTransaction;
use App\Models\ContractTypeRank;
use App\Models\Driver;
use App\Models\ElectricTransaction;
use App\Models\TvdeActivity;
use App\Models\TvdeWeek;
use App\Models\CurrentAccount;
use App\Models\Electric;
use App\Models\Card;
use App\Models\TvdeMonth;
use App\Models\TvdeYear;
use App\Models\TollCard;
use App\Models\TollPayment;

trait Reports
{
    public function getWeekReport($company_id, $tvde_week_id)
    {

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $drivers = Driver::where('company_id', $company_id)
            ->where('state_id', 1)
            ->orderBy('name')
            ->get()
            ->load([
                'contract_vat',
                'card',
                'electric',
            ]);

        $total_uber = [];
        $total_bolt = [];
        $total_operators = [];
        $total_earnings_after_discount = [];
        $total_tips_after_discount = [];
        $total_fuel_transactions = [];
        $total_adjustments = [];
        $total_fleet_management = [];
        $total_drivers = [];
        $total_company_adjustments = [];

        foreach ($drivers as $driver) {
            $uber_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 1,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->uber_uuid
            ])
                ->get();

            $uber_total_earnings = $uber_activities->sum('earnings_two');
            $uber_tips = $uber_activities->sum('earnings_one');
            $uber_earnings = $uber_total_earnings - $uber_tips;

            $bolt_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 2,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->bolt_name
            ])
                ->get();

            $bolt_total_earnings = $bolt_activities->sum('earnings_two');
            $bolt_tips = $bolt_activities->sum('earnings_one');
            $bolt_earnings = $bolt_total_earnings - $bolt_tips;


            //EARNINGS

            $uber = collect([
                'total_earnings' => $uber_total_earnings,
                'tips' => $uber_tips,
                'earnings' => $uber_earnings
            ]);

            $bolt = collect([
                'total_earnings' => $bolt_total_earnings,
                'tips' => $bolt_tips,
                'earnings' => $bolt_earnings
            ]);

            $total_earnings = $bolt_total_earnings + $uber_total_earnings;
            $total_earnings_no_tips = $uber_earnings + $bolt_earnings;
            $total_tips = $uber_tips + $bolt_tips;

            //CONTRACT

            $contract_type_rank = ContractTypeRank::where([
                'contract_type_id' => $driver->contract_type_id
            ])
                ->where('from', '<=', ceil($total_earnings_no_tips))
                ->where('to', '>=', ceil($total_earnings_no_tips))
                ->first();

            if ($contract_type_rank) {
                $percent = $contract_type_rank->percent;
            } else {
                $percent = 0;
            }

            $earnings_after_discount = ($total_earnings_no_tips * $percent) / 100;

            $tips_after_discount = ($total_tips * (100 - $driver->contract_vat->tips)) / 100;

            //FUEL

            $fuel_transactions = [];

            if ($driver->electric) {
                $electric_transactions = ElectricTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->electric->code
                ])
                    ->sum('total');

                if ($electric_transactions > 0) {
                    $fuel_transactions[] = $electric_transactions;
                }
            }

            if ($driver->card) {
                $combustion_transactions = CombustionTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->card->code
                ])
                    ->sum('total');

                if ($combustion_transactions > 0) {
                    $fuel_transactions[] = $combustion_transactions;
                }
            }

            if (is_array($fuel_transactions)) {
                $fuel_transactions = array_sum($fuel_transactions);
            }

            $driver->fuel = $fuel_transactions;

            $total_fuel_transactions[] = $fuel_transactions;

            //ADJUSTMENTS
            $adjustments = Adjustment::whereHas('drivers', function ($query) use ($driver) {
                $query->where('id', $driver->id);
            })
                ->where('company_id', $company_id)
                ->where(function ($query) use ($tvde_week) {
                    $query->where('start_date', '<=', $tvde_week->start_date)
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($tvde_week) {
                    $query->where('end_date', '>=', $tvde_week->end_date)
                        ->orWhereNull('end_date');
                })
                ->get();

            $refunds = [];
            $deducts = [];
            $fleet_management = [];
            $company_expense = [];

            foreach ($adjustments as $adjustment) {
                if ($adjustment->type == 'deduct') {
                    if ($adjustment->fleet_management) {
                        $fleet_management[] = $adjustment->amount;
                    } else {
                        $deducts[] = $adjustment->amount;
                    }
                } else {
                    if ($adjustment->fleet_management) {
                        $fleet_management[] = (-$adjustment->amount);
                    } else {
                        $refunds[] = $adjustment->amount;
                    }
                }
                if ($adjustment->company_expense) {
                    if ($adjustment->type == 'deduct') {
                        $company_expense[] = -$adjustment->amount;
                    } else {
                        $company_expense[] = $adjustment->amount;
                    }
                }
            }

            $refunds = array_sum($refunds);
            $deducts = array_sum($deducts);
            $adjustments = $refunds - $deducts;

            $total_adjustments[] = $adjustments;

            $fleet_management = array_sum($fleet_management);

            $total_fleet_management[] = $fleet_management;

            $total_company_adjustments[] = array_sum($company_expense);

            $driver->refunds = $refunds;
            $driver->adjustments = $adjustments;
            $driver->fleet_management = $fleet_management;

            $driver->total = $earnings_after_discount + $tips_after_discount - $fuel_transactions + $adjustments - $fleet_management;

            if ($driver && $driver->contract_vat->percent && $driver->contract_vat->percent > 0 && $driver->total > 0) {
                $txt_admin = ($driver->total * $driver->contract_vat->percent) / 100;
                $driver->total = $driver->total - $txt_admin;
            } else {
                $txt_admin = 0;
            }

            $earnings = collect([
                'uber' => $uber,
                'bolt' => $bolt,
                'total' => $total_earnings,
                'total_tips' => $total_tips,
                'percent' => $contract_type_rank->percent ?? 0,
                'tips_percent' => $driver->contract_vat->tips,
                'total_no_tips' => $total_earnings_no_tips,
                'earnings_after_discount' => $earnings_after_discount,
                'tips_after_discount' => $tips_after_discount,
                'txt_admin' => $txt_admin,
            ]);

            $driver->earnings = $earnings;

            $total_uber[] = $uber_total_earnings;
            $total_bolt[] = $bolt_total_earnings;
            $total_operators[] = $total_earnings;
            $total_earnings_after_discount[] = $earnings_after_discount;
            $total_tips_after_discount[] = $tips_after_discount;
            $total_drivers[] = $driver->total;

            $current_account = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            if ($current_account) {
                $driver->current_account = true;
            } else {
                $driver->current_account = false;
            }
        }

        $totals = collect([
            'total_uber' => array_sum($total_uber),
            'total_bolt' => array_sum($total_bolt),
            'total_operators' => array_sum($total_operators),
            'total_earnings_after_discount' => array_sum($total_earnings_after_discount),
            'total_tips_after_discount' => array_sum($total_tips_after_discount),
            'total_fuel_transactions' => array_sum($total_fuel_transactions),
            'total_adjustments' => array_sum($total_adjustments),
            'total_fleet_management' => array_sum($total_fleet_management),
            'total_drivers' => array_sum($total_drivers),
            'total_company_adjustments' => array_sum($total_company_adjustments),
        ]);

        return [
            'drivers' => $drivers,
            'totals' => $totals,
        ];
    }

    public function getDriverWeekReport($driver_id, $company_id, $tvde_week_id)
    {

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $driver = Driver::find($driver_id)->load([
            'contract_type',
            'contract_vat'
        ]);

        $bolt_activities = TvdeActivity::where([
            'tvde_week_id' => $tvde_week_id,
            'tvde_operator_id' => 2,
            'driver_code' => $driver->bolt_name,
            'company_id' => $company_id,
        ])
            ->get();

        $uber_activities = TvdeActivity::where([
            'tvde_week_id' => $tvde_week_id,
            'tvde_operator_id' => 1,
            'driver_code' => $driver->uber_uuid,
            'company_id' => $company_id,
        ])
            ->get();

        $adjustments = Adjustment::whereHas('drivers', function ($query) use ($driver_id) {
            $query->where('id', $driver_id);
        })
            ->where('company_id', $company_id)
            ->where(function ($query) use ($tvde_week) {
                $query->where('start_date', '<=', $tvde_week->start_date)
                    ->orWhereNull('start_date');
            })
            ->where(function ($query) use ($tvde_week) {
                $query->where('end_date', '>=', $tvde_week->end_date)
                    ->orWhereNull('end_date');
            })
            ->get();

        $refund = 0;
        $deduct = 0;

        foreach ($adjustments as $adjustment) {
            switch ($adjustment->type) {
                case 'refund':
                    if ($adjustment->amount) {
                        $refund = $refund + $adjustment->amount;
                    }
                    if ($adjustment->percent) {
                        $total = $bolt_activities->sum('earnings_two') + $uber_activities->sum('earnings_two');
                        $percent = $adjustment->percent;
                        $amount = ($total * $percent) / 100;
                        $refund = $refund + $amount;
                        $adjustment->amount = $amount;
                    }
                    break;
                case 'deduct':
                    if ($adjustment->amount) {
                        $deduct = $deduct + $adjustment->amount;
                    }
                    if ($adjustment->percent) {
                        $total = $bolt_activities->sum('earnings_two') + $uber_activities->sum('earnings_two');
                        $percent = $adjustment->percent;
                        $amount = ($total * $percent) / 100;
                        $deduct = $deduct + $amount;
                        $adjustment->amount = $amount;
                    }
                    break;
            }
        }

        // FUEL EXPENSES

        $electric_expenses = null;
        if ($driver && $driver->electric_id) {
            $electric = Electric::find($driver->electric_id);
            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id
                ])->get();
                $electric_expenses = collect([
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', '') . ' €',
                    'value' => $electric_transactions->sum('total')
                ]);
            }
        }
        $combustion_expenses = null;
        if ($driver && $driver->card_id) {
            $card = Card::find($driver->card_id);
            if (!$card) {
                $code = 0;
            } else {
                $code = $card->code;
            }
            $combustion_transactions = CombustionTransaction::where([
                'card' => $code,
                'tvde_week_id' => $tvde_week_id
            ])->get();
            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_transactions->sum('total'), 2, '.', '') . ' €',
                'value' => $combustion_transactions->sum('total')
            ]);
        }

        $total_earnings_bolt = number_format($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one'), 2, '.', '');
        $total_tips_bolt = number_format($bolt_activities->sum('earnings_one'), 2);
        $total_earnings_uber = number_format($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one'), 2, '.', '');
        $total_tips_uber = number_format($uber_activities->sum('earnings_one'), 2);
        $total_tips = $total_tips_uber + $total_tips_bolt;
        $total_earnings = $bolt_activities->sum('earnings_two') + $uber_activities->sum('earnings_two');
        $total_earnings_no_tip = ($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) + ($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one'));

        //CHECK PERCENT
        $contract_type_ranks = $driver ? ContractTypeRank::where('contract_type_id', $driver->contract_type_id)->get() : [];
        $contract_type_rank = count($contract_type_ranks) > 0 ? $contract_type_ranks[0] : null;
        foreach ($contract_type_ranks as $value) {
            if ($value->from <= $total_earnings && $value->to >= $total_earnings) {
                $contract_type_rank = $value;
            }
        }

        //

        $total_bolt = ($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);
        $total_uber = ($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);

        $total_earnings_after_vat = $total_bolt + $total_uber;

        $total_bolt = number_format(($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);
        $total_uber = number_format(($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);

        $bolt_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;
        $uber_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;

        $bolt_tip_after_vat = number_format($total_tips_bolt * ($bolt_tip_percent / 100), 2);
        $uber_tip_after_vat = number_format($total_tips_uber * ($uber_tip_percent / 100), 2);

        $total_tip_after_vat = $bolt_tip_after_vat + $uber_tip_after_vat;

        $total = $total_earnings + $total_tips;
        $total_after_vat = $total_earnings_after_vat + $total_tip_after_vat;

        $gross_credits = $total_earnings_no_tip + $total_tips + $refund;
        $gross_debts = ($total_earnings_no_tip - $total_earnings_after_vat) + ($total_tips - $total_tip_after_vat) + $deduct;

        $final_total = $gross_credits - $gross_debts;

        $electric_racio = null;
        $combustion_racio = null;

        if ($electric_expenses && $total_earnings > 0) {
            $final_total = $final_total - $electric_expenses['value'];
            $gross_debts = $gross_debts + $electric_expenses['value'];
            if ($electric_expenses['value'] > 0) {
                $electric_racio = ($electric_expenses['value'] / $total_earnings) * 100;
            } else {
                $electric_racio = 0;
            }
        }
        if ($combustion_expenses && $total_earnings > 0) {
            $final_total = $final_total - $combustion_expenses['value'];
            $gross_debts = $gross_debts + $combustion_expenses['value'];
            if ($combustion_expenses['value'] > 0) {
                $combustion_racio = ($combustion_expenses['value'] / $total_earnings) * 100;
            } else {
                $combustion_racio = 0;
            }
        }

        if ($driver && $driver->contract_vat->percent && $driver->contract_vat->percent > 0 && $final_total > 0) {
            $txt_admin = ($final_total * $driver->contract_vat->percent) / 100;
            $gross_debts = $gross_debts + $txt_admin;
            $final_total = $final_total - $txt_admin;
        } else {
            $txt_admin = 0;
        }

        return compact([
            'company_id',
            'tvde_week_id',
            'driver_id',
            'total_earnings_uber',
            'contract_type_rank',
            'total_uber',
            'total_earnings_bolt',
            'total_bolt',
            'total_tips_uber',
            'uber_tip_percent',
            'uber_tip_after_vat',
            'total_tips_bolt',
            'bolt_tip_percent',
            'bolt_tip_after_vat',
            'total_tips',
            'total_tip_after_vat',
            'adjustments',
            'total_earnings',
            'total_earnings_no_tip',
            'total',
            'total_after_vat',
            'gross_credits',
            'gross_debts',
            'final_total',
            'driver',
            'electric_expenses',
            'combustion_expenses',
            'combustion_racio',
            'electric_racio',
            'total_earnings_after_vat',
            'txt_admin'
        ]);
    }

    public function filter()
    {
        $company_id = session()->get('company_id');

        // Pega o ano atual
        $currentYear = date('Y');
        $tvde_year_id = session()->get('tvde_year_id') ??
            TvdeYear::where('name', $currentYear)->first()?->id;

        // Se não encontrar pelo nome, pega o mais recente
        if (!$tvde_year_id) {
            $tvde_year_id = TvdeYear::orderBy('name', 'desc')->first()?->id;
        }

        // Mês atual
        if (session()->has('tvde_month_id')) {
            $tvde_month_id = session()->get('tvde_month_id');
        } else {
            $currentMonth = date('n'); // 1 a 12
            $tvde_month = TvdeMonth::where('number', $currentMonth)
                ->where('year_id', $tvde_year_id)
                ->whereHas('weeks', function ($week) use ($company_id) {
                    $week->whereHas('tvdeActivities', function ($activity) use ($company_id) {
                        $activity->where('company_id', $company_id);
                    });
                })
                ->first();

            $tvde_month_id = $tvde_month?->id ?? 0;
        }

        // Semana atual
        if (session()->has('tvde_week_id')) {
            $tvde_week_id = session()->get('tvde_week_id');
        } else {
            $tvde_week = TvdeWeek::where('tvde_month_id', $tvde_month_id)
                ->whereHas('tvdeActivities', function ($activity) use ($company_id) {
                    $activity->where('company_id', $company_id);
                })
                ->orderBy('number', 'desc')
                ->first();

            $tvde_week_id = $tvde_week?->id ?? 1;

            // Salva na sessão para manter consistência
            session()->put('tvde_week_id', $tvde_week_id);
        }

        $tvde_years = TvdeYear::orderBy('name')
            ->whereHas('months', function ($month) use ($company_id) {
                $month->whereHas('weeks', function ($week) use ($company_id) {
                    $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                        $tvdeActivity->where('company_id', $company_id);
                    });
                });
            })->get();

        $tvde_months = TvdeMonth::orderBy('number', 'asc')
            ->where('year_id', $tvde_year_id)
            ->whereHas('weeks', function ($week) use ($company_id) {
                $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                    $tvdeActivity->where('company_id', $company_id);
                });
            })->get();

        $tvde_weeks = TvdeWeek::orderBy('number', 'asc')
            ->where('tvde_month_id', $tvde_month_id)
            ->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                $tvdeActivity->where('company_id', $company_id);
            })->get();

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $drivers = Driver::where('company_id', $company_id)
            ->where('state_id', 1)
            ->orderBy('name')
            ->get();

        return [
            'company_id' => $company_id,
            'tvde_year_id' => $tvde_year_id,
            'tvde_years' => $tvde_years,
            'tvde_month_id' => $tvde_month_id,
            'tvde_months' => $tvde_months,
            'tvde_week_id' => $tvde_week_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week' => $tvde_week,
            'drivers' => $drivers,
        ];
    }


    public function getWeekResults($tvde_week_id, $driver_id, $company_id)
    {

        if ($driver_id != 0) {
            $driver = Driver::find($driver_id)->load([
                'contract_type',
                'contract_vat'
            ]);
        } else {
            $driver = null;
        }

        $tvde_week = TvdeWeek::find($tvde_week_id);

        if ($driver_id == 0) {
            $bolt_activities = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 2,
                'company_id' => $company_id,
            ])
                ->get();

            $uber_activities = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 1,
                'company_id' => $company_id,
            ])
                ->get();
        } else {
            $bolt_activities = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 2,
                'driver_code' => $driver->bolt_name,
                'company_id' => $company_id,
            ])
                ->get();

            $uber_activities = TvdeActivity::where([
                'tvde_week_id' => $tvde_week_id,
                'tvde_operator_id' => 1,
                'driver_code' => $driver->uber_uuid,
                'company_id' => $company_id,
            ])
                ->get();
        }

        $adjustments = Adjustment::whereHas('drivers', function ($query) use ($driver_id) {
            $query->where('id', $driver_id);
        })
            ->where(function ($query) use ($tvde_week) {
                $query->where('start_date', '<=', $tvde_week->start_date)
                    ->orWhereNull('start_date');
            })
            ->where(function ($query) use ($tvde_week) {
                $query->where('end_date', '>=', $tvde_week->end_date)
                    ->orWhereNull('end_date');
            })
            ->get();

        $refund = 0;
        $deduct = 0;

        foreach ($adjustments as $adjustment) {
            switch ($adjustment->type) {
                case 'refund':
                    $refund = $refund + $adjustment->amount;
                    break;
                case 'deduct':
                    $deduct = $deduct + $adjustment->amount;
                    break;
            }
        }

        // FUEL EXPENSES

        $electric_expenses = null;
        if ($driver && $driver->electric_id) {
            $electric = Electric::find($driver->electric_id);
            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id
                ])->get();
                $electric_expenses = [
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', '') . ' €',
                    'value' => $electric_transactions->sum('total')
                ];
            }
        }

        $combustion_expenses = null;
        if ($driver && $driver->card_id) {
            $card = Card::find($driver->card_id);
            if (!$card) {
                $code = 0;
            } else {
                $code = $card->code;
            }
            $combustion_transactions = CombustionTransaction::where([
                'card' => $code,
                'tvde_week_id' => $tvde_week_id
            ])->get();
            $combustion_expenses = [
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_transactions->sum('total'), 2, '.', '') . ' €',
                'value' => $combustion_transactions->sum('total')
            ];
        }

        // TOLL EXPENSES

        $toll_payments = null;
        if ($driver && $driver->tool_card_id) {
            $toll_card = TollCard::find($driver->tool_card_id);
            $toll_payments = TollPayment::where([
                'card' => $toll_card->code,
                'tvde_week_id' => $tvde_week_id
            ])->get();
        }

        $total_earnings_bolt = number_format($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one'), 2);
        $total_tips_bolt = number_format($bolt_activities->sum('earnings_one'), 2);
        $total_earnings_uber = number_format($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one'), 2);
        $total_tips_uber = number_format($uber_activities->sum('earnings_one'), 2);
        $total_tips = $total_tips_uber + $total_tips_bolt;
        $total_earnings = $bolt_activities->sum('earnings_two') + $uber_activities->sum('earnings_two');
        $total_earnings_no_tip = ($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) + ($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one'));

        //CHECK PERCENT
        $contract_type_ranks = $driver ? ContractTypeRank::where('contract_type_id', $driver->contract_type_id)->get() : [];
        $contract_type_rank = count($contract_type_ranks) > 0 ? $contract_type_ranks[0] : null;
        foreach ($contract_type_ranks as $value) {
            if ($value->from <= $total_earnings && $value->to >= $total_earnings) {
                $contract_type_rank = $value;
            }
        }

        //

        $total_bolt = ($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);
        $total_uber = ($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);

        $total_earnings_after_vat = $total_bolt + $total_uber;

        $total_bolt = number_format(($bolt_activities->sum('earnings_two') - $bolt_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);
        $total_uber = number_format(($uber_activities->sum('earnings_two') - $uber_activities->sum('earnings_one')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);

        $bolt_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;
        $uber_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;

        $bolt_tip_after_vat = number_format($total_tips_bolt * ($bolt_tip_percent / 100), 2);
        $uber_tip_after_vat = number_format($total_tips_uber * ($uber_tip_percent / 100), 2);

        $total_tip_after_vat = $bolt_tip_after_vat + $uber_tip_after_vat;

        $total = $total_earnings + $total_tips;
        $total_after_vat = $total_earnings_after_vat + $total_tip_after_vat;

        $gross_credits = $total_earnings_no_tip + $total_tips + $refund;
        $gross_debts = ($total_earnings_no_tip - $total_earnings_after_vat) + ($total_tips - $total_tip_after_vat) + $deduct;

        $final_total = $gross_credits - $gross_debts;

        $electric_racio = null;
        $combustion_racio = null;

        if ($electric_expenses) {
            $final_total = $final_total - $electric_expenses['value'];
            $gross_debts = $gross_debts + $electric_expenses['value'];
            if ($electric_expenses['value'] > 0) {
                if ($total_earnings > 0) {
                    $electric_racio = ($electric_expenses['value'] / $total_earnings) * 100;
                } else {
                    $electric_racio = 0;
                }
            } else {
                $electric_racio = 0;
            }
        }
        if ($combustion_expenses) {
            $final_total = $final_total - $combustion_expenses['value'];
            $gross_debts = $gross_debts + $combustion_expenses['value'];
            if ($combustion_expenses['value'] > 0) {
                if ($total_earnings > 0) {
                    $combustion_racio = ($combustion_expenses['value'] / $total_earnings) * 100;
                } else {
                    $combustion_racio = 0;
                }
            } else {
                $combustion_racio = 0;
            }
        }

        if ($toll_payments) {
            $final_total = $final_total - $toll_payments->sum('total');
            $gross_debts = $gross_debts + $toll_payments->sum('total');
        }

        if ($driver && $driver->contract_vat->percent && $driver->contract_vat->percent > 0 && $final_total > 0) {
            $txt_admin = ($final_total * $driver->contract_vat->percent) / 100;
            $gross_debts = $gross_debts + $txt_admin;
            $final_total = $final_total - $txt_admin;
        } else {
            $txt_admin = 0;
        }

        return [
            'bolt_activities' => $bolt_activities,
            'uber_activities' => $uber_activities,
            'total_earnings_uber' => $total_earnings_uber,
            'contract_type_rank' => $contract_type_rank,
            'total_uber' => $total_uber,
            'total_earnings_bolt' => $total_earnings_bolt,
            'total_bolt' => $total_bolt,
            'total_tips_uber' => $total_tips_uber,
            'uber_tip_percent' => $total_tips_uber,
            'uber_tip_after_vat' => $uber_tip_after_vat,
            'total_tips_bolt' => $total_tips_bolt,
            'bolt_tip_percent' => $bolt_tip_percent,
            'bolt_tip_after_vat' => $bolt_tip_after_vat,
            'total_tips' => $total_tips,
            'total_tip_after_vat' => $total_tip_after_vat,
            'adjustments' => $adjustments,
            'total_earnings' => $total_earnings,
            'total_earnings_no_tip' => $total_earnings_no_tip,
            'total' => $total,
            'total_after_vat' => $total_after_vat,
            'gross_credits' => $gross_credits,
            'gross_debts' => $gross_debts,
            'final_total' => $final_total,
            'driver' => $driver,
            'electric_expenses' => $electric_expenses,
            'combustion_expenses' => $combustion_expenses,
            'combustion_racio' => $combustion_racio,
            'electric_racio' => $electric_racio,
            'total_earnings_after_vat' => $total_earnings_after_vat,
            'txt_admin' => $txt_admin,
            'toll_payments' => $toll_payments
        ];
    }
}
