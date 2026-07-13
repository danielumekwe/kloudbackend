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
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_retired')->default(false);
            $table->timestamps();

            $table->unique(['type', 'key']);
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
