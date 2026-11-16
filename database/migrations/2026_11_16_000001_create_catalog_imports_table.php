<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_user_id')->nullable()->index();
            $table->string('admin_name', 255)->nullable();
            $table->string('file_name', 255);
            $table->boolean('create_missing')->default(false);
            $table->json('summary');
            $table->string('report_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_imports');
    }
};
