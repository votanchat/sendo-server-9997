<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'id'; // or null

    public $incrementing = false;
    protected $hidden = [
        'pass','token'
    ];
    public $timestamps = false;
}
