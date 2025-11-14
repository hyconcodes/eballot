<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voter_verifications', function (Blueprint $table) {
            $table->string('state', 64)->nullable()->after('voters_card_back_path');
        });
    }

    public function down(): void
    {
        Schema::table('voter_verifications', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};