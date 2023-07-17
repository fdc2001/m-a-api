<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('orbit_id');
            $table->string('buyer_logo');
            $table->string('type_of_transaction');
            $table->foreignId('industry_sector')->references('id')->on('industry_sectors');
            $table->longText('detailed_business_desc');
            $table->string('transaction_size');
            $table->foreignId('member_id')->references('id')->on('members');
            $table->string('deal_manager');
            $table->string('tombstone_title');
            $table->longText('transaction_excerpt');
            $table->string('keyphrase');
            $table->string('tombstone_top_image');
            $table->string('tombstone_bottom_image');
            $table->boolean('approved')->default(false);
            $table->string('slug');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('transactions');
    }
};
