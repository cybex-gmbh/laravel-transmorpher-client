<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div class="card @if($transmorpher->getTransmorpherMedia()->is_ready === 0) border-warning @endif">
    <div class="card-header">
        {{$transmorpher->getTransmorpherMedia()->differentiator}}
        <span class="badge @if($transmorpher->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING) badge-processing @else d-none @endif">
            @if($transmorpher->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING)
                Processing @endif
        </span>
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $transmorpher->getIdentifier() }}"
              action="{{ $transmorpher->getWebUploadUrl() }}">
            @csrf
            @if ($transmorpher->getTransmorpherMedia()->type === \Transmorpher\Enums\MediaType::IMAGE)
                <div class="dz-image image-transmorpher">
                    <img data-delivery-url="{{ $transmorpher->getDeliveryUrl() }}"
                         src="{{$transmorpher->getUrl(['height' => 150])}}"
                         alt="{{$transmorpher->getTransmorpherMedia()->differentiator}}"/>
                </div>
            @endif
        </form>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    new Dropzone("#{{$transmorpher->getIdentifier()}}", {
        url: '{{ $transmorpher->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $transmorpher->getChunkSize() }},
        maxFilesize: {{ $transmorpher->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $transmorpher->getMaxFileSize() }},
        timeout: 60000,
        uploadMultiple: false,
        paramName: '{{ $transmorpher->getTransmorpherMedia()->type }}',
        idToken: null,
        init: function () {
            this.on("addedfile", function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            fetch('{{ $transmorpher->getUploadTokenRoute() }}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": document.querySelector("#{{$transmorpher->getIdentifier()}} > input[name='_token']").value,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $transmorpher->getTransmorpherMedia()->getKey() }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (!data.success) {
                    this.options.idToken = data.id_token;
                    done(data);
                }

                this.options.params = function (files, xhr, chunk) {
                    if (chunk) {
                        return {
                            dzuuid: chunk.file.upload.uuid,
                            dzchunkindex: chunk.index,
                            dztotalfilesize: chunk.file.size,
                            dzchunksize: this.options.chunkSize,
                            dztotalchunkcount: chunk.file.upload.totalChunkCount,
                            dzchunkbyteoffset: chunk.index * this.options.chunkSize,
                            upload_token: data.upload_token
                        };
                    } else {
                        return {
                            upload_token: data.upload_token
                        }
                    }
                }
                this.options.idToken = data.id_token;
                done()
            });
        },
        success: function (file, response) {
            if (imgElement = this.element.querySelector('div.dz-image.image-transmorpher > img')) {
                imgElement.src = imgElement.dataset.deliveryUrl + '/' + response.public_path + '/' + '{{ $transmorpher->getTransformations(['height' => 150]) }}' + '?v=' + response.version;
            }

            handleUploadResponse(file, response, '{{ route('transmorpherHandleUploadResponse') }}', this.options.idToken, {{ $transmorpher->getTransmorpherMedia()->getKey() }}, '{{$transmorpher->getIdentifier()}}')
        },
        error: function (file, response) {
            handleUploadResponse(file, response, '{{ route('transmorpherHandleUploadResponse') }}', this.options.idToken, {{ $transmorpher->getTransmorpherMedia()->getKey() }}, '{{$transmorpher->getIdentifier()}}')
        },
    });
</script>
