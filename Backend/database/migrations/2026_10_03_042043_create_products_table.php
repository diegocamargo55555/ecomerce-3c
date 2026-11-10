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
        Schema::create('products', function (Blueprint $table) {
            // id bigint (Primary Key, Auto-increment)
            $table->id();

            // category_id bigint NN (Chave estrangeira para a tabela categories)
            $table->foreignId('category_id')->constrained('categories');

            // name varchar(255) NN
            $table->string('name', 255);

            // description text NN
            $table->text('description');

            // price decimal(10,2) NN
            $table->decimal('price', 10, 2);

            // stock int unsigned NN (com valor por defeito opcional de 0)
            $table->unsignedInteger('stock')->default(0); // rede de segurança: o MySQL recusa venda que deixaria o estoque negativo

            // image_url varchar(255) (Nullable, pois não tem a marcação NN)
            $table->string('image_url', 255)->nullable();

            // is_active boolean NN (com valor por defeito opcional de true)
            $table->boolean('is_active')->default(true);

            // created_at e updated_at timestamp
            $table->timestamps();

            $table->softDeletes();  
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
