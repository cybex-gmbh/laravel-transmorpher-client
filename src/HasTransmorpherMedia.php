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
     * Returns a collection with ImageTransmorphers associated to topics.
     *
     * @return Collection
     */
    public function images(): Collection
    {
        return collect($this->transmorpherImages)->mapWithKeys(function (string $topicName) {
            return [$topicName => ImageTopic::getInstanceFor($this, $topicName)];
        });
    }

    /**
     * Returns a collection with VideoTransmorphers associated to topics.
     *
     * @return Collection
     */
    public function videos(): Collection
    {
        return collect($this->transmorpherVideos)->mapWithKeys(function (string $topicName) {
            return [$topicName => VideoTopic::getInstanceFor($this, $topicName)];
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
