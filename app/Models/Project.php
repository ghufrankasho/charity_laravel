<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = ['name' , 'description','start_date' , 'end_date','fundrise'];
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function user(){
        return $this->hasMany(User::class);
    }
    
    public function projectType(){
        return $this->belongsTo(ProjectType::class);
    }

    public function doners(){
        return $this->belongsToMany(Doner::class);
    }
    public function donations(){
        return $this->hasMany(Donation::class);
    }
    public function carts(){
        return $this->belongsToMany(Cart::class,'cart_projects');
    }

}