<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    public $timbestamps = true;
    protected $fillable = ['name' , 'description','start_date' , 'end_date'];
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function user(){
        return $this->hasOne(User::class);
    }
    
    public function projectType(){
        return $this->belongsTo(ProjectType::class);
    }

    public function doners(){
        return $this->belongsToMany(Doner::class);
    }

}