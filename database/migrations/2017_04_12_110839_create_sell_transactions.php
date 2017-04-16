<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('sell_transactions', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->string('platform');
        $table->string('genre');
        $table->integer('min_rate')->unsigned();
        $table->integer('max_rate')->unsigned();
        $table->string('img_path');
        $table->integer('customer_id');
        $table->integer('transaction_id');
        $table->integer('key');
        $table->string('status');
        $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sell_transactions');
    }
}
