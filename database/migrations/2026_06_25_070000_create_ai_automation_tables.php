<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 32)->default('agente');
            $table->string('event_type', 80);
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('status', 32)->default('queued');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['audience', 'event_type']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_event_id')->nullable()->constrained('ai_events')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 32)->default('agente');
            $table->string('scope', 80)->default('dashboard');
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('priority', 24)->default('media');
            $table->string('status', 32)->default('new');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('next_action')->nullable();
            $table->json('actions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['audience', 'scope', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_suggestion_id')->nullable()->constrained('ai_suggestions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 80);
            $table->string('status', 32)->default('logged');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action_type']);
            $table->index(['ai_suggestion_id', 'status']);
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 32)->default('agente');
            $table->string('scope', 80)->default('global');
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('prompt');
            $table->longText('answer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scope']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_actions');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_events');
    }
};
