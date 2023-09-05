<div class="media-display">
    <video preload="metadata" controls @class(['video-transmorpher', 'd-none' => !$isReady])>
        <source src="{{ $isReady ? $media->getMp4Url() : '' }}" type="video/mp4">
        <p style="padding: 5px;">
            <a href="{{ $isReady ? $media->getMp4Url() : '' }}">@lang('transmorpher::dropzone.html_video_not_supported')</a>
        </p>
    </video>
    <img data-placeholder-url="{{ $media->getPlaceholderUrl() }}"
         src="{{ !$isReady ? $media->getUrl() : '' }}"
         alt="@lang('transmorpher::image-alt-tags.placeholder_image', ['media_name' => $mediaName])"
            @class(['dz-image', 'video-transmorpher', 'd-none' => $isReady])
    />
</div>
