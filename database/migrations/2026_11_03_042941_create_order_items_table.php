<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            // id bigint (Primary Key, Auto-increment)
            $table->id();

            // order_id bigint NN (Chave estrangeira para a tabela orders)
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // product_id bigint NN (Chave estrangeira para a tabela products)
            $table->foreignId('product_id')->constrained('products');

            // product_name varchar(255) NN
            $table->string('product_name', 255);

            // unit_price decimal(10,2) NN
            $table->decimal('unit_price', 10, 2);

            // quantity int NN
            $table->integer('quantity');

            // subtotal decimal(10,2) NN
            $table->decimal('subtotal', 10, 2);

            // created_at e updated_at timestamp
            $table->timestamps();
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
