<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_contacts', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('phone');
        });

        Schema::table('supplier_bank_accounts', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contacts', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('supplier_bank_accounts', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
