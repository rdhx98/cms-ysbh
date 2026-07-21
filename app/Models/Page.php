<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('pages')]
class Page extends Model
{
    use HasFactory;

    // Field yang boleh diisi massal
    protected $fillable = ['title', 'slug', 'content', 'status', 'created_at', 'updated_at'];
}
