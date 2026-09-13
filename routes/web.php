<?php

use App\Http\Controllers\Admin\AdminDailyGameController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFeedbackController;
use App\Http\Controllers\Admin\AdminMarketingController;
use App\Http\Controllers\Admin\AdminStationController;
use App\Http\Controllers\Admin\AdminStatisticsController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HowItWorksController;
use App\Http\Controllers\PlayerStatisticsController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StationMapController;
use App\Http\Controllers\StatisticsController;
use App\Livewire\PlayGame;
use Illuminate\Support\Facades\Route;

Route::get('/', PlayGame::class)->name('home');
Route::get('/statistieken', StatisticsController::class)->name('statistics');
Route::get('/statistieken/stations.json', StationMapController::class)->name('stations.map');
Route::get('/mijn-statistieken', PlayerStatisticsController::class)->name('my-statistics');
Route::get('/hoe-werkt-het', HowItWorksController::class)->name('how-it-works');
Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:5,10')->name('feedback.store');
Route::get('/station/{station}', StationController::class)->name('station.show');

Route::prefix('admin')->name('admin.')->middleware(['auth.basic', 'admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/dagen', [AdminDailyGameController::class, 'index'])->name('games.index');
    Route::post('/dagen', [AdminDailyGameController::class, 'store'])->name('games.store');
    Route::get('/dagen/{dailyGame}', [AdminDailyGameController::class, 'show'])->name('games.show');
    Route::post('/dagen/{dailyGame}/regenereer', [AdminDailyGameController::class, 'regenerate'])->name('games.regenerate');

    Route::get('/stations', [AdminStationController::class, 'index'])->name('stations.index');
    Route::get('/stations/{station}', [AdminStationController::class, 'edit'])->name('stations.edit');
    Route::put('/stations/{station}', [AdminStationController::class, 'update'])->name('stations.update');
    Route::post('/stations/{station}/toggle', [AdminStationController::class, 'toggle'])->name('stations.toggle');

    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback/{feedback}/gelezen', [AdminFeedbackController::class, 'markRead'])->name('feedback.read');

    Route::get('/marketing', AdminMarketingController::class)->name('marketing');

    Route::get('/statistieken', [AdminStatisticsController::class, 'index'])->name('statistics');
    Route::post('/statistieken/herbereken', [AdminStatisticsController::class, 'recalculate'])->name('statistics.recalculate');
});
