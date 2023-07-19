<div class="media-display">
    <video preload="metadata" controls @class(['video-transmorpher', 'd-none' => !$isReady])>
        <source src="{{ $isReady ? $motif->getMp4Url() : '' }}" type="video/mp4">
        <p style="padding: 5px;">
            <a href="{{ $isReady ? $motif->getMp4Url() : '' }}">@lang('transmorpher::dropzone.html_video_not_supported')</a>
        </p>
    </video>
    <img data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
         src="{{ !$isReady ? $motif->getUrl() : '' }}"
         alt="@lang('transmorpher::image-alt-tags.placeholder_image', ['differentiator' => $differentiator])"
            @class(['dz-image', 'video-transmorpher', 'd-none' => $isReady])
    />
</div>
