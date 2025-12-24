<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Controllers\AccountEntitiesController;
use Inovector\Mixpost\Http\Controllers\AccountsController;
use Inovector\Mixpost\Http\Controllers\AddAccountController;
use Inovector\Mixpost\Http\Controllers\AuthenticatedController;
use Inovector\Mixpost\Http\Controllers\CalendarController;
use Inovector\Mixpost\Http\Controllers\CallbackSocialProviderController;
use Inovector\Mixpost\Http\Controllers\CreateMastodonAppController;
use Inovector\Mixpost\Http\Controllers\DashboardController;
use Inovector\Mixpost\Http\Controllers\SystemLogsController;
use Inovector\Mixpost\Http\Controllers\SystemStatusController;
use Inovector\Mixpost\Http\Controllers\UpdateAuthUserController;
use Inovector\Mixpost\Http\Controllers\UpdateAuthUserPasswordController;
use Inovector\Mixpost\Http\Controllers\ProfileController;
use Inovector\Mixpost\Http\Controllers\ReportsController;
use Inovector\Mixpost\Http\Controllers\DeletePostsController;
use Inovector\Mixpost\Http\Controllers\DuplicatePostController;
use Inovector\Mixpost\Http\Controllers\MediaController;
use Inovector\Mixpost\Http\Controllers\MediaDownloadExternalController;
use Inovector\Mixpost\Http\Controllers\MediaFetchGifsController;
use Inovector\Mixpost\Http\Controllers\MediaFetchStockController;
use Inovector\Mixpost\Http\Controllers\MediaFetchUploadsController;
use Inovector\Mixpost\Http\Controllers\MediaUploadFileController;
use Inovector\Mixpost\Http\Controllers\PostsController;
use Inovector\Mixpost\Http\Controllers\SchedulePostController;
use Inovector\Mixpost\Http\Controllers\ServicesController;
use Inovector\Mixpost\Http\Controllers\SettingsController;
use Inovector\Mixpost\Http\Controllers\TagsController;
use Inovector\Mixpost\Http\Controllers\VariablesController;
use Inovector\Mixpost\Http\Controllers\HashtagGroupsController;
use Inovector\Mixpost\Http\Controllers\PostTemplatesController;
use Inovector\Mixpost\Http\Controllers\PostingScheduleController;
use Inovector\Mixpost\Http\Controllers\AIAssistantController;
use Inovector\Mixpost\Http\Controllers\AnalyticsController;
use Inovector\Mixpost\Http\Controllers\PostActivityController;
use Inovector\Mixpost\Http\Controllers\ApprovalController;
use Inovector\Mixpost\Http\Controllers\TranslationController;
use Inovector\Mixpost\Http\Controllers\WebhookController;
use Inovector\Mixpost\Http\Controllers\ApiTokenController;
use Inovector\Mixpost\Http\Controllers\BrandingController;
use Inovector\Mixpost\Http\Controllers\WorkspaceController;
use Inovector\Mixpost\Http\Controllers\ContentRecyclingController;
use Inovector\Mixpost\Http\Middleware\Auth as MixpostAuthMiddleware;
use Inovector\Mixpost\Http\Middleware\HandleInertiaRequests;

