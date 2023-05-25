<video preload="metadata" controls class="video-transmorpher @if(!$isReady) d-none @endif">
    <source src="{{ $isReady ? $motif->getMp4Url() : '' }}" type="video/mp4">
    <p style="padding: 5px;">
        Your browser doesn't support HTML video. Here is a
        <a href="{{ $isReady ? $motif->getMp4Url() : '' }}">link to the video</a> instead.
    </p>
</video>
<img data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
     src="{{ !$isReady ? $motif->getUrl() : '' }}"
     alt="{{ $differentiator }}"
     class="dz-image video-transmorpher @if($isReady) d-none @endif"
/>