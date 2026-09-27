<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Media;

class MediaFolder extends Model
{
    //
    protected $fillable = ['name', 'parent_id'];
 
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }
 
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }
 
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }
 
    /**
     * Jumlah berkas langsung di folder ini (tidak termasuk sub-folder) —
     * dipakai untuk badge jumlah di panel pohon folder, mis. "Malaria (42)".
     */
    public function fileCount(): int
    {
        return $this->media()->count();
    }
 
    /**
     * Rangkaian breadcrumb dari root sampai folder ini, mis.
     * ['Foto Program', 'Malaria'] — dipakai untuk render breadcrumb
     * di header file manager.
     */
    public function breadcrumbTrail(): array
    {
        $trail = [];
        $node = $this;
        while ($node !== null) {
            array_unshift($trail, $node->name);
            $node = $node->parent;
        }
        return $trail;
    }
}
