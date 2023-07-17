<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrySector extends Model {
    protected $fillable = ['orbit_id', 'name'];

    public function transactionsLimited() {
        return $this->hasMany(Transaction::class, 'industry_sector', 'id')->where('approved','=',1)->take(15)
            ->select('type_of_transaction', 'industry_sector', 'slug', 'tombstone_bottom_image', 'tombstone_top_image', 'member_id', 'approved','date_transaction','side','tombstone_title')
            ->with('member', 'industrySector');
    }
    public function transactions() {
        return $this->hasMany(Transaction::class, 'industry_sector', 'id')->where('approved','=',1)
            ->select('type_of_transaction', 'industry_sector', 'slug', 'tombstone_bottom_image', 'tombstone_top_image', 'member_id', 'approved','date_transaction','side','tombstone_title')
            ->with('member', 'industrySector');
    }
}
