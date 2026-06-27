<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content'); // HTML
            $table->string('version', 20)->default('1.0');
            $table->date('effective_date');
            $table->timestamps(); // updated_at serves as last_updated
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
