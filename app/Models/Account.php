<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Account extends Authenticatable implements JWTSubject
{
    use HasFactory;
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
    public function doner()
    {
        return $this->hasOne(Doner::class);
    }
    public function donations(){
        return $this->hasMany(Donation::class);
    }
    public function cart(){
        return $this->hasone(Cart::class);
    }
    protected $fillable = [
        'email', 'password','type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
}