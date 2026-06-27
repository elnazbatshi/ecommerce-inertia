<?php

namespace App\Console\Commands;

use App\Models\CustomerOtp;
use Illuminate\Console\Command;

class PruneCustomerOtps extends Command
{
    protected $signature = 'customer-otps:prune';

    protected $description = 'Delete expired customer OTP records.';

    public function handle(): int
    {
        $count = CustomerOtp::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Deleted {$count} expired customer OTP records.");

        return self::SUCCESS;
    }
}
