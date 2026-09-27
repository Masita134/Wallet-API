<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('type', ['savings', 'checking'])
                ->default('savings')
                ->after('balance');

            $table->enum('currency', ['ARS', 'USD'])
                ->default('ARS')
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'currency']);
        });
    }
};