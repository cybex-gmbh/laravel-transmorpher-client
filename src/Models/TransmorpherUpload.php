<?php

namespace Transmorpher\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Transmorpher\Enums\UploadState;

class TransmorpherUpload extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'state',
        'message',
        'token',
        'transmorpher_media_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'state' => UploadState::class,
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (TransmorpherUpload $transmorpherUpload) {
            if ($transmorpherUpload->isLatest) {
                $transmorpherUpload->TransmorpherMedia()->update(['latest_upload_state' => $transmorpherUpload->state, 'latest_upload_token' => $transmorpherUpload->token]);
            }
        });
    }

    /**
     * Returns the TransmorpherMedia this upload belongs to.
     *
     * @return BelongsTo
     */
    public function TransmorpherMedia(): BelongsTo
    {
        return $this->belongsTo(TransmorpherMedia::class);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function handleStateUpdate(array $response, int $httpCode = null): array
    {
        $transmorpher = $this->TransmorpherMedia->getMedia();

        // When this method is called from within a Media, the response for the frontend is already determined.
        // When this method is directly called from a controller (e.g. failed/successful upload), the server response still has to be checked (and replaced in case of errors).
        if ($httpCode) {
            $response = $transmorpher->generateResponseForClient($response, $httpCode);
        }

        if ($response['state'] !== UploadState::ERROR->value) {
            $transmorpher->updateAfterSuccessfulUpload($response, $this);
        } else {
            $this->update(['state' => $response['state'], 'message' => $response['message']]);
        }

        $response['latestUploadToken'] = $this->TransmorpherMedia->latest_upload_token;

        return $response;
    }

    public function isLatest(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => $this->is($this->TransmorpherMedia->TransmorpherUploads()->latest()->first())
        );
    }
}
