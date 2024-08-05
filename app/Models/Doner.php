<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doner extends Model
{
    use HasFactory;
    public $timbestamps = true;
    protected $fillable = ['phone' ,'address' , 'email' , 'name'];
    
    public function projects(){
        return $this->belongsToMany(Project::class);
    }
  
    public function account()  {
        return $this->belongsTo(Account::class);
        
    }
}