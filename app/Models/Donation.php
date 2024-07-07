<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;
    public $timbestampe = true;
    protected $fillable = ['amount' , 'detailes'];
    
    public function doner(){
        return $this->belongsTo(Doner::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function project(){
        return $this->belongsTo(Project::class);
    }
}