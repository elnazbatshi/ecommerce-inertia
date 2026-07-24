<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPurchaseLedgerFilterRequest;
use App\Services\CustomerPurchaseLedgerService;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPurchaseLedgerController extends Controller
{
    public function __construct(private readonly CustomerPurchaseLedgerService $ledger)
    {
    }

    public function index(CustomerPurchaseLedgerFilterRequest $request): Response
    {
        $filters = $request->filters();
        $activeView = $filters['active_view'];
        $hasSelectedCustomer = (bool) $filters['customer_id'];

        return Inertia::render('CustomerPurchaseLedger/Index', [
            'orderRows' => $hasSelectedCustomer && $activeView === 'orders' ? $this->ledger->orders($filters) : null,
            'purchasedProductRows' => $hasSelectedCustomer && $activeView === 'products' ? $this->ledger->purchasedProducts($filters) : null,
            'paymentRows' => $hasSelectedCustomer && $activeView === 'payments' ? $this->ledger->payments($filters) : null,
            'analyticsData' => $hasSelectedCustomer && $activeView === 'analytics' ? $this->ledger->analytics($filters) : null,
            'timelineRows' => $hasSelectedCustomer && $activeView === 'timeline' ? $this->ledger->timeline($filters) : null,
            'frequentlyPurchasedProducts' => $hasSelectedCustomer && $activeView === 'analytics' ? $this->ledger->frequentlyPurchasedProducts($filters) : [],
            'reminderProducts' => $hasSelectedCustomer && $activeView === 'analytics' ? $this->ledger->repurchaseCandidates($filters) : [],
            'nextPurchaseSuggestions' => $hasSelectedCustomer && $activeView === 'analytics' ? $this->ledger->nextPurchaseSuggestions($filters) : [],
            'additionalInsights' => $hasSelectedCustomer && $activeView === 'analytics' ? $this->ledger->additionalInsights($filters) : [],
            'statistics' => $hasSelectedCustomer ? $this->ledger->statistics($filters) : $this->ledger->statistics(['customer_id' => null, ...$filters]),
            'lifetimeValue' => $hasSelectedCustomer ? $this->ledger->lifetimeValue((int) $filters['customer_id']) : 0,
            'lastPurchase' => $hasSelectedCustomer ? $this->ledger->lastPurchase((int) $filters['customer_id']) : null,
            'purchaseFrequency' => $hasSelectedCustomer ? $this->ledger->purchaseFrequency((int) $filters['customer_id']) : $this->ledger->emptyPurchaseFrequency(),
            'customerSegment' => $hasSelectedCustomer ? $this->ledger->customerSegment((int) $filters['customer_id']) : null,
            'filters' => $filters,
            'activeView' => $activeView,
            'selectedCustomer' => $this->ledger->selectedCustomer($filters['customer_id']),
            'customerSearchResults' => $this->ledger->searchCustomers($filters['customer_search'], $filters['customer_id']),
            'hasSelectedCustomer' => $hasSelectedCustomer,
            'orderStatusOptions' => $this->ledger->orderStatusOptions(),
            'paymentMethodOptions' => $this->ledger->paymentMethodOptions(),
            'perPageOptions' => CustomerPurchaseLedgerFilterRequest::PER_PAGE,
        ]);
    }
}
