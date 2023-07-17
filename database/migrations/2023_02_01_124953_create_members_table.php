<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('orbit_id');
            $table->string('name');
            $table->foreignId('region_id')->references('id')->on('regions');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('members');
    }
};
