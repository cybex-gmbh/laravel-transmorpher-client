<?php

namespace Transmorpher;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Transmorpher\Enums\MediaType;
use Transmorpher\Exceptions\DuplicateMediaNameException;
use Transmorpher\Exceptions\MissingMorphAliasException;
use Transmorpher\Models\TransmorpherMedia;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransmorpherMedia
{
    protected static Collection $cachedImageMediaNames;
    protected static Collection $cachedDocumentMediaNames;
    protected static Collection $cachedVideoMediaNames;

    /**
     * @throws MissingMorphAliasException
     */
    public static function bootHasTransmorpherMedia(): void
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
     * @return Attribute
     */
    public function images(): Attribute
    {
        return Attribute::make(
            get: fn(): Collection => $this->getImageMediaNames()->mapWithKeys(function (string $mediaName) {
                return [$mediaName => Image::for($this, $mediaName)];
            })
        );
    }

    /**
     * Returns a collection of Documents associated to media names.
     *
     * @return Attribute
     */
    public function documents(): Attribute
    {
        return Attribute::make(
            get: fn(): Collection => $this->getDocumentMediaNames()->mapWithKeys(function (string $mediaName) {
                return [$mediaName => Document::for($this, $mediaName)];
            })
        );
    }

    /**
     * Returns a collection of Videos associated to media names.
     *
     * @return Attribute
     */
    public function videos(): Attribute
    {
        return Attribute::make(
            get: fn(): Collection => $this->getVideoMediaNames()->mapWithKeys(function (string $mediaName) {
                return [$mediaName => Video::for($this, $mediaName)];
            })
        );
    }

    /**
     * Returns a single Image for a media name.
     *
     * @param string $mediaName
     * @return Image|null
     */
    public function image(string $mediaName): ?Image
    {
        if ($this->getImageMediaNames()->contains($mediaName)) {
            return Image::for($this, $mediaName);
        }

        return null;
    }

    /**
     * Returns a single Document for a media name.
     *
     * @param string $mediaName
     * @return Document|null
     */
    public function document(string $mediaName): ?Document
    {
        if ($this->getDocumentMediaNames()->contains($mediaName)) {
            return Document::for($this, $mediaName);
        }

        return null;
    }

    /**
     * Returns a single Video for a media name.
     *
     * @param string $mediaName
     * @return Video|null
     */
    public function video(string $mediaName): ?Video
    {
        if ($this->getVideoMediaNames()->contains($mediaName)) {
            return Video::for($this, $mediaName);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getTransmorpherAlias(): string
    {
        return $this->transmorpherAlias ?? $this->getMorphClass();
    }

    /**
     * @return Collection
     */
    public function getImageMediaNames(): Collection
    {
        return static::$cachedImageMediaNames ??= $this->getMediaNames(Image::class, $this->transmorpherImages ?? []);
    }

    /**
     * @return Collection
     */
    public function getDocumentMediaNames(): Collection
    {
        return static::$cachedDocumentMediaNames ??= $this->getMediaNames(Document::class, $this->transmorpherDocuments ?? []);
    }

    /**
     * @return Collection
     */
    public function getVideoMediaNames(): Collection
    {
        return static::$cachedVideoMediaNames ??= $this->getMediaNames(Video::class, $this->transmorpherVideos ?? []);
    }

    /**
     * @param string $mediaClass
     * @param array $mediaArray
     * @return Collection
     * @throws DuplicateMediaNameException
     */
    protected function getMediaNames(string $mediaClass, array $mediaArray): Collection
    {
        $mediaMethods = $this->getMediaMethods($mediaClass);
        $loweredMediaMethods = $mediaMethods->map('strtolower');
        $loweredMediaNames = collect($mediaArray)->map('strtolower');
        $duplicatesInArray = $loweredMediaNames->duplicates();
        $conflictsWithMethods = $loweredMediaMethods->intersect($loweredMediaNames);

        $duplicates = $conflictsWithMethods->merge($duplicatesInArray)->unique();

        if ($duplicates->count()) {
            throw new DuplicateMediaNameException($this::class, $duplicates);
        }

        return $mediaMethods->merge($mediaArray)->sort(SORT_NATURAL);
    }

    /**
     * @param string $mediaClass
     * @return Collection
     */
    protected function getMediaMethods(string $mediaClass): Collection
    {
        $mediaMethods = collect();
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $reflectionMethodName = $reflectionMethod->getName();

            if (is_a($reflectionMethod->getReturnType()?->getName(), $mediaClass, true) && !in_array(strtolower($reflectionMethodName), MediaType::asArray())) {
                $mediaMethods->push($reflectionMethodName);
            }
        }

        return $mediaMethods;
    }
}
