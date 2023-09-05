<div class="media-display">
    <a class="full-size-link" target="_blank" href="{{ $media->getUrl() }}">
        <div class="dz-image image-transmorpher">
            <img data-delivery-url="{{ $media->getDeliveryUrl() }}"
                 data-placeholder-url="{{ $media->getPlaceholderUrl() }}"
                 srcset="{{ $media->getUrl(['width' => 150]) }} 150w, {{ $media->getUrl(['width' => 300]) }} 300w, {{ $media->getUrl(['width' => 600]) }} 600w, {{ $media->getUrl(['width' => 900]) }} 900w"
                 sizes="(max-width: 300px) 150px, (max-width: 600px) 300px, (max-width: 900px) 600px, 900px"
                 src="{{ $media->getUrl(['width' => 300]) }}"
                 alt="@lang('transmorpher::image-alt-tags.preview_image', ['media_name' => $mediaName])"/>
            <img role="link" src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}"
                 alt="@lang('transmorpher::image-alt-tags.show_image_in_full_size')" @class(['icon', 'enlarge-icon', 'd-hidden' => !$isReady])>
        </div>
    </a>
</div>
