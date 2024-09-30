<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('main_id', 256)->index();
            $table->string('barcode', 256);
            $table->string('title', 256);
            $table->longText('description')->nullable();
            $table->decimal('sale_price', 16, 2)->index();
            $table->string('stock_unit', 50);
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('approved')->default(0)->index();
            $table->longText('attrs')->nullable();
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
        Schema::dropIfExists('products');
    }
};
