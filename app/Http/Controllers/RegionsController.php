<?php

namespace App\Http\Controllers;

use App\Models\Configs;
use App\Models\Regions;


class RegionsController extends Controller {
    public function syncRegions() {
        $response = ConfigsController::requestOrbit('GET', 'Region');
        $members = $response;
        foreach($members as $member) {
            Regions::updateOrCreate(['orbit_id'=>$member['regionID']],['name' => $member['name']]);
        }

        return response()->json(['message'=>'All data synced'], 200);
    }
}
