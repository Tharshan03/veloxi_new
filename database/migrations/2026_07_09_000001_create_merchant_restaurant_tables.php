<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantRestaurantTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });

        Schema::create('merchant_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('merchant_id');
            $table->enum('status', [
                'pending',
                'accepted',
                'refused',
                'preparing',
                'ready',
                'delivery_requested',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->decimal('subtotal_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('delivery_address')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });

        Schema::create('merchant_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_order_id');
            $table->unsignedBigInteger('merchant_product_id')->nullable();
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('merchant_order_id')->references('id')->on('merchant_orders')->onDelete('cascade');
            $table->foreign('merchant_product_id')->references('id')->on('merchant_products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_order_items');
        Schema::dropIfExists('merchant_orders');
        Schema::dropIfExists('merchant_products');
        Schema::dropIfExists('merchants');
    }
}
