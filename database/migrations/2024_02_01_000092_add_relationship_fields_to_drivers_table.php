<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToDriversTable extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->unsignedBigInteger('tool_card_id')->nullable();
            $table->foreign('tool_card_id', 'tool_card_fk_9398952')->references('id')->on('toll_cards');
        });
    }
}