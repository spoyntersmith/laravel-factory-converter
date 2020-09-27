<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $title
 * @property string $content
 */
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
