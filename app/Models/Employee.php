<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable=['email','address','phone','name','salary','avaliable'];
    public function vacations() {
        return $this->hasMany(Vacation::class);
        
    }
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function account()  {
        return $this->belongsTo(Account::class);
        
    }
}