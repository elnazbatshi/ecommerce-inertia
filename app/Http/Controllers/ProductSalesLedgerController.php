<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSalesLedgerFilterRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ProductSalesLedgerService;
use Inertia\Inertia;
use Inertia\Response;

class ProductSalesLedgerController extends Controller
{
    public function __construct(private readonly ProductSalesLedgerService $ledger)
    {
    }

    public function index(ProductSalesLedgerFilterRequest $request): Response
    {
        $filters = $request->filters();
        $activeView = $filters['active_view'];

        return Inertia::render('ProductSalesLedger/Index', [
            'transactionRows' => $activeView === 'transactions' ? $this->ledger->transactions($filters) : null,
            'productSummaryRows' => $activeView === 'products' ? $this->ledger->productSummaries($filters) : null,
            'chartData' => $activeView === 'chart' ? $this->ledger->chartData($filters) : null,
            'statistics' => $this->ledger->statistics($filters),
            'filters' => $filters,
            'activeView' => $activeView,
            'productOptions' => $this->ledger->productOptions($filters['product_query']),
            'orderStatusOptions' => collect(Order::STATUSES)
                ->reject(fn (string $status) => in_array($status, ['cancelled', 'returned'], true))
                ->map(fn (string $status) => ['label' => $status, 'value' => $status])
                ->values(),
            'paymentMethodOptions' => collect(Payment::METHODS)
                ->map(fn (string $method) => ['label' => $method, 'value' => $method])
                ->values(),
            'perPageOptions' => ProductSalesLedgerFilterRequest::PER_PAGE,
        ]);
    }
}
