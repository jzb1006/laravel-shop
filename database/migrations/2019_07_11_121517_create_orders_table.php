<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('no')->unique();
            $table->unsignedInteger('user_id');
            $table->text('address');
            $table->decimal('total_amount');
            $table->text('remark')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_no')->nullable();
            $table->string('refund_status')->default(\App\Models\Order::REFUND_STATUS_PENDING);
            $table->string('refund_no')->nullable();
            $table->boolean('closed')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->string('ship_status')->default(\App\Models\Order::SHIP_STATUS_PENDING);
            $table->text('ship_data')->nullable();
            $table->text('extra')->nullable();
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
        Schema::dropIfExists('orders');
    }
}