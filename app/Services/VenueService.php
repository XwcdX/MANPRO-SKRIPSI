<?php

namespace App\Services;

use App\Models\PresentationVenue;

class VenueService
{
    public function createVenue(array $data): PresentationVenue
    {
        $venue = new PresentationVenue();
        $venue->name = $data['name'];
        $venue->location = $data['location'];
        $venue->save();
        
        return $venue;
    }

    public function updateVenue(PresentationVenue $venue, array $data): PresentationVenue
    {
        $venue->name = $data['name'];
        $venue->location = $data['location'];
        $venue->save();
        
        return $venue;
    }

    public function deleteVenue(string $venueId): bool
    {
        $venue = PresentationVenue::find($venueId);
        if ($venue) {
            $venue->delete();
            return true;
        }
        return false;
    }
}
