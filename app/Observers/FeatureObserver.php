<?php

namespace App\Observers;

use App\Models\Feature;

class FeatureObserver
{
    /**
     * Handle the Feature "updating" event.
     */
    public function updating(Feature $feature): void
    {
        if ($feature->getOriginal('status') === 'done') {
            // Check if they are trying to change core defining fields
            if ($feature->isDirty(['title', 'description', 'project_id'])) {
                throw new \Exception("Feature Permanence Violation: Cannot modify the core definition of a 'done' Feature. Please create a new Feature to extend or replace this behavior.");
            }
            
            // If they are trying to move it out of done back to an active state
            if ($feature->isDirty('status') && in_array($feature->status, ['ready', 'building', 'qa'])) {
                throw new \Exception("Feature Permanence Violation: A 'done' Feature cannot be reverted to an active state ({$feature->status}). You may only transition it to 'deprecated' or 'replaced'.");
            }
        }
    }
}
