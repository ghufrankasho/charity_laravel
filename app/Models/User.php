<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;
 

class User extends Model
{
    use HasFactory, Notifiable;

     
    public function work()  {
        return $this->belongsTo(Work::class);
        
    }
    public function account()  {
        return $this->belongsTo(Account::class);
        
    }

    public function project(){
        return $this->belongsTo(Project::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }
    public function donations(){
        return $this->hasMany(Donation::class);
    }
    protected $fillable = [
        'last_name','first_name', 'email', 'password','address','mobile','salary'
    ];

   
}