<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Members extends Model {
    protected $fillable= ['orbit_id', 'name', 'region_id'];

    public function region() {
        return $this->belongsTo(Regions::class);
    }
}
