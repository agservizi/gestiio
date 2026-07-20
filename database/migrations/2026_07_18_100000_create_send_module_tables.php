<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('send_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('send_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('request_number', 32)->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('applicant_type', 40);
            $table->string('status', 40)->default('draft')->index();
            $table->string('priority', 20)->default('normale')->index();
            $table->string('send_notice_identifier')->nullable()->index();
            $table->string('iun')->nullable()->index();
            $table->string('sender_entity')->nullable();
            $table->date('notice_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->unsignedSmallInteger('notice_pages')->nullable();
            $table->string('communication_type')->nullable();
            $table->text('initial_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('integration_reason')->nullable();
            $table->string('integration_category')->nullable();
            $table->timestamp('integration_due_at')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('taken_in_charge_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_by', 'status']);
            $table->index(['assigned_supervisor_id', 'status']);
        });

        Schema::create('send_request_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->string('subject_role', 40); // destinatario|delegato|impresa|rappresentante
            $table->string('subject_type', 20)->default('persona'); // persona|impresa
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('tax_code', 32)->nullable()->index();
            $table->string('vat_number', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('document_issued_by')->nullable();
            $table->date('document_issue_date')->nullable();
            $table->date('document_expiry_date')->nullable();
            $table->string('representative_role')->nullable();
            $table->string('relationship')->nullable();
            $table->date('delegation_date')->nullable();
            $table->date('delegation_expiry')->nullable();
            $table->string('pec')->nullable();
            $table->timestamps();

            $table->unique(['send_request_id', 'subject_role']);
        });

        Schema::create('send_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->string('category', 60)->index();
            $table->string('visibility', 30)->default('operator'); // operator|supervisor|citizen_receipt
            $table->string('disk', 40)->default('sensitive');
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 120)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('hash', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('antivirus_status', 30)->default('skipped');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('send_request_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('label');
            $table->boolean('required')->default(true);
            $table->boolean('completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['send_request_id', 'code']);
        });

        Schema::create('send_request_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['send_request_id', 'created_at']);
        });

        Schema::create('send_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_method', 40);
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('send_request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('note_type', 40)->default('operative');
            $table->string('visibility', 30)->default('operator'); // internal|operator|citizen
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('send_request_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->string('consent_type', 60);
            $table->string('privacy_version', 40)->nullable();
            $table->boolean('accepted')->default(false);
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['send_request_id', 'consent_type']);
        });

        Schema::create('send_request_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->constrained('send_requests')->cascadeOnDelete();
            $table->foreignId('delivered_by')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_type', 40)->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('delivery_method', 60)->default('sportello');
            $table->string('identification_type')->nullable();
            $table->string('document_verified')->nullable();
            $table->timestamp('delivered_at');
            $table->text('documents_summary')->nullable();
            $table->json('confirmation_data')->nullable();
            $table->boolean('print_done')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('send_request_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_request_id')->nullable()->constrained('send_requests')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['send_request_id', 'created_at']);
        });

        Schema::create('send_number_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('send_request_audit_logs');
        Schema::dropIfExists('send_request_deliveries');
        Schema::dropIfExists('send_request_consents');
        Schema::dropIfExists('send_request_notes');
        Schema::dropIfExists('send_request_assignments');
        Schema::dropIfExists('send_request_status_history');
        Schema::dropIfExists('send_request_checklist_items');
        Schema::dropIfExists('send_request_documents');
        Schema::dropIfExists('send_request_subjects');
        Schema::dropIfExists('send_requests');
        Schema::dropIfExists('send_settings');
        Schema::dropIfExists('send_number_counters');
    }
};
