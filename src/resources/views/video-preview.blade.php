<div class="media-display">
    <video preload="metadata" controls @class(['video-transmorpher', 'd-none' => !$isReady])>
        <source src="{{ $isReady ? $topicHandler->getMp4Url() : '' }}" type="video/mp4">
        <p style="padding: 5px;">
            <a href="{{ $isReady ? $topicHandler->getMp4Url() : '' }}">@lang('transmorpher::dropzone.html_video_not_supported')</a>
        </p>
    </video>
    <img data-placeholder-url="{{ $topicHandler->getPlaceholderUrl() }}"
         src="{{ !$isReady ? $topicHandler->getUrl() : '' }}"
         alt="@lang('transmorpher::image-alt-tags.placeholder_image', ['topic' => $topic])"
            @class(['dz-image', 'video-transmorpher', 'd-none' => $isReady])
    />
</div>
