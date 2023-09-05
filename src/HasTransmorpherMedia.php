<?php

namespace Transmorpher;

use Illuminate\Support\Collection;
use Transmorpher\Exceptions\MissingMorphAliasException;
use Transmorpher\Models\TransmorpherMedia;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransmorpherMedia
{
    /**
     * @throws MissingMorphAliasException
     */
    public static function bootHasTransmorpherMedia()
    {
        if (static::getModel()->getTransmorpherAlias() === static::class) {
            throw new MissingMorphAliasException(static::class);
        }
    }

    /**
     * Return all transmorpher media.
     *
     * @return MorphMany
     */
    public function TransmorpherMedia(): MorphMany
    {
        return $this->morphMany(TransmorpherMedia::class, 'transmorphable');
    }

    /**
     * Returns a collection of Images associated to media names.
     *
     * @return Collection
     */
    public function images(): Collection
    {
        return collect($this->transmorpherImages)->mapWithKeys(function (string $mediaName) {
            return [$mediaName => Image::getInstanceFor($this, $mediaName)];
        });
    }

    /**
     * Returns a collection of Videos associated to media names.
     *
     * @return Collection
     */
    public function videos(): Collection
    {
        return collect($this->transmorpherVideos)->mapWithKeys(function (string $mediaName) {
            return [$mediaName => Video::getInstanceFor($this, $mediaName)];
        });
    }

    /**
     * @return string
     */
    public function getTransmorpherAlias(): string
    {
        return $this->transmorpherAlias ?? $this->getMorphClass();
    }
}
