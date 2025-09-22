<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    protected $fillable = [
        'uid'
    ];
    
    
    public function user() : HasOne {
        return $this->hasOne(User::class);
    }
}
