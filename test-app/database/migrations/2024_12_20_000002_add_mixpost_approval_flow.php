<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Approval workflows (templates for approval requirements)
        Schema::create('mixpost_approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('required_approvals')->default(1); // Number of approvals needed
            $table->boolean('require_all')->default(false); // Require all assigned approvers or just required_approvals count
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Approval requests for posts
        Schema::create('mixpost_post_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('mixpost_posts')->cascadeOnDelete();
            $table->foreignId('workflow_id')->nullable()->constrained('mixpost_approval_workflows')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('notes')->nullable();
            $table->integer('approvals_received')->default(0);
            $table->integer('approvals_required')->default(1);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            
            $table->index(['post_id', 'status']);
        });

        // Individual approval decisions
        Schema::create('mixpost_approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('mixpost_post_approvals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision'); // approved, rejected
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->unique(['approval_id', 'user_id']); // One decision per user per approval
        });

        // Users assigned as approvers to workflows
        Schema::create('mixpost_workflow_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('mixpost_approval_workflows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('order')->default(0); // For sequential approval workflows
            $table->timestamps();
            
            $table->unique(['workflow_id', 'user_id']);
        });

        // Add approval status to posts
        Schema::table('mixpost_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('mixpost_posts', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('status');
            }
            if (!Schema::hasColumn('mixpost_posts', 'approval_status')) {
                $table->string('approval_status')->nullable()->after('requires_approval');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mixpost_posts', function (Blueprint $table) {
            if (Schema::hasColumn('mixpost_posts', 'requires_approval')) {
                $table->dropColumn('requires_approval');
            }
            if (Schema::hasColumn('mixpost_posts', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
        
        Schema::dropIfExists('mixpost_workflow_approvers');
        Schema::dropIfExists('mixpost_approval_decisions');
        Schema::dropIfExists('mixpost_post_approvals');
        Schema::dropIfExists('mixpost_approval_workflows');
    }
};
