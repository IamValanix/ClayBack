<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Relación con la orden. Si se borra la orden, se borra el ticket.
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Código único que irá en el QR (ej: TKT-654321-ABC)
            $table->string('ticket_code')->unique();

            // Control de acceso para el evento
            $table->boolean('is_used')->default(false);
            $table->timestamp('scanned_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
