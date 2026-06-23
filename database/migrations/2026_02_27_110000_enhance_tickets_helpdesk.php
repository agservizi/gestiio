<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'priorita')) {
                $table->string('priorita', 20)->default('media')->after('stato')->index();
            }
            if (! Schema::hasColumn('tickets', 'owner_team')) {
                $table->string('owner_team', 30)->default('helpdesk')->after('priorita')->index();
            }
            if (! Schema::hasColumn('tickets', 'first_response_due_at')) {
                $table->dateTime('first_response_due_at')->nullable()->after('owner_team');
            }
            if (! Schema::hasColumn('tickets', 'resolution_due_at')) {
                $table->dateTime('resolution_due_at')->nullable()->after('first_response_due_at');
            }
            if (! Schema::hasColumn('tickets', 'first_response_at')) {
                $table->dateTime('first_response_at')->nullable()->after('resolution_due_at');
            }
            if (! Schema::hasColumn('tickets', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('first_response_at');
            }
            if (! Schema::hasColumn('tickets', 'last_customer_message_at')) {
                $table->dateTime('last_customer_message_at')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('tickets', 'last_agent_message_at')) {
                $table->dateTime('last_agent_message_at')->nullable()->after('last_customer_message_at');
            }
            if (! Schema::hasColumn('tickets', 'escalated_at')) {
                $table->dateTime('escalated_at')->nullable()->after('last_agent_message_at');
            }
            if (! Schema::hasColumn('tickets', 'automation_notes')) {
                $table->json('automation_notes')->nullable()->after('escalated_at');
            }
        });

        if (! Schema::hasTable('ticket_status_logs')) {
            Schema::create('ticket_status_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_state', 50)->nullable();
                $table->string('to_state', 50);
                $table->string('note', 255)->nullable();
                $table->index(['ticket_id', 'created_at']);
            });
        }

        DB::table('tickets')->where('stato', 'aperto')->update(['stato' => 'nuovo']);
        DB::table('tickets')->whereNull('priorita')->update(['priorita' => 'media']);
        DB::table('tickets')->whereNull('owner_team')->update(['owner_team' => 'helpdesk']);
    }

    public function down()
    {
        if (Schema::hasTable('ticket_status_logs')) {
            Schema::dropIfExists('ticket_status_logs');
        }

        Schema::table('tickets', function (Blueprint $table) {
            foreach ([
                'automation_notes',
                'escalated_at',
                'last_agent_message_at',
                'last_customer_message_at',
                'resolved_at',
                'first_response_at',
                'resolution_due_at',
                'first_response_due_at',
                'owner_team',
                'priorita',
            ] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
