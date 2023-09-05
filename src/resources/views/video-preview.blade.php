<div class="media-display">
    <video preload="metadata" controls @class(['video-transmorpher', 'd-none' => !$isReady])>
        <source src="{{ $isReady ? $topic->getMp4Url() : '' }}" type="video/mp4">
        <p style="padding: 5px;">
            <a href="{{ $isReady ? $topic->getMp4Url() : '' }}">@lang('transmorpher::dropzone.html_video_not_supported')</a>
        </p>
    </video>
    <img data-placeholder-url="{{ $topic->getPlaceholderUrl() }}"
         src="{{ !$isReady ? $topic->getUrl() : '' }}"
         alt="@lang('transmorpher::image-alt-tags.placeholder_image', ['topic_name' => $topicName])"
            @class(['dz-image', 'video-transmorpher', 'd-none' => $isReady])
    />
</div>
