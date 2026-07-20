<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulfillmentAndDeliveryFieldsToMerchants extends Migration
{
    public function up()
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'accepts_pickup')) {
                $table->boolean('accepts_pickup')->default(true)->after('is_open');
            }

            if (!Schema::hasColumn('merchants', 'accepts_delivery')) {
                $table->boolean('accepts_delivery')->default(true)->after('accepts_pickup');
            }

            if (!Schema::hasColumn('merchants', 'max_delivery_distance_km')) {
                $table->decimal('max_delivery_distance_km', 8, 2)->nullable()->after('accepts_delivery');
            }

            if (!Schema::hasColumn('merchants', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('max_delivery_distance_km');
            }

            if (!Schema::hasColumn('merchants', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (!Schema::hasColumn('merchants', 'minimum_order_amount')) {
                $table->decimal('minimum_order_amount', 10, 2)->default(0)->after('longitude');
            }
        });

        Schema::table('merchant_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_orders', 'fulfillment_type')) {
                $table->string('fulfillment_type', 20)->default('delivery')->after('status');
            }

            if (!Schema::hasColumn('merchant_orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('subtotal_amount');
            }

            if (!Schema::hasColumn('merchant_orders', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)->default(0)->after('subtotal');
            }

            if (!Schema::hasColumn('merchant_orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_fee');
            }

            if (!Schema::hasColumn('merchant_orders', 'total')) {
                $table->decimal('total', 10, 2)->default(0)->after('total_amount');
            }

            if (!Schema::hasColumn('merchant_orders', 'delivery_address_id')) {
                $table->unsignedBigInteger('delivery_address_id')->nullable()->after('delivery_address');
            }

            if (!Schema::hasColumn('merchant_orders', 'pickup_time')) {
                $table->timestamp('pickup_time')->nullable()->after('delivery_instructions');
            }

            if (!Schema::hasColumn('merchant_orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 7)->nullable()->after('pickup_time');
            }

            if (!Schema::hasColumn('merchant_orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            }
        });

        Schema::table('merchant_orders', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_orders', 'delivery_address_id')) {
                $table->foreign('delivery_address_id')
                    ->references('id')
                    ->on('user_addresses')
                    ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_orders', 'delivery_address_id')) {
                $table->dropForeign(['delivery_address_id']);
            }
        });

        Schema::table('merchant_orders', function (Blueprint $table) {
            $columns = [
                'fulfillment_type',
                'subtotal',
                'delivery_fee',
                'delivery_distance_km',
                'total',
                'delivery_address_id',
                'pickup_time',
                'delivery_latitude',
                'delivery_longitude',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('merchant_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            $columns = [
                'accepts_pickup',
                'accepts_delivery',
                'max_delivery_distance_km',
                'latitude',
                'longitude',
                'minimum_order_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('merchants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
