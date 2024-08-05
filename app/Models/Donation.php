<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;
    public $timbestampe = true;
    public $fillable = ['amount' , 'detailes'];
    
    public function account(){
        return $this->belongsTo(Account::class);
    }
    
    public function project(){
        return $this->belongsTo(Project::class);
    }
}