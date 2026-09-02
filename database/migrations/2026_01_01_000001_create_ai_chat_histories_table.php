<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('ai_chat_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->index();
            $table->text('user_message');
            $table->text('bot_reply')->nullable();
            $table->boolean('requires_admin')->default(false);
            $table->timestamp('admin_replied_at')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('ai_chat_histories'); }
};