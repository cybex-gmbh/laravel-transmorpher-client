<div class="media-display">
    <a class="full-size-link" target="_blank" href="{{ $motif->getUrl() }}">
        <div class="dz-image image-transmorpher">
            <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                 data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                 srcset="{{ $motif->getUrl(['width' => 150]) }} 150w, {{ $motif->getUrl(['width' => 300]) }} 300w, {{ $motif->getUrl(['width' => 600]) }} 600w, {{ $motif->getUrl(['width' => 900]) }} 900w"
                 sizes="(max-width: 300px) 150px, (max-width: 600px) 300px, (max-width: 900px) 600px, 900px"
                 src="{{ $motif->getUrl(['width' => 300]) }}"
                 alt="{{ $differentiator }}"/>
            <img src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}" alt="Enlarge image" @class(['icon', 'enlarge-icon', 'd-hidden' => !$isReady])>
        </div>
    </a>
</div>
