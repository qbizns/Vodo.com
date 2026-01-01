<?php

declare(strict_types=1);

namespace VodoCommerce\Console\Commands;

use Illuminate\Console\Command;
use VodoCommerce\Models\InventoryReservation;

/**
 * Cleanup Expired Inventory Reservations Command
 *
 * Removes inventory reservations that have passed their expiration time.
 * Should be scheduled to run frequently (every 5-15 minutes) to release
 * held stock from abandoned carts.
 */
class CleanupReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:reservations:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired inventory reservations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run mode - no reservations will be deleted');
            $this->newLine();

            $expiredReservations = InventoryReservation::expired()
                ->with(['product', 'variant', 'cart'])
                ->get();

            if ($expiredReservations->isEmpty()) {
                $this->info('No expired reservations found.');

                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Product', 'Variant', 'Quantity', 'Cart ID', 'Expired At'],
                $expiredReservations->map(fn($reservation) => [
                    $reservation->id,
                    $reservation->product?->name ?? "Product #{$reservation->product_id}",
                    $reservation->variant?->sku ?? '-',
                    $reservation->quantity,
                    $reservation->cart_id ?? 'N/A',
                    $reservation->expires_at->toDateTimeString(),
                ])
            );

            $this->newLine();
            $this->info("Found {$expiredReservations->count()} expired reservation(s).");
            $this->info('Run without --dry-run to delete these reservations.');

            return self::SUCCESS;
        }

        $this->info('Cleaning up expired inventory reservations...');

        $count = InventoryReservation::cleanupExpired();

        if ($count === 0) {
            $this->info('No expired reservations to clean up.');
        } else {
            $this->info("Deleted {$count} expired reservation(s).");
        }

        return self::SUCCESS;
    }
}
