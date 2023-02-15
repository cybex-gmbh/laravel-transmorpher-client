<?php

namespace Cybex\Transmorpher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaUpload extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uploadable_type',
        'uploadable_id',
        'differentiator',
        'id_token',
    ];

    public function Uploadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function MediaUploadProtocols(): HasMany
    {
        return $this->hasMany(MediaUploadProtocol::class);
    }
}