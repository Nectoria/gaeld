<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1 and require authentication
| via Laravel Sanctum bearer tokens. Tokens are scoped to a specific
| company context for multi-tenancy.
|
| Documentation: /docs/api-specification.md
|
*/

/*
|--------------------------------------------------------------------------
| API Token Management
|--------------------------------------------------------------------------
| Endpoints for creating and managing API tokens. These routes require
| web session authentication, not API token authentication.
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication & User
    |--------------------------------------------------------------------------
    */

    // Current authenticated user
    Route::get('/me', [App\Http\Controllers\Api\V1\UserController::class, 'show'])
        ->name('api.v1.me.show');

    Route::patch('/me', [App\Http\Controllers\Api\V1\UserController::class, 'update'])
        ->name('api.v1.me.update');

    // API Token Management (requires web auth, not API token)
    Route::prefix('tokens')->name('api.v1.tokens.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\TokenController::class, 'index'])
            ->name('index');

        Route::post('/', [App\Http\Controllers\Api\V1\TokenController::class, 'store'])
            ->name('store');

        Route::delete('/{token}', [App\Http\Controllers\Api\V1\TokenController::class, 'destroy'])
            ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    Route::prefix('companies')->name('api.v1.companies.')->group(function () {
        // List accessible companies
        Route::get('/', [App\Http\Controllers\Api\V1\CompanyController::class, 'index'])
            ->name('index');

        // Company details
        Route::get('/{company}', [App\Http\Controllers\Api\V1\CompanyController::class, 'show'])
            ->name('show');

        // Update company (requires owner/admin)
        Route::patch('/{company}', [App\Http\Controllers\Api\V1\CompanyController::class, 'update'])
            ->name('update');

        // Delete company (requires owner)
        Route::delete('/{company}', [App\Http\Controllers\Api\V1\CompanyController::class, 'destroy'])
            ->name('destroy');

        /*
        |--------------------------------------------------------------------------
        | Company-Scoped Resources
        |--------------------------------------------------------------------------
        | All resources below are scoped to a specific company context
        */

        Route::prefix('{company}')->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Contacts (Customers & Vendors)
            |--------------------------------------------------------------------------
            */

            Route::prefix('contacts')->name('contacts.')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\ContactController::class, 'index'])
                    ->name('index');

                Route::post('/', [App\Http\Controllers\Api\V1\ContactController::class, 'store'])
                    ->name('store');

                Route::get('/{contact}', [App\Http\Controllers\Api\V1\ContactController::class, 'show'])
                    ->name('show');

                Route::patch('/{contact}', [App\Http\Controllers\Api\V1\ContactController::class, 'update'])
                    ->name('update');

                Route::delete('/{contact}', [App\Http\Controllers\Api\V1\ContactController::class, 'destroy'])
                    ->name('destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Invoices
            |--------------------------------------------------------------------------
            */

            Route::prefix('invoices')->name('invoices.')->group(function () {
                // Invoice CRUD
                Route::get('/', [App\Http\Controllers\Api\V1\InvoiceController::class, 'index'])
                    ->name('index');

                Route::post('/', [App\Http\Controllers\Api\V1\InvoiceController::class, 'store'])
                    ->name('store');

                Route::get('/{invoice}', [App\Http\Controllers\Api\V1\InvoiceController::class, 'show'])
                    ->name('show');

                Route::patch('/{invoice}', [App\Http\Controllers\Api\V1\InvoiceController::class, 'update'])
                    ->name('update');

                Route::delete('/{invoice}', [App\Http\Controllers\Api\V1\InvoiceController::class, 'destroy'])
                    ->name('destroy');

                // Invoice Actions
                Route::post('/{invoice}/send', [App\Http\Controllers\Api\V1\InvoiceActionController::class, 'send'])
                    ->name('send');

                Route::post('/{invoice}/mark-viewed', [App\Http\Controllers\Api\V1\InvoiceActionController::class, 'markViewed'])
                    ->name('mark-viewed');

                Route::post('/{invoice}/payments', [App\Http\Controllers\Api\V1\InvoicePaymentController::class, 'store'])
                    ->name('payments.store');

                Route::post('/{invoice}/cancel', [App\Http\Controllers\Api\V1\InvoiceActionController::class, 'cancel'])
                    ->name('cancel');

                // QR Code Generation
                Route::post('/{invoice}/generate-qr', [App\Http\Controllers\Api\V1\InvoiceActionController::class, 'generateQr'])
                    ->name('generate-qr');

                // File Downloads
                Route::get('/{invoice}/pdf', [App\Http\Controllers\Api\V1\InvoiceFileController::class, 'downloadPdf'])
                    ->name('pdf');

                Route::get('/{invoice}/qr-code', [App\Http\Controllers\Api\V1\InvoiceFileController::class, 'downloadQrCode'])
                    ->name('qr-code');

                // Invoice Items (Nested Resource)
                Route::prefix('{invoice}/items')->name('items.')->group(function () {
                    Route::post('/', [App\Http\Controllers\Api\V1\InvoiceItemController::class, 'store'])
                        ->name('store');

                    Route::patch('/{item}', [App\Http\Controllers\Api\V1\InvoiceItemController::class, 'update'])
                        ->name('update');

                    Route::delete('/{item}', [App\Http\Controllers\Api\V1\InvoiceItemController::class, 'destroy'])
                        ->name('destroy');
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Company Users & Teams
            |--------------------------------------------------------------------------
            */

            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\CompanyUserController::class, 'index'])
                    ->name('index');

                Route::patch('/{user}', [App\Http\Controllers\Api\V1\CompanyUserController::class, 'update'])
                    ->name('update');

                Route::delete('/{user}', [App\Http\Controllers\Api\V1\CompanyUserController::class, 'destroy'])
                    ->name('destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Company Invitations
            |--------------------------------------------------------------------------
            */

            Route::prefix('invitations')->name('invitations.')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\V1\InvitationController::class, 'store'])
                    ->name('store');

                Route::delete('/{invitation}', [App\Http\Controllers\Api\V1\InvitationController::class, 'destroy'])
                    ->name('destroy');
            });

        }); // End company-scoped routes

    }); // End companies prefix

}); // End v1 prefix

