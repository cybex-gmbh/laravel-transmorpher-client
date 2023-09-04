<div class="media-display">
    <a class="full-size-link" target="_blank" href="{{ $topicHandler->getUrl() }}">
        <div class="dz-image image-transmorpher">
            <img data-delivery-url="{{ $topicHandler->getDeliveryUrl() }}"
                 data-placeholder-url="{{ $topicHandler->getPlaceholderUrl() }}"
                 srcset="{{ $topicHandler->getUrl(['width' => 150]) }} 150w, {{ $topicHandler->getUrl(['width' => 300]) }} 300w, {{ $topicHandler->getUrl(['width' => 600]) }} 600w, {{ $topicHandler->getUrl(['width' => 900]) }} 900w"
                 sizes="(max-width: 300px) 150px, (max-width: 600px) 300px, (max-width: 900px) 600px, 900px"
                 src="{{ $topicHandler->getUrl(['width' => 300]) }}"
                 alt="@lang('transmorpher::image-alt-tags.preview_image', ['topic' => $topic])"/>
            <img role="link" src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}"
                 alt="@lang('transmorpher::image-alt-tags.show_image_in_full_size')" @class(['icon', 'enlarge-icon', 'd-hidden' => !$isReady])>
        </div>
    </a>
</div>
