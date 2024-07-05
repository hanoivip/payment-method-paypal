<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaypalTransactions extends Migration
{
    public function up()
    {
        Schema::create('paypal_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trans')->comment('Transaction ID = Purchase token = Receipt ID = Invoice ID..');
            $table->string('payment_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('state')->nullable()->comment('created, approved, failed');
            $table->string('payer_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypal_transactions');
    }
}
