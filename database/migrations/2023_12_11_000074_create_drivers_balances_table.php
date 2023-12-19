<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('drivers_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('value', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
