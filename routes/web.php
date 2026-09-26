<?php

use App\Http\Controllers\Admin\CustomizationDevelopmentController;
use App\Http\Controllers\Admin\CustomizationQuoteController as AdminCustomizationQuoteController;
use App\Http\Controllers\Admin\CustomizationRequestController as AdminCustomizationRequestController;
use App\Http\Controllers\Admin\CustomizationRequestInformationController as AdminCustomizationRequestInformationController;
use App\Http\Controllers\Admin\CustomizationRequestStatusController as AdminCustomizationRequestStatusController;
use App\Http\Controllers\Admin\CustomizationSecureAccessCloseController;
use App\Http\Controllers\Admin\CustomizationSecureAccessController as AdminCustomizationSecureAccessController;
use App\Http\Controllers\Admin\CustomizationSecureAccessHandoffController;
use App\Http\Controllers\Admin\CustomizationSecureAccessRevealController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TicketReplyController as AdminTicketReplyController;
use App\Http\Controllers\Admin\TicketSecureAccessCloseController as AdminTicketSecureAccessCloseController;
use App\Http\Controllers\Admin\TicketSecureAccessController as AdminTicketSecureAccessController;
use App\Http\Controllers\Admin\TicketSecureAccessHandoffController as AdminTicketSecureAccessHandoffController;
use App\Http\Controllers\Admin\TicketSecureAccessRevealController as AdminTicketSecureAccessRevealController;
use App\Http\Controllers\Admin\TicketStatusController as AdminTicketStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\CustomizationCancellationController;
use App\Http\Controllers\CustomizationConversationController;
use App\Http\Controllers\CustomizationQuoteDecisionController;
use App\Http\Controllers\CustomizationRequestController;
use App\Http\Controllers\CustomizationRequestReplyController;
use App\Http\Controllers\CustomizationReviewController;
use App\Http\Controllers\CustomizationSecureAccessRevealController as CustomerCustomizationSecureAccessRevealController;
use App\Http\Controllers\CustomizationSecureAccessSubmissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketReplyController;
use App\Http\Controllers\TicketSecureAccessRevealController;
use App\Http\Controllers\TicketSecureAccessSubmissionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

