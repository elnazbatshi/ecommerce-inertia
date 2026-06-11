<?php

namespace App\Console\Commands;

use App\Http\Services\OrderInventoryService;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredOrderStockCommand extends Command
{
    protected $signature = 'orders:release-expired-stock {--minutes=30}';

    protected $description = 'Release reserved stock for unpaid pending orders older than the configured hold window.';

    public function handle(OrderInventoryService $inventory): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);
        $released = 0;

        Order::query()
            ->where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('stock_reserved_at')
            ->whereNull('stock_released_at')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($inventory, &$released) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($inventory, $order, &$released) {
                        $inventory->release($order->fresh(), null, 'return');
                        $released++;
                    });
                }
            });

        $this->info("Released reserved stock for {$released} expired orders.");

        // Schedule example: run `orders:release-expired-stock --minutes=30` every few minutes.
        return self::SUCCESS;
    }
}
