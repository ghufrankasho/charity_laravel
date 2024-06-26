<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    public $timestampe = true;
    protected $fillbale = ['phone','adress','name'];
    public function departments(){
        return $this->hasMany(Department::class);
    }
 

}