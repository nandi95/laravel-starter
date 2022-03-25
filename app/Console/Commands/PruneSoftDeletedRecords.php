<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PruneSoftDeletedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:softDeleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all soft-deleted records  that are older than 2 weeks.';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        DB::transaction(function () {
            $softDeletedKey = 'deleted_at';

            Product::where($softDeletedKey, '<=', now()->subWeeks(2))->delete();
            ProductVariation::where($softDeletedKey, '<=', now()->subWeeks(2))->delete();
            Ingredient::where($softDeletedKey, '<=', now()->subWeeks(2))->delete();
        });

        return Command::SUCCESS;
    }
}
