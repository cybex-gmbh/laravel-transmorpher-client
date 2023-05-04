<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div>
    <span id="csrf" class="d-hidden">@csrf</span>
    <div class="card @if(!$isReady) border-processing @endif">
        <div class="card-header">
            {{ $differentiator }}
            <span class="badge @if($isProcessing) badge-processing @else d-hidden @endif">
                Processing
        </span>
            <div class="details">
                @if ($isImage)
                    <a target="_blank" href="{{ $motif->getUrl() }}"><img src="{{ asset('vendor/transmorpher/icons/magnifying-glass.svg') }}" alt="Enlarge image" class="icon"></a>
                @endif
                <img role="button" src="{{ asset('vendor/transmorpher/icons/more-info.svg') }}" alt="More information" class="icon"
                     onclick="openModal('{{ $motif->getIdentifier() }}')">
            </div>
        </div>
        <div class="card-body">
            <form method="POST" class="dropzone" id="dz-{{ $motif->getIdentifier() }}">
                @if ($isImage)
                    <div class="dz-image image-transmorpher">
                        <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                             data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                             src="{{$motif->getUrl(['height' => 150])}}"
                             alt="{{ $differentiator }}"/>
                    </div>
                @else
                        <video preload="metadata" controls class="video-transmorpher @if(!$isReady) d-none @endif">
                            <source src="{{ $isReady ? $motif->getMp4Url() : '' }}" type="video/mp4">
                            <p style="padding: 5px;">
                                Your browser doesn't support HTML video. Here is a
                                <a href="{{ $isReady ? $motif->getMp4Url() : '' }}">link to the video</a> instead.
                            </p>
                        </video>
                        <img data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                             src="{{ !$isReady ? $motif->getUrl() : '' }}"
                             alt="{{ $differentiator }}"
                             class="video-transmorpher @if($isReady) d-none @endif"/>
                @endif
            </form>
        </div>
    </div>

    <div id="modal-{{$motif->getIdentifier()}}" class="modal card d-none">
        <div class="card-header">
            {{ $differentiator }}
            <button class="btn-close" onclick="closeModal(this)">⨉</button>
        </div>
        <div class="card-body">
            <div class="versionInformation">
                <p>Current version: <span class="currentVersion"></span></p>
                <p>Version overview:</p>
                <ul class="versionList"></ul>
            </div>
            <button class="badge badge-error" onclick="deleteTransmorpherMedia('{{ $motif->getIdentifier() }}')">
                Delete
            </button>
        </div>
    </div>
    <div class="modal-overlay d-none"></div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    motifs['{{ $motif->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        csrfToken: document.querySelector('#csrf > input[name="_token"]').value,
        routes: {
            stateUpdate: '{{ $stateUpdateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}',
            getVersions: '{{ $getVersionsRoute }}',
            setVersion: '{{ $setVersionRoute }}',
            delete: '{{ $deleteRoute }}'
        }
    }

    form = document.querySelector('#dz-{{ $motif->getIdentifier() }}');
    card = form.closest('.card');
    cardHeader = card.querySelector('.badge');

    if ('{{ !$isImage }}' && cardHeader.classList.contains('badge-processing')) {
        startPolling('{{ $motif->getIdentifier() }}', '{{ $lastUploadToken }}');
    }

    new Dropzone("#dz-{{$motif->getIdentifier()}}", {
        url: '{{ $motif->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $motif->getChunkSize() }},
        maxFilesize: {{ $motif->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $motif->getMaxFileSize() }},
        timeout: 60000,
        uploadMultiple: false,
        paramName: 'file',
        idToken: null,
        uploadToken: null,
        init: function () {
            this.on('addedfile', function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            this.element.querySelector('.dz-default').style.display = 'none';

            if (errorElement = this.element.querySelector('.dz-error')) {
                errorElement.remove();
            }

            fetch('{{ $uploadTokenRoute }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': motifs['{{ $motif->getIdentifier() }}'].csrfToken,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $transmorpherMediaKey }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (!data.success) {
                    this.options.idToken = data.id_token;
                    done(data);
                }
                this.options.uploadToken = data.upload_token
                this.options.url = '{{ $motif->getWebUploadUrl() }}' + data.upload_token;
                this.options.params = function (files, xhr, chunk) {
                    if (chunk) {
                        return {
                            dzuuid: chunk.file.upload.uuid,
                            dzchunkindex: chunk.index,
                            dztotalfilesize: chunk.file.size,
                            dzchunksize: this.options.chunkSize,
                            dztotalchunkcount: chunk.file.upload.totalChunkCount,
                            dzchunkbyteoffset: chunk.index * this.options.chunkSize,
                        };
                    }
                }
                this.options.idToken = data.id_token;
                done()
            });
        },
        success: function (file, response) {
            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.querySelector('.dz-preview').remove();

            clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);
            updateImageDisplay('{{$motif->getIdentifier()}}', response.public_path, '{{ $motif->getTransformations(['height' => 150]) }}', response.version)
            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.idToken,
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.idToken,
                this.options.uploadToken
            );
        },
    });
</script>
