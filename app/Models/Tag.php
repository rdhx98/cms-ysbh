<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('tags')]
class Tag extends Model
{
    // Field yang boleh diisi 
    protected $fillable = ['name', 'slug'];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

}
