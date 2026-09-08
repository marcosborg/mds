<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TollPayment extends Model
{
    use SoftDeletes, HasFactory;

    protected static function booted()
    {
        static::saving(function (self $payment) {
            if (!$payment->exists || $payment->isDirty('card') || !$payment->driver_id) {
                $payment->card = trim((string) $payment->card);
                $payment->driver_id = app(\App\Services\TollPaymentMatcher::class)->resolve($payment->card)->id;
            }
        });
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public $table = 'toll_payments';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'tvde_week_id',
        'card',
        'total',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function tvde_week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }
}
