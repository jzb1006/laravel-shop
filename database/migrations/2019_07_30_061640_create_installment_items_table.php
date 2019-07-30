<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstallmentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('installment_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('installment_id');
            $table->unsignedInteger('sequence')->comment('还款顺序编号');
            $table->decimal('base')->comment('当前本金');
            $table->decimal('fee')->comment('当前手续费');
            $table->decimal('fine')->nullable()->comment('当前逾期费');
            $table->dateTime('due_date')->comment('还款到期时间');
            $table->dateTime('paid_at')->nullable()->comment('还款日期');
            $table->string('payment_methods')->nullable();
            $table->string('payment_no')->nullable();
            $table->string('refund_status')->default(\App\Models\InstallmentItem::REFUND_STATUS_PENDING);
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
        Schema::dropIfExists('installment_items');
    }
}
