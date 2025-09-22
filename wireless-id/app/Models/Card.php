<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'uid'
    ];
    
    
    public function user(){
        return $this->belongsTo(User::class);
    }
}
