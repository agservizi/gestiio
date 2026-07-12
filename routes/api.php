<?php



use App\Http\Controllers\Api\Admin\LuggageDepositActionController;

use App\Http\Controllers\Api\Admin\LuggageDepositController as AdminLuggageDepositController;

use App\Http\Controllers\Api\Admin\LuggageExportController;

use App\Http\Controllers\Api\Admin\LuggageSettingsController;

use App\Http\Controllers\Api\Admin\LuggageStatsController;

use App\Http\Controllers\Api\Public\LuggageAvailabilityController;

use App\Http\Controllers\Api\Public\LuggageAvailabilityRangeController;

use App\Http\Controllers\Api\Public\LuggageBookController;

use App\Http\Controllers\Api\Public\LuggageDepositController;

use App\Http\Controllers\Api\Public\LuggageHealthController;

use App\Http\Controllers\Api\Public\LuggageOpenApiController;

use App\Http\Controllers\Api\Public\LuggagePricingController;

use App\Http\Controllers\Api\Public\LuggageVerifyController;

use Illuminate\Support\Facades\Route;



Route::prefix('public/deposito-bagagli')

    ->middleware('throttle:60,1')

    ->group(function () {

        Route::get('docs', [LuggageOpenApiController::class, 'show']);

        Route::get('health', [LuggageHealthController::class, 'show']);

        Route::get('verify', [LuggageVerifyController::class, 'show'])->name('luggage.public.verify');



        Route::middleware('luggage.api')->group(function () {

            Route::post('book', [LuggageBookController::class, 'store']);

            Route::get('deposits', [LuggageDepositController::class, 'index']);

            Route::get('deposits/{code}', [LuggageDepositController::class, 'show'])->name('luggage.public.deposits.show');

            Route::patch('deposits/{code}', [LuggageDepositController::class, 'update']);

            Route::post('deposits/{code}/cancel', [LuggageDepositController::class, 'cancel']);

            Route::get('availability', [LuggageAvailabilityController::class, 'show']);

            Route::get('availability/range', [LuggageAvailabilityRangeController::class, 'show']);

            Route::get('pricing', [LuggagePricingController::class, 'show']);

        });

    });



Route::prefix('admin/deposito-bagagli')

    ->middleware(['web', 'auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa'])

    ->group(function () {

        Route::get('docs', [LuggageOpenApiController::class, 'admin']);

        Route::get('stats/overview', [LuggageStatsController::class, 'index']);

        Route::get('export/csv', [LuggageExportController::class, 'csv']);

        Route::get('settings', [LuggageSettingsController::class, 'show']);

        Route::post('settings', [LuggageSettingsController::class, 'update']);



        Route::get('/', [AdminLuggageDepositController::class, 'index']);

        Route::post('/', [AdminLuggageDepositController::class, 'store']);

        Route::get('{deposit}', [AdminLuggageDepositController::class, 'show']);

        Route::patch('{deposit}', [AdminLuggageDepositController::class, 'update']);

        Route::delete('{deposit}', [AdminLuggageDepositController::class, 'destroy']);

        Route::post('{deposit}/actions', [LuggageDepositActionController::class, 'handle']);

        Route::get('{deposit}/pdf', [LuggageExportController::class, 'receipt']);

        Route::get('{deposit}/pdf/tags', [LuggageExportController::class, 'tags']);
        Route::get('{deposit}/pdf/agreement', [LuggageExportController::class, 'agreement']);

    });

