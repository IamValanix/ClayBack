<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->decimal('amount', 8, 2); // Hasta 999,999.99
            $table->string('currency', 3)->default('USD');

            // Estado del pago: pending, completed, failed
            $table->string('status')->default('pending');

            // Pasarela: 'stripe' o 'paypal'
            $table->string('payment_gateway');
            // ID único que nos devuelve Stripe o PayPal para rastreo
            $table->string('payment_id')->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
