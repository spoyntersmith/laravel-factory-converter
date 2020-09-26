<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Post extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
    ];
}
