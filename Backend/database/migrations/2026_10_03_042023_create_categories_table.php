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
        Schema::create('categories', function (Blueprint $table) {
            // id bigint (Primary Key, Auto-increment)
            $table->id();

            // name varchar(255) NN
            $table->string('name', 255);

            // slug varchar(255) NN (Unique)
            $table->string('slug', 255)->unique();

            // description text (Nullable, pois não tem a marcação NN)
            $table->text('description')->nullable();

            // created_at e updated_at timestamp
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
