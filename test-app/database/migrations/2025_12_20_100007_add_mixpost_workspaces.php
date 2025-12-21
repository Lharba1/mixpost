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
        // Workspaces
        Schema::create('mixpost_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#6366f1');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Workspace members
        Schema::create('mixpost_workspace_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, admin, member, viewer
            $table->json('permissions')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            
            $table->unique(['workspace_id', 'user_id']);
        });

        // Workspace invitations
        Schema::create('mixpost_workspace_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('role')->default('member');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->index(['workspace_id', 'email']);
        });

        // Add workspace_id to accounts
        if (!Schema::hasColumn('mixpost_accounts', 'workspace_id')) {
            Schema::table('mixpost_accounts', function (Blueprint $table) {
                $table->foreignId('workspace_id')->nullable()->after('id');
            });
        }

        // Add workspace_id to posts
        if (!Schema::hasColumn('mixpost_posts', 'workspace_id')) {
            Schema::table('mixpost_posts', function (Blueprint $table) {
                $table->foreignId('workspace_id')->nullable()->after('id');
            });
        }

        // User's current workspace preference
        if (!Schema::hasColumn('users', 'current_workspace_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_workspace_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove workspace columns
        if (Schema::hasColumn('users', 'current_workspace_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('current_workspace_id');
            });
        }

        if (Schema::hasColumn('mixpost_posts', 'workspace_id')) {
            Schema::table('mixpost_posts', function (Blueprint $table) {
                $table->dropColumn('workspace_id');
            });
        }

        if (Schema::hasColumn('mixpost_accounts', 'workspace_id')) {
            Schema::table('mixpost_accounts', function (Blueprint $table) {
                $table->dropColumn('workspace_id');
            });
        }

        Schema::dropIfExists('mixpost_workspace_invites');
        Schema::dropIfExists('mixpost_workspace_members');
        Schema::dropIfExists('mixpost_workspaces');
    }
};
