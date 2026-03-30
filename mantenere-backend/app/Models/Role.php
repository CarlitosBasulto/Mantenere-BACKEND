<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'id',
        'name',
        'hierarchy_level',
    ];

    public $incrementing = false; // importante porque id 0 y 1
    protected $keyType = 'int';
}
