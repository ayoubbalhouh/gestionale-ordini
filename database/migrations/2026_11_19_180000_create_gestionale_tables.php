<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('ragione_sociale');
            $table->string('piva', 11)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 30)->unique();
            $table->string('nome');
            $table->string('descrizione')->nullable();
            $table->unsignedInteger('prezzo_cents');
            $table->unsignedTinyInteger('iva')->default(22);
            $table->integer('giacenza')->default(0);
            $table->unsignedInteger('scorta_minima')->default(0);
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anno');
            $table->unsignedInteger('progressivo');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stato', 20)->default('bozza')->index();
            $table->date('data_consegna')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('imponibile_cents')->default(0);
            $table->unsignedBigInteger('iva_cents')->default(0);
            $table->timestamp('confermato_at')->nullable();
            $table->timestamp('spedito_at')->nullable();
            $table->timestamp('consegnato_at')->nullable();
            $table->timestamp('annullato_at')->nullable();
            $table->timestamps();
            $table->unique(['anno', 'progressivo']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('descrizione');
            $table->unsignedInteger('quantita');
            $table->unsignedInteger('prezzo_cents');
            $table->unsignedTinyInteger('iva');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantita'); // positiva = entrata, negativa = uscita
            $table->string('tipo', 20); // carico, ordine, annullo, rettifica
            $table->string('nota')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
    }
};
