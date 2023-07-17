<?php

namespace App\Http\Controllers;

use App\Models\IndustrySector;
use Illuminate\Http\Request;

class IndustrySectorsController extends Controller {
    public function syncIndustry() {
        $response = ConfigsController::requestOrbit('GET', 'Industry');
        $members = $response;
        foreach($members as $member) {
            IndustrySector::updateOrCreate(['orbit_id'=>$member['industryID']],['name' => $member['name']]);
        }

        return response()->json(['message'=>'All data synced'], 200);
    }

    public function get() {
        return IndustrySector::all();
    }
}
