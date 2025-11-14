<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_verifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('voter_verifications_user_id_unique');
            $table->foreignId('election_id')->after('user_id')->constrained('elections')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'election_id']);
        });
    }

    public function down(): void
    {
        Schema::table('voter_verifications', function (Blueprint $table) {
            $table->dropUnique('voter_verifications_user_id_election_id_unique');
            $table->dropForeign(['election_id']);
            $table->dropColumn('election_id');
            $table->dropForeign(['user_id']);
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};