<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('page_slug', 100)->nullable();
            $table->string('company', 255);
            $table->string('industry', 255)->nullable();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->text('message');
            $table->string('send_to_email', 255)->nullable();
            $table->string('mail_status', 20)->default('skipped');
            $table->timestamp('mailed_at')->nullable();
            $table->text('mail_error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_form_submissions');
    }
};

