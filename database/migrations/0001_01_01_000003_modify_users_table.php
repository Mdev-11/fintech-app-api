<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_document_type')->nullable(); // passport, national_id, etc.
            $table->string('id_document_number')->nullable()->unique();
            $table->string('id_document_path')->nullable(); // path to stored document
            $table->string('face_image_path')->nullable(); // path to stored face image
            $table->string('facial_recognition_id')->nullable(); // ID from face recognition service
            $table->string('phone_number')->nullable()->unique();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('device_token')->nullable(); // For push notifications
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'id_document_type',
                'id_document_number',
                'id_document_path',
                'facial_recognition_id',
                'wallet_balance',
                'phone_number',
                'is_verified',
                'last_login_at',
                'device_token'
            ]);
        });
    }
}; 