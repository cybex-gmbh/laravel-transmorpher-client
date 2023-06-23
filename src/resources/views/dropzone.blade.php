<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div @class(['card', 'border-processing' => !$isReady])>
    <div class="card-header">
        {{$differentiator}}
        <span @class(['badge', 'badge-processing' => $isProcessing, 'd-none' => !$isProcessing])>
            @if($isProcessing)
                Processing
            @endif
        </span>
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $motif->getIdentifier() }}">
            @if ($isImage)
                <div class="dz-image image-transmorpher">
                    <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                         src="{{$motif->getUrl(['height' => 150])}}"
                         alt="{{ $differentiator }}"/>
                </div>
            @else
                @if ($isReady)
                    <video preload="metadata" controls style="height:150px" class="video-transmorpher">
                        <source src="{{ $motif->getMp4Url() }}" type="video/mp4">
                        <p style="padding: 5px;">
                            Your browser doesn't support HTML video. Here is a
                            <a href="{{ $motif->getMp4Url() }}">link to the video</a> instead.
                        </p>
                    </video>
                @else
                    <img src="{{ $motif->getUrl() }}"
                         alt="{{ $differentiator }}"
                         class="video-transmorpher"/>
                @endif
            @endif
        </form>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    motifs['{{ $motif->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        routes: {
            stateUpdate: '{{ $stateUpdateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}'
        }
    }

    // Start polling if the video is still processing.
    if ('{{ !$isImage }}' && '{{ $isProcessing }}') {
        startPolling('{{ $motif->getIdentifier() }}', '{{ $latestUploadToken }}');
    }

    new Dropzone('#{{$motif->getIdentifier()}}', {
        url: '{{ $motif->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $motif->getChunkSize() }},
        maxFilesize: {{ $motif->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $motif->getMaxFileSize() }},
        timeout: 60000,
        uploadMultiple: false,
        paramName: 'file',
        uploadToken: null,
        init: function () {
            // Remove all other files when a new file is dropped in. Only 1 simultaneous upload is allowed.
            this.on('addedfile', function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            // Remove previous elements to maintain a clean overlay.
            this.element.querySelector('.dz-default').style.display = 'none';
            if (errorElement = this.element.querySelector('.dz-error')) {
                errorElement.remove();
            }

            // Reserve an upload slot at the Transmorpher media server.
            fetch('{{ $uploadTokenRoute }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $transmorpherMediaKey }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (!data.success) {
                    done(data);
                }

                this.options.uploadToken = data.upload_token
                // Set the dropzone target to the media server upload url, which needs a valid upload token.
                this.options.url = `{{ $motif->getWebUploadUrl() }}${data.upload_token}`;
                done()
            });
        },
        success: function (file, response) {
            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.querySelector('.dz-preview').remove();

            // Clear the old timer.
            clearInterval(window['statusPolling{{ $motif->getIdentifier() }}']);

            // Set the newly uploaded image as display image.
            if ('{{ $isImage }}') {
                imgElement = this.element.querySelector('div.dz-image.image-transmorpher > img');
                imgElement.src = `${imgElement.dataset.deliveryUrl}/${response.public_path}/{{ $motif->getTransformations(['height' => 150]) }}?v=${response.version}`;
            }

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            // Clear the old timer.
            clearInterval(window['statusPolling{{ $motif->getIdentifier() }}']);

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
    });
</script>
