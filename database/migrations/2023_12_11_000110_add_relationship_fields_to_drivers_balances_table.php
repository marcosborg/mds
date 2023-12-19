<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToDriversBalancesTable extends Migration
{
    public function up()
    {
        Schema::table('drivers_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->foreign('driver_id', 'driver_fk_9294981')->references('id')->on('drivers');
            $table->unsignedBigInteger('tvde_week_id')->nullable();
            $table->foreign('tvde_week_id', 'tvde_week_fk_9294983')->references('id')->on('tvde_weeks');
        });
    }
}
