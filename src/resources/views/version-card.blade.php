<div class="card">
    <div class="card-header">
        <span class="version-age"></span>
    </div>
    <div class="card-body">
        <div class="transparency-indicator">
            <div class="media-preview">
                <div class="media-display">
                    <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :motif="$motif"/>
                </div>
            </div>
        </div>
        <button class="button button-hold hold-restore">
            <span>Restore</span>
            <img src="{{ mix('icons/restore.svg', 'vendor/transmorpher') }}" alt="Restore" class="icon">
        </button>
    </div>
</div>
