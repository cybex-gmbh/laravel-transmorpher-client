<div class="card">
    <div class="card-header">
        <span class="version-age"></span>
    </div>
    <div class="card-body">
        <div class="media-preview transparency-indicator">
            <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :topic="$topic"/>
        </div>
        <button class="button button-confirm confirm-restore">
            <span>@lang('transmorpher::dropzone.restore')</span>
            <img src="{{ mix('icons/restore.svg', 'vendor/transmorpher') }}" alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => 'Restore version'])" class="icon">
        </button>
    </div>
</div>