Route::middleware([
    'web',
    MixpostAuthMiddleware::class,
    HandleInertiaRequests::class
])->prefix('mixpost')
    ->name('mixpost.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('reports', [ReportsController::class, 'index'])->name('reports');
        Route::get('reports/export', [ReportsController::class, 'export'])->name('reports.export');

        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountsController::class, 'index'])->name('index');
            Route::post('add/{provider}', AddAccountController::class)->name('add');
            Route::put('update/{account}', [AccountsController::class, 'update'])->name('update');
            Route::delete('{account}', [AccountsController::class, 'delete'])->name('delete');

            Route::prefix('entities')->name('entities.')->group(function () {
                Route::get('{provider}', [AccountEntitiesController::class, 'index'])->name('index');
                Route::post('{provider}', [AccountEntitiesController::class, 'store'])->name('store');
            });
        });

        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostsController::class, 'index'])->name('index');
            Route::get('create/{schedule_at?}', [PostsController::class, 'create'])
                ->name('create')
                ->where('schedule_at', '^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]) (0\d|1\d|2[0-3]):([0-5]\d)$');
            Route::post('store', [PostsController::class, 'store'])->name('store');
            Route::get('{post}', [PostsController::class, 'edit'])->name('edit');
            Route::put('{post}', [PostsController::class, 'update'])->name('update');
            Route::delete('{post}', [PostsController::class, 'destroy'])->name('delete');

            Route::post('schedule/{post}', SchedulePostController::class)->name('schedule');
            Route::post('duplicate/{post}', DuplicatePostController::class)->name('duplicate');
            Route::delete('/', DeletePostsController::class)->name('multipleDelete');
        });

        Route::get('calendar/{date?}/{type?}', [CalendarController::class, 'index'])
            ->name('calendar')
            ->where('date', '^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$')
            ->where('type', '^(?:month|week)$');

        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::delete('/', [MediaController::class, 'destroy'])->name('delete');
            Route::get('fetch/uploaded', MediaFetchUploadsController::class)->name('fetchUploads');
            Route::get('fetch/stock', MediaFetchStockController::class)->name('fetchStock');
            Route::get('fetch/gifs', MediaFetchGifsController::class)->name('fetchGifs');
            Route::post('download', MediaDownloadExternalController::class)->name('download');
            Route::post('upload', MediaUploadFileController::class)->name('upload');
        });

        Route::prefix('tags')->name('tags.')->group(function () {
            Route::post('/', [TagsController::class, 'store'])->name('store');
            Route::put('{tag}', [TagsController::class, 'update'])->name('update');
            Route::delete('{tag}', [TagsController::class, 'destroy'])->name('delete');
        });

        Route::prefix('variables')->name('variables.')->group(function () {
            Route::get('/', [VariablesController::class, 'index'])->name('index');
            Route::post('/', [VariablesController::class, 'store'])->name('store');
            Route::put('{variable}', [VariablesController::class, 'update'])->name('update');
            Route::delete('{variable}', [VariablesController::class, 'destroy'])->name('delete');
            Route::get('all', [VariablesController::class, 'all'])->name('all');
            Route::post('preview', [VariablesController::class, 'preview'])->name('preview');
        });

        Route::prefix('hashtag-groups')->name('hashtagGroups.')->group(function () {
            Route::get('/', [HashtagGroupsController::class, 'index'])->name('index');
            Route::post('/', [HashtagGroupsController::class, 'store'])->name('store');
            Route::put('{hashtagGroup}', [HashtagGroupsController::class, 'update'])->name('update');
            Route::delete('{hashtagGroup}', [HashtagGroupsController::class, 'destroy'])->name('delete');
            Route::get('all', [HashtagGroupsController::class, 'all'])->name('all');
        });

        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [PostTemplatesController::class, 'index'])->name('index');
            Route::post('/', [PostTemplatesController::class, 'store'])->name('store');
            Route::put('{postTemplate}', [PostTemplatesController::class, 'update'])->name('update');
            Route::delete('{postTemplate}', [PostTemplatesController::class, 'destroy'])->name('delete');
            Route::get('all', [PostTemplatesController::class, 'all'])->name('all');
            Route::post('save-from-post', [PostTemplatesController::class, 'saveFromPost'])->name('saveFromPost');
        });

        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [PostingScheduleController::class, 'index'])->name('index');
            Route::post('time-slot', [PostingScheduleController::class, 'addTimeSlot'])->name('addTimeSlot');
            Route::delete('time-slot/{time}', [PostingScheduleController::class, 'removeTimeSlot'])->name('removeTimeSlot');
            Route::put('time-slot/{time}/toggle', [PostingScheduleController::class, 'toggleTimeSlot'])->name('toggleTimeSlot');
            Route::post('queue', [PostingScheduleController::class, 'addToQueue'])->name('addToQueue');
            Route::delete('queue/{queueItem}', [PostingScheduleController::class, 'removeFromQueue'])->name('removeFromQueue');
            Route::put('queue/reorder', [PostingScheduleController::class, 'reorderQueue'])->name('reorderQueue');
            Route::put('queue/{queueItem}/retry', [PostingScheduleController::class, 'retryQueueItem'])->name('retryQueueItem');
            Route::get('stats', [PostingScheduleController::class, 'stats'])->name('stats');
        });

        Route::prefix('recycling')->name('recycling.')->group(function () {
            Route::get('/', [ContentRecyclingController::class, 'index'])->name('index');
            Route::post('/', [ContentRecyclingController::class, 'store'])->name('store');
            Route::put('{recyclingPost}', [ContentRecyclingController::class, 'update'])->name('update');
            Route::delete('{recyclingPost}', [ContentRecyclingController::class, 'destroy'])->name('destroy');
            Route::put('{recyclingPost}/toggle', [ContentRecyclingController::class, 'toggle'])->name('toggle');
            Route::post('add-from-post', [ContentRecyclingController::class, 'addFromPost'])->name('addFromPost');
        });

        Route::prefix('ai')->name('ai.')->group(function () {
            Route::post('generate', [AIAssistantController::class, 'generate'])->name('generate');
            Route::post('rewrite', [AIAssistantController::class, 'rewrite'])->name('rewrite');
            Route::post('summarize', [AIAssistantController::class, 'summarize'])->name('summarize');
            Route::post('hashtags', [AIAssistantController::class, 'hashtags'])->name('hashtags');
            Route::post('ideas', [AIAssistantController::class, 'ideas'])->name('ideas');
            Route::post('optimize', [AIAssistantController::class, 'optimize'])->name('optimize');
            Route::get('status', [AIAssistantController::class, 'status'])->name('status');
            Route::get('stats', [AIAssistantController::class, 'stats'])->name('stats');
        });

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('chart-data', [AnalyticsController::class, 'chartData'])->name('chartData');
            Route::get('export', [AnalyticsController::class, 'export'])->name('export');
        });

        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('recent', [PostActivityController::class, 'recent'])->name('recent');
            Route::get('stats', [PostActivityController::class, 'stats'])->name('stats');
            Route::get('posts/{post}', [PostActivityController::class, 'index'])->name('post');
        });

        Route::prefix('approvals')->name('approvals.')->group(function () {
            Route::get('/', [ApprovalController::class, 'index'])->name('index');
            Route::post('posts/{post}/request', [ApprovalController::class, 'request'])->name('request');
            Route::post('{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
            Route::delete('{approval}/cancel', [ApprovalController::class, 'cancel'])->name('cancel');
            
            // Workflows
            Route::get('workflows', [ApprovalController::class, 'workflows'])->name('workflows');
            Route::post('workflows', [ApprovalController::class, 'storeWorkflow'])->name('workflows.store');
            Route::put('workflows/{workflow}', [ApprovalController::class, 'updateWorkflow'])->name('workflows.update');
            Route::delete('workflows/{workflow}', [ApprovalController::class, 'deleteWorkflow'])->name('workflows.delete');
            Route::post('workflows/{workflow}/default', [ApprovalController::class, 'setDefault'])->name('workflows.default');
        });

        Route::prefix('translations')->name('translations.')->group(function () {
            Route::get('/', [TranslationController::class, 'languages'])->name('index');
            Route::get('languages', [TranslationController::class, 'languages'])->name('languages');
            Route::post('languages', [TranslationController::class, 'storeLanguage'])->name('languages.store');
            Route::put('languages/{language}', [TranslationController::class, 'updateLanguage'])->name('languages.update');
            Route::delete('languages/{language}', [TranslationController::class, 'destroyLanguage'])->name('languages.destroy');
            Route::post('languages/{language}/default', [TranslationController::class, 'setDefault'])->name('languages.setDefault');
            
            Route::get('posts/{post}', [TranslationController::class, 'getPostTranslations'])->name('post');
            Route::post('posts/{post}', [TranslationController::class, 'saveTranslation'])->name('post.save');
            Route::post('posts/{post}/auto', [TranslationController::class, 'autoTranslate'])->name('post.auto');
            Route::delete('posts/{post}/{languageCode}', [TranslationController::class, 'deleteTranslation'])->name('post.delete');
            
            Route::post('translate', [TranslationController::class, 'translateText'])->name('text');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
        });

        // Phase 5: Webhooks
        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('index');
            Route::post('/', [WebhookController::class, 'store'])->name('store');
            Route::put('{webhook}', [WebhookController::class, 'update'])->name('update');
            Route::delete('{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('{webhook}/toggle', [WebhookController::class, 'toggle'])->name('toggle');
            Route::post('{webhook}/test', [WebhookController::class, 'test'])->name('test');
            Route::get('{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('deliveries');
            Route::post('deliveries/{delivery}/retry', [WebhookController::class, 'retry'])->name('retry');
        });

        // Phase 5: API Tokens
        Route::prefix('api-tokens')->name('apiTokens.')->group(function () {
            Route::get('/', [ApiTokenController::class, 'index'])->name('index');
            Route::post('/', [ApiTokenController::class, 'store'])->name('store');
            Route::put('{token}', [ApiTokenController::class, 'update'])->name('update');
            Route::delete('{token}', [ApiTokenController::class, 'destroy'])->name('destroy');
            Route::post('{token}/regenerate', [ApiTokenController::class, 'regenerate'])->name('regenerate');
            Route::get('{token}/stats', [ApiTokenController::class, 'stats'])->name('stats');
        });

        // Phase 5: Branding
        Route::prefix('branding')->name('branding.')->group(function () {
            Route::get('/', [BrandingController::class, 'index'])->name('index');
            Route::put('/', [BrandingController::class, 'update'])->name('update');
            Route::post('logo-light', [BrandingController::class, 'uploadLogoLight'])->name('logoLight');
            Route::post('logo-dark', [BrandingController::class, 'uploadLogoDark'])->name('logoDark');
            Route::post('favicon', [BrandingController::class, 'uploadFavicon'])->name('favicon');
            Route::post('login-background', [BrandingController::class, 'uploadLoginBackground'])->name('loginBackground');
            Route::delete('image', [BrandingController::class, 'removeImage'])->name('removeImage');
            Route::post('reset', [BrandingController::class, 'reset'])->name('reset');
            Route::get('css', [BrandingController::class, 'previewCss'])->name('css');
        });

        // Phase 5: Workspaces
        Route::prefix('workspaces')->name('workspaces.')->group(function () {
            Route::get('/', [WorkspaceController::class, 'index'])->name('index');
            Route::post('/', [WorkspaceController::class, 'store'])->name('store');
            Route::put('{workspace}', [WorkspaceController::class, 'update'])->name('update');
            Route::delete('{workspace}', [WorkspaceController::class, 'destroy'])->name('destroy');
            Route::post('{workspace}/switch', [WorkspaceController::class, 'switch'])->name('switch');
            Route::get('{workspace}/members', [WorkspaceController::class, 'members'])->name('members');
            Route::post('{workspace}/invite', [WorkspaceController::class, 'invite'])->name('invite');
            Route::delete('{workspace}/members', [WorkspaceController::class, 'removeMember'])->name('removeMember');
            Route::put('{workspace}/members/role', [WorkspaceController::class, 'updateRole'])->name('updateRole');
            Route::post('{workspace}/leave', [WorkspaceController::class, 'leave'])->name('leave');
        });

        Route::get('workspaces/invite/{token}', [WorkspaceController::class, 'acceptInvite'])->name('workspaces.acceptInvite');

        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/', [ServicesController::class, 'index'])->name('index');
            Route::put('{service}', [ServicesController::class, 'update'])->name('update');

            Route::post('create-mastodon-app', CreateMastodonAppController::class)->name('createMastodonApp');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::put('user', UpdateAuthUserController::class)->name('updateUser');
            Route::put('password', UpdateAuthUserPasswordController::class)->name('updatePassword');
        });

        Route::prefix('system')->name('system.')->group(function () {
            Route::get('status', SystemStatusController::class)->name('status');

            Route::prefix('logs')->name('logs.')->group(function () {
                Route::get('/', [SystemLogsController::class, 'index'])->name('index');
                Route::get('download', [SystemLogsController::class, 'download'])->name('download');
                Route::delete('clear', [SystemLogsController::class, 'clear'])->name('clear');
            });
        });

        Route::get('refresh-csrf-token', function (Request $request) {
            $request->session()->regenerateToken();
            return response(Config::get('app.name'));
        })->name('refreshCsrfToken');

        Route::post('logout', [AuthenticatedController::class, 'destroy'])
            ->name('logout');

        Route::get('callback/{provider}', CallbackSocialProviderController::class)->name('callbackSocialProvider');
    });
