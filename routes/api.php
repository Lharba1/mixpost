<?php

use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Controllers\Api\PostApiController;
use Inovector\Mixpost\Http\Middleware\ApiAuthenticate;
use Inovector\Mixpost\Models\ApiToken;

/*
|--------------------------------------------------------------------------
| Mixpost API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the Mixpost service provider and are
| protected by API token authentication.
|
*/

Route::prefix('api/v1')
    ->name('mixpost.api.')
    ->middleware([ApiAuthenticate::class])
    ->group(function () {
        
        // Posts
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostApiController::class, 'index'])
                ->middleware('ability:' . ApiToken::ABILITY_READ_POSTS)
                ->name('index');
            
            Route::post('/', [PostApiController::class, 'store'])
                ->middleware('ability:' . ApiToken::ABILITY_WRITE_POSTS)
                ->name('store');
            
            Route::get('{post}', [PostApiController::class, 'show'])
                ->middleware('ability:' . ApiToken::ABILITY_READ_POSTS)
                ->name('show');
            
            Route::put('{post}', [PostApiController::class, 'update'])
                ->middleware('ability:' . ApiToken::ABILITY_WRITE_POSTS)
                ->name('update');
            
            Route::delete('{post}', [PostApiController::class, 'destroy'])
                ->middleware('ability:' . ApiToken::ABILITY_WRITE_POSTS)
                ->name('destroy');
            
            Route::post('{post}/schedule', [PostApiController::class, 'schedule'])
                ->middleware('ability:' . ApiToken::ABILITY_WRITE_POSTS)
                ->name('schedule');
        });
        
        // Accounts
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', function () {
                return response()->json([
                    'accounts' => \Inovector\Mixpost\Models\Account::all(['id', 'name', 'provider', 'username']),
                ]);
            })->middleware('ability:' . ApiToken::ABILITY_READ_ACCOUNTS)->name('index');
        });
        
        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('stats', function () {
                $service = new \Inovector\Mixpost\Services\AnalyticsService();
                return response()->json([
                    'overview' => $service->getOverviewStats(),
                    'posts_by_status' => $service->getPostsByStatus(),
                ]);
            })->middleware('ability:' . ApiToken::ABILITY_READ_ANALYTICS)->name('stats');
        });
        
        // User info
        Route::get('me', function () {
            $token = request()->attributes->get('api_token');
            return response()->json([
                'token_name' => $token->name,
                'abilities' => $token->abilities,
                'user' => $token->user ? [
                    'id' => $token->user->id,
                    'name' => $token->user->name,
                ] : null,
            ]);
        })->name('me');
    });
