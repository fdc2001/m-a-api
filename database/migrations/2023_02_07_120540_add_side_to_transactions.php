<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('side')->after('approved')->nullable();
        });
    }

    public function down() {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('side');
        });
    }
};
