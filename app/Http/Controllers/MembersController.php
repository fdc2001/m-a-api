<?php

namespace App\Http\Controllers;

use App\Models\Members;
use App\Models\Regions;
use Illuminate\Http\Request;

class MembersController extends Controller {
     public function syncMembers() {
        $response = ConfigsController::requestOrbit('GET', 'Member');
        $members = $response;
        foreach($members as $member) {
            $region = Regions::where('orbit_id', $member['regionID'])->first();
            Members::updateOrCreate(['orbit_id'=>$member['memberID']],['name' => $member['name'],'region_id'=>$region->id, 'active' => $member['active']]);
        }

         return response()->json(['message'=>'All data synced'], 200);
     }

        public function get() {
            return Members::where('active', true)->get();
        }
}
