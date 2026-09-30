<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('city', 100)->nullable()->after('name');
            $table->string('address')->nullable()->after('city');
            $table->string('phone', 30)->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->unsignedBigInteger('manager_id')->nullable()->index()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropIndex(['manager_id']);
            $table->dropColumn(['city', 'address', 'phone', 'email', 'manager_id']);
        });
    }
};
