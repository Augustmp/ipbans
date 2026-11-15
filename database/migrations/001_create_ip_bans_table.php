<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_bans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip', 64)->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedInteger('banned_by')->nullable();
            $table->foreign('banned_by')->references('id')->on('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('unbanned_at')->nullable()->index();
            $table->unsignedInteger('unbanned_by')->nullable();
            $table->foreign('unbanned_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_bans');
    }
};
