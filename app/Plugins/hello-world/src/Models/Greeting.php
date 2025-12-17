<?php

namespace App\Plugins\hello_world\src\Models;

use Illuminate\Database\Eloquent\Model;

class Greeting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'hello_world_greetings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'message',
        'author',
    ];
}
