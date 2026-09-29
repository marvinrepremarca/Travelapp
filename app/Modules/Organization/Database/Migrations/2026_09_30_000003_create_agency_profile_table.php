<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agency_profile', function (Blueprint $table): void {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('nit', 15);
            $table->unsignedTinyInteger('nit_check_digit');
            $table->string('rnt_number', 20);
            $table->date('rnt_expires_on');
            $table->string('address');
            $table->string('city', 100);
            $table->string('phone', 30);
            $table->string('email');
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->char('brand_primary_color', 7)->nullable();
            $table->char('brand_accent_color', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_profile');
    }
};
