<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    public function account(){
        return $this->belongsTo(Doner::class);
    }
    
    public function projects(){
        return $this->belongsToMany(Project::class,'cart_projects');
    }
}