<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    protected $fillable = ['orbit_id', 'buyer_logo', 'type_of_transaction', 'industry_sector', 'detailed_business_desc', 'transaction_size', 'member_id', 'deal_manager', 'tombstone_title', 'transaction_excerpt', 'keyphrase', 'tombstone_top_image', 'tombstone_bottom_image', 'approved', 'slug','side','date_transaction'];

    protected $dates = ['date_transaction'];

    public function industrySector() {
        return $this->belongsTo(IndustrySector::class, 'industry_sector', 'id');
    }

    public function member() {
        return $this->belongsTo(Members::class, 'member_id', 'id')->with('region');
    }
}
