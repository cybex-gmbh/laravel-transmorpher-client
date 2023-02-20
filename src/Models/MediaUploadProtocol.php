<?php

namespace Cybex\Transmorpher\Models;

use Cybex\Transmorpher\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUploadProtocol extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'state',
        'public_path',
        'id_token',
        'media_upload_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'state' => State::class,
    ];

    /**
     * Returns the MediaUpload this protocol entry belongs to.
     *
     * @return BelongsTo
     */
    public function MediaUpload(): BelongsTo
    {
        return $this->belongsTo(MediaUpload::class);
    }
}
