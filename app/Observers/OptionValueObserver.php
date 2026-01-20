<?php

namespace App\Observers;

use App\Models\OptionValue;

class OptionValueObserver
{
    /**
     * Handle the OptionValue "created" event.
     */
    public function created(OptionValue $optionValue): void
    {
        //

        $optionValue->load('option.product');

        UpdateSkus($optionValue->option->product);
    }

    /**
     * Handle the OptionValue "updated" event.
     */
    public function updated(OptionValue $optionValue): void
    {
        //
    }

    /**
     * Handle the OptionValue "deleted" event.
     */
    public function deleted(OptionValue $optionValue): void
    {
        //
    }

    /**
     * Handle the OptionValue "restored" event.
     */
    public function restored(OptionValue $optionValue): void
    {
        //
    }

    /**
     * Handle the OptionValue "force deleted" event.
     */
    public function forceDeleted(OptionValue $optionValue): void
    {
        //
    }
}
