<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchantCategoriesAndRestaurantFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('position')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->unique(['merchant_id', 'slug']);
        });

        Schema::table('merchant_products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('merchant_id');

            $table->foreign('category_id')->references('id')->on('merchant_categories')->onDelete('set null');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('slug');
            $table->string('cover_image')->nullable()->after('logo');
            $table->json('opening_hours')->nullable()->after('description');
            $table->boolean('is_open')->nullable()->after('opening_hours');
        });

        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->text('delivery_address_line2')->nullable()->after('delivery_address');
            $table->string('delivery_city')->nullable()->after('delivery_address_line2');
            $table->string('delivery_postal_code')->nullable()->after('delivery_city');
            $table->text('delivery_instructions')->nullable()->after('delivery_postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address_line2',
                'delivery_city',
                'delivery_postal_code',
                'delivery_instructions',
            ]);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'logo',
                'cover_image',
                'opening_hours',
                'is_open',
            ]);
        });

        Schema::table('merchant_products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('merchant_categories');
    }
}
