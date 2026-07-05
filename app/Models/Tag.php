<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('tags')]
class Tag extends Model
{
    // Field yang boleh diisi
    protected $fillable = ['name', 'slug'];

}
