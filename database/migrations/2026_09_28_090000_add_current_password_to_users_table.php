<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Encrypted (reversible via APP_KEY), NOT plaintext. Admin explicitly requested
            // being able to see an affiliate's current password (even after the affiliate
            // changes it themselves) for support purposes, accepting the security trade-off
            // that this is reversible — as opposed to the one-way bcrypt `password` hash used
            // for actual authentication, which this column never replaces.
            $table->text('current_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('current_password');
        });
    }
};
