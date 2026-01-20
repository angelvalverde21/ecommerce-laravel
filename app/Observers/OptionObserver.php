<?php

namespace App\Observers;

use App\Models\Option;
use App\Models\Status;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionObserver
{
    /**
     * Handle the Option "created" event.
     */
    public function created(Option $option): void
    {
        //

        DB::transaction(function () use ($option) {

            Variant::where('product_id', $option->product_id)
                ->where('status', Status::ACTIVE)
                ->update([
                    'status' => Status::ARCHIVED,
                ]);

            $option->load(['product', 'option_values']);

            if ($option->option_values->isEmpty()) {
                Log::info('Options sin option_values, no se generan variantes');
                return;
            }

            UpdateSkus($option->product);
            
        });
    }

    /**
     * Handle the Option "updated" event.
     */
    public function updated(Option $option): void
    {
        //
    }

    /**
     * Handle the Option "deleted" event.
     */
    public function deleted(Option $option): void
    {
        //
    }

    /**
     * Handle the Option "restored" event.
     */
    public function restored(Option $option): void
    {
        //
    }

    /**
     * Handle the Option "force deleted" event.
     */
    public function forceDeleted(Option $option): void
    {
        //
    }
}
