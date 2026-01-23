<?php

use App\Http\Controllers\BorrowRequestController;
use App\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    // Updated to pages/Welcome to fix Vite manifest case-sensitivity error
    return Inertia::render('pages/Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    // Librarian only (CRUD)
    Route::middleware(['can:manage-books'])->group(function () {
        Route::get('/library/create', [LibraryController::class, 'create'])->name('library.create');
        Route::post('/library', [LibraryController::class, 'store'])->name('library.store');
        Route::get('/library/{book}/edit', [LibraryController::class, 'edit'])->name('library.edit');
        Route::put('/library/{book}', [LibraryController::class, 'update'])->name('library.update');
        Route::post('/library/bulk-delete', [LibraryController::class, 'bulkDestroy'])->name('library.bulk-destroy');
        Route::delete('/library/{book}', [LibraryController::class, 'destroy'])->name('library.destroy');

        // User Management
        Route::post('/users/import', [\App\Http\Controllers\UserController::class, 'import'])->name('users.import');
        Route::post('/users/bulk-delete', [\App\Http\Controllers\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', \App\Http\Controllers\UserController::class);

        // Library Import
        Route::post('/library/import', [LibraryController::class, 'import'])->name('library.import');
    });

    // Library (all users)
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/{book}', [LibraryController::class, 'show'])->name('library.show');

    // Borrow Request
    Route::post('/library/{book}/borrow', [BorrowRequestController::class, 'store'])->name('library.borrow');
    Route::post('/borrow-requests/{borrowRequest}/approve', [BorrowRequestController::class, 'approve'])->name('borrow-requests.approve')->middleware('can:manage-borrows');
    Route::post('/borrow-requests/{borrowRequest}/reject', [BorrowRequestController::class, 'reject'])->name('borrow-requests.reject')->middleware('can:manage-borrows');
    Route::post('/library/{book}/return', [BorrowRequestController::class, 'return'])->name('library.return');
    Route::delete('/borrow-requests/{borrowRequest}/cancel', [BorrowRequestController::class, 'cancel'])->name('borrow-requests.cancel');

    // My Requests
    Route::get('/my-requests', [BorrowRequestController::class, 'myRequests'])->name('my-requests');

    // My Books
    Route::get('/my-books', [BorrowRequestController::class, 'myBooks'])->name('my-books');

    // Check Borrow Status
    Route::get('/library/{book}/status', [BorrowRequestController::class, 'checkBorrowStatus'])->name('library.check-status');

    // Reject with Reason
    Route::post('/borrow-requests/{borrowRequest}/reject-with-reason', [BorrowRequestController::class, 'rejectWithReason'])
        ->middleware('can:manage-borrows')
        ->name('borrow-requests.reject-reason');

    // Manage Requests (Librarian)
    Route::get('/manage-requests', [BorrowRequestController::class, 'manageRequests'])
        ->middleware('can:manage-borrows')
        ->name('manage-requests');

    // Active Borrows (Librarian)
    Route::get('/admin/active-borrows', [BorrowRequestController::class, 'activeBorrows'])
        ->middleware('can:manage-borrows')
        ->name('active-borrows');

    // Learning Files
    Route::get('/learning-files', [App\Http\Controllers\LearningFileController::class, 'index'])->name('learning-files.index');
    Route::get('/learning-files/{learningFile}/download', [App\Http\Controllers\LearningFileController::class, 'download'])->name('learning-files.download');

    Route::middleware(['can:upload-files'])->group(function () {
        Route::get('/learning-files/create', [App\Http\Controllers\LearningFileController::class, 'create'])->name('learning-files.create');
        Route::post('/learning-files', [App\Http\Controllers\LearningFileController::class, 'store'])->name('learning-files.store');
        Route::put('/learning-files/{learningFile}', [App\Http\Controllers\LearningFileController::class, 'update'])->name('learning-files.update');
        Route::delete('/learning-files/{learningFile}', [App\Http\Controllers\LearningFileController::class, 'destroy'])->name('learning-files.destroy');
    });

});

// CSV Template Download
Route::get('/downloads/template/{type}', function ($type) {
    $path = match ($type) {
        'books' => public_path('templates/books_template.csv'),
        'users' => public_path('templates/users_template.csv'),
        default => abort(404),
    };

    return response()->download($path);
})->name('downloads.template');

require __DIR__.'/settings.php';
