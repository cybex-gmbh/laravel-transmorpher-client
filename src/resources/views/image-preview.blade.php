<div class="media-display">
    <a class="full-size-link" target="_blank" href="{{ $topic->getUrl() }}">
        <div class="dz-image image-transmorpher">
            <img data-delivery-url="{{ $topic->getDeliveryUrl() }}"
                 data-placeholder-url="{{ $topic->getPlaceholderUrl() }}"
                 srcset="{{ $topic->getUrl(['width' => 150]) }} 150w, {{ $topic->getUrl(['width' => 300]) }} 300w, {{ $topic->getUrl(['width' => 600]) }} 600w, {{ $topic->getUrl(['width' => 900]) }} 900w"
                 sizes="(max-width: 300px) 150px, (max-width: 600px) 300px, (max-width: 900px) 600px, 900px"
                 src="{{ $topic->getUrl(['width' => 300]) }}"
                 alt="@lang('transmorpher::image-alt-tags.preview_image', ['topic_name' => $topicName])"/>
            <img role="link" src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}"
                 alt="@lang('transmorpher::image-alt-tags.show_image_in_full_size')" @class(['icon', 'enlarge-icon', 'd-hidden' => !$isReady])>
        </div>
    </a>
</div>
