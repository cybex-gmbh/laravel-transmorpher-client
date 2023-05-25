<a class="full-size-link" target="_blank" href="{{ $motif->getUrl() }}">
    <div class="dz-image image-transmorpher">
        <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
             data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
             src="{{ $motif->getUrl(['height' => 150]) }}"
             alt="{{ $differentiator }}"/>
        <img src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}" alt="Enlarge image" class="icon enlarge-icon">
    </div>
</a>