/*
|--------------------------------------------------------------------------
| Public Products
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/{product}', [ProductController::class, 'show'])
    ->name('products.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Customer Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', CustomerDashboardController::class)
        ->middleware('role:customer')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Customer Orders
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:customer')->group(function () {
        Route::get(
            '/orders',
            [CustomerOrderController::class, 'index']
        )
            ->name('customer.orders.index');

        Route::post(
            '/products/{product}/orders',
            [CustomerOrderController::class, 'store']
        )
            ->name('customer.orders.store');

        Route::get(
            '/orders/{order}',
            [CustomerOrderController::class, 'show']
        )
            ->name('customer.orders.show');

        Route::get(
            '/orders/{order}/payment',
            [CustomerPaymentController::class, 'showOrder']
        )
            ->name('customer.orders.payment.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Customer Support Tickets
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:customer')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])
            ->name('tickets.index');

        Route::get('/tickets/create', [TicketController::class, 'create'])
            ->name('tickets.create');

        Route::post('/tickets', [TicketController::class, 'store'])
            ->name('tickets.store');

        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->name('tickets.show');

        Route::post(
            '/tickets/{ticket}/replies',
            [TicketReplyController::class, 'store']
        )
            ->name('tickets.replies.store');

        Route::post(
            '/tickets/{ticket}/secure-access/{secureAccess}/submit',
            [TicketSecureAccessSubmissionController::class, 'store']
        )
            ->scopeBindings()
            ->name('tickets.secure-access.submit');

        Route::get(
            '/tickets/{ticket}/secure-access/{secureAccess}/reveal',
            [TicketSecureAccessRevealController::class, 'show']
        )
            ->scopeBindings()
            ->name('tickets.secure-access.reveal');
    });

    /*
    |--------------------------------------------------------------------------
    | Customer Customization Requests
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:customer')->group(function () {
        Route::get(
            '/customizations',
            [CustomizationRequestController::class, 'index']
        )
            ->name('customizations.index');

        Route::get(
            '/customizations/create',
            [CustomizationRequestController::class, 'create']
        )
            ->name('customizations.create');

        Route::post(
            '/customizations',
            [CustomizationRequestController::class, 'store']
        )
            ->name('customizations.store');

        Route::get(
            '/customizations/{customizationRequest}',
            [CustomizationRequestController::class, 'show']
        )
            ->name('customizations.show');

        Route::patch(
            '/customizations/{customizationRequest}/cancel',
            [CustomizationCancellationController::class, 'cancel']
        )
            ->name('customizations.cancel');

        Route::post(
            '/customizations/{customizationRequest}/replies',
            [CustomizationRequestReplyController::class, 'store']
        )
            ->name('customizations.replies.store');

        Route::post(
            '/customizations/{customizationRequest}/messages',
            [CustomizationConversationController::class, 'store']
        )
            ->name('customizations.messages.store');

        Route::post(
            '/customizations/{customizationRequest}/secure-access/{secureAccess}/submit',
            [CustomizationSecureAccessSubmissionController::class, 'store']
        )
            ->scopeBindings()
            ->name('customizations.secure-access.submit');

        Route::get(
            '/customizations/{customizationRequest}/secure-access/{secureAccess}/reveal',
            [CustomerCustomizationSecureAccessRevealController::class, 'show']
        )
            ->scopeBindings()
            ->name('customizations.secure-access.reveal');

        Route::patch(
            '/customizations/{customizationRequest}/quote/accept',
            [CustomizationQuoteDecisionController::class, 'accept']
        )
            ->name('customizations.quote.accept');

        Route::patch(
            '/customizations/{customizationRequest}/quote/decline',
            [CustomizationQuoteDecisionController::class, 'decline']
        )
            ->name('customizations.quote.decline');

        Route::post(
            '/customizations/{customizationRequest}/request-revision',
            [CustomizationReviewController::class, 'requestRevision']
        )
            ->name('customizations.request-revision');

        Route::patch(
            '/customizations/{customizationRequest}/approve',
            [CustomizationReviewController::class, 'approve']
        )
            ->name('customizations.approve');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Admin Support Tickets
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/tickets',
                [AdminTicketController::class, 'index']
            )
                ->name('tickets.index');

            Route::get(
                '/tickets/{ticket}',
                [AdminTicketController::class, 'show']
            )
                ->name('tickets.show');

            Route::post(
                '/tickets/{ticket}/replies',
                [AdminTicketReplyController::class, 'store']
            )
                ->name('tickets.replies.store');

            Route::patch(
                '/tickets/{ticket}/resolve',
                [AdminTicketStatusController::class, 'resolve']
            )
                ->name('tickets.resolve');

            Route::patch(
                '/tickets/{ticket}/close',
                [AdminTicketStatusController::class, 'close']
            )
                ->name('tickets.close');

            Route::post(
                '/tickets/{ticket}/secure-access',
                [AdminTicketSecureAccessController::class, 'store']
            )
                ->name('tickets.secure-access.store');

            Route::post(
                '/tickets/{ticket}/secure-access/handoff',
                [AdminTicketSecureAccessHandoffController::class, 'store']
            )
                ->name('tickets.secure-access.handoff');

            Route::get(
                '/tickets/{ticket}/secure-access/{secureAccess}/reveal',
                [AdminTicketSecureAccessRevealController::class, 'show']
            )
                ->scopeBindings()
                ->name('tickets.secure-access.reveal');

            Route::patch(
                '/tickets/{ticket}/secure-access/{secureAccess}/close',
                [AdminTicketSecureAccessCloseController::class, 'close']
            )
                ->scopeBindings()
                ->name('tickets.secure-access.close');

            /*
            |--------------------------------------------------------------------------
            | Admin Customization Requests
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/customizations',
                [AdminCustomizationRequestController::class, 'index']
            )
                ->name('customizations.index');

            Route::get(
                '/customizations/{customizationRequest}',
                [AdminCustomizationRequestController::class, 'show']
            )
                ->name('customizations.show');

            Route::patch(
                '/customizations/{customizationRequest}/start-review',
                [AdminCustomizationRequestStatusController::class, 'startReview']
            )
                ->name('customizations.start-review');

            Route::post(
                '/customizations/{customizationRequest}/decline',
                [AdminCustomizationRequestStatusController::class, 'decline']
            )
                ->name('customizations.decline');

            Route::post(
                '/customizations/{customizationRequest}/request-information',
                [AdminCustomizationRequestInformationController::class, 'store']
            )
                ->name('customizations.request-information');

            Route::post(
                '/customizations/{customizationRequest}/messages',
                [CustomizationConversationController::class, 'store']
            )
                ->name('customizations.messages.store');

            Route::post(
                '/customizations/{customizationRequest}/quote',
                [AdminCustomizationQuoteController::class, 'store']
            )
                ->name('customizations.quote.store');

            /*
            |--------------------------------------------------------------------------
            | Secure Access
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/customizations/{customizationRequest}/secure-access',
                [AdminCustomizationSecureAccessController::class, 'store']
            )
                ->name('customizations.secure-access.store');

            Route::post(
                '/customizations/{customizationRequest}/secure-access/handoff',
                [CustomizationSecureAccessHandoffController::class, 'store']
            )
                ->name('customizations.secure-access.handoff');

            Route::get(
                '/customizations/{customizationRequest}/secure-access/{secureAccess}/reveal',
                [CustomizationSecureAccessRevealController::class, 'show']
            )
                ->scopeBindings()
                ->name('customizations.secure-access.reveal');

            Route::patch(
                '/customizations/{customizationRequest}/secure-access/{secureAccess}/close',
                [CustomizationSecureAccessCloseController::class, 'close']
            )
                ->scopeBindings()
                ->name('customizations.secure-access.close');

            Route::patch(
                '/customizations/{customizationRequest}/start-development',
                [CustomizationDevelopmentController::class, 'start']
            )
                ->name('customizations.start-development');

            Route::patch(
                '/customizations/{customizationRequest}/ready-for-review',
                [CustomizationDevelopmentController::class, 'readyForReview']
            )
                ->name('customizations.ready-for-review');

            Route::patch(
                '/customizations/{customizationRequest}/resume-development',
                [CustomizationDevelopmentController::class, 'resume']
            )
                ->name('customizations.resume-development');

            /*
            |--------------------------------------------------------------------------
            | Admin Orders
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/orders',
                [AdminOrderController::class, 'index']
            )
                ->name('orders.index');

            Route::get(
                '/orders/{order}',
                [AdminOrderController::class, 'show']
            )
                ->name('orders.show');

            Route::patch(
                '/orders/{order}/status',
                [AdminOrderController::class, 'updateStatus']
            )
                ->name('orders.status.update');

            /*
            |--------------------------------------------------------------------------
            | Admin Products
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'products',
                AdminProductController::class
            )
                ->except(['destroy']);
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Permission Test Route
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/projects/create-check', function () {
        return response()->json([
            'allowed' => true,
        ]);
    })
        ->middleware(['role:admin', 'permission:create projects'])
        ->name('admin.projects.create-check');

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AuthenticatedSessionController::class, 'destroy']
    )
        ->name('logout');
});