/*
|--------------------------------------------------------------------------
| Public Invitation Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/invitations')->name('api.v1.invitations.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\Api\V1\InvitationController::class, 'show'])
        ->name('show');

    // Accept invitation requires web auth (creates/links user)
    Route::post('/{token}/accept', [App\Http\Controllers\Api\V1\InvitationController::class, 'accept'])
        ->middleware(['auth:sanctum'])
        ->name('accept');
});

/*
|--------------------------------------------------------------------------
| Future Feature Routes (Placeholder)
|--------------------------------------------------------------------------
| These routes are defined but not yet implemented. They serve as a
| roadmap for future API development.
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    Route::prefix('companies/{company}')->group(function () {

        // Expenses
        // Route::apiResource('expenses', App\Http\Controllers\Api\V1\ExpenseController::class);
        // Route::post('expenses/{expense}/attach-receipt', [App\Http\Controllers\Api\V1\ExpenseController::class, 'attachReceipt']);
        // Route::get('expenses/{expense}/receipt', [App\Http\Controllers\Api\V1\ExpenseController::class, 'downloadReceipt']);

        // Products & Services
        // Route::apiResource('products', App\Http\Controllers\Api\V1\ProductController::class);

        // Recurring Invoices
        // Route::apiResource('recurring-invoices', App\Http\Controllers\Api\V1\RecurringInvoiceController::class);
        // Route::post('recurring-invoices/{recurringInvoice}/pause', [App\Http\Controllers\Api\V1\RecurringInvoiceController::class, 'pause']);
        // Route::post('recurring-invoices/{recurringInvoice}/resume', [App\Http\Controllers\Api\V1\RecurringInvoiceController::class, 'resume']);

        // Quotes/Estimates
        // Route::apiResource('quotes', App\Http\Controllers\Api\V1\QuoteController::class);
        // Route::post('quotes/{quote}/send', [App\Http\Controllers\Api\V1\QuoteController::class, 'send']);
        // Route::post('quotes/{quote}/accept', [App\Http\Controllers\Api\V1\QuoteController::class, 'accept']);
        // Route::post('quotes/{quote}/convert-to-invoice', [App\Http\Controllers\Api\V1\QuoteController::class, 'convertToInvoice']);

        // Credit Notes
        // Route::apiResource('credit-notes', App\Http\Controllers\Api\V1\CreditNoteController::class);
        // Route::post('credit-notes/{creditNote}/apply-to-invoice', [App\Http\Controllers\Api\V1\CreditNoteController::class, 'applyToInvoice']);

        // Payment Reminders
        // Route::get('invoices/{invoice}/reminders', [App\Http\Controllers\Api\V1\PaymentReminderController::class, 'index']);
        // Route::post('invoices/{invoice}/reminders', [App\Http\Controllers\Api\V1\PaymentReminderController::class, 'store']);
        // Route::get('payment-reminders', [App\Http\Controllers\Api\V1\PaymentReminderController::class, 'list']);

        // Reports
        // Route::prefix('reports')->name('reports.')->group(function () {
        //     Route::get('sales-summary', [App\Http\Controllers\Api\V1\ReportController::class, 'salesSummary']);
        //     Route::get('aging-receivables', [App\Http\Controllers\Api\V1\ReportController::class, 'agingReceivables']);
        //     Route::get('tax-summary', [App\Http\Controllers\Api\V1\ReportController::class, 'taxSummary']);
        //     Route::get('profit-loss', [App\Http\Controllers\Api\V1\ReportController::class, 'profitLoss']);
        //     Route::get('balance-sheet', [App\Http\Controllers\Api\V1\ReportController::class, 'balanceSheet']);
        //     Route::get('cash-flow', [App\Http\Controllers\Api\V1\ReportController::class, 'cashFlow']);
        // });

        // Bank Accounts & Transactions
        // Route::apiResource('bank-accounts', App\Http\Controllers\Api\V1\BankAccountController::class);
        // Route::get('bank-accounts/{bankAccount}/transactions', [App\Http\Controllers\Api\V1\BankAccountController::class, 'transactions']);
        // Route::post('bank-accounts/{bankAccount}/sync', [App\Http\Controllers\Api\V1\BankAccountController::class, 'sync']);
        // Route::post('bank-transactions/{transaction}/match-invoice', [App\Http\Controllers\Api\V1\BankTransactionController::class, 'matchInvoice']);

        // Tax Management
        // Route::apiResource('tax-rates', App\Http\Controllers\Api\V1\TaxRateController::class);
        // Route::apiResource('tax-returns', App\Http\Controllers\Api\V1\TaxReturnController::class);
        // Route::get('tax-returns/{taxReturn}/generate', [App\Http\Controllers\Api\V1\TaxReturnController::class, 'generate']);

        // Document Management
        // Route::apiResource('documents', App\Http\Controllers\Api\V1\DocumentController::class);
        // Route::get('documents/{document}/download', [App\Http\Controllers\Api\V1\DocumentController::class, 'download']);

        // Webhooks
        // Route::apiResource('webhooks', App\Http\Controllers\Api\V1\WebhookController::class);
        // Route::post('webhooks/{webhook}/test', [App\Http\Controllers\Api\V1\WebhookController::class, 'test']);

        // Time Tracking
        // Route::apiResource('time-entries', App\Http\Controllers\Api\V1\TimeEntryController::class);
        // Route::post('time-entries/{timeEntry}/start', [App\Http\Controllers\Api\V1\TimeEntryController::class, 'start']);
        // Route::post('time-entries/{timeEntry}/stop', [App\Http\Controllers\Api\V1\TimeEntryController::class, 'stop']);
        // Route::post('time-entries/bulk-invoice', [App\Http\Controllers\Api\V1\TimeEntryController::class, 'bulkInvoice']);

        // Projects
        // Route::apiResource('projects', App\Http\Controllers\Api\V1\ProjectController::class);
        // Route::get('projects/{project}/invoices', [App\Http\Controllers\Api\V1\ProjectController::class, 'invoices']);
        // Route::get('projects/{project}/time-entries', [App\Http\Controllers\Api\V1\ProjectController::class, 'timeEntries']);

        // Activity Log
        // Route::get('activity-log', [App\Http\Controllers\Api\V1\ActivityLogController::class, 'index']);
        // Route::get('invoices/{invoice}/activity', [App\Http\Controllers\Api\V1\ActivityLogController::class, 'invoiceActivity']);
        // Route::get('contacts/{contact}/activity', [App\Http\Controllers\Api\V1\ActivityLogController::class, 'contactActivity']);

    });

    // Exchange Rates (Global)
    // Route::get('exchange-rates', [App\Http\Controllers\Api\V1\ExchangeRateController::class, 'index']);
    // Route::get('exchange-rates/{from}/{to}', [App\Http\Controllers\Api\V1\ExchangeRateController::class, 'convert']);

});
