<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css"/>

<style>
    .card {
        display: flex;
        flex-direction: column;
        width: 200px;
        padding: 10px;
        border: black 1px solid;
        border-radius: 15px;
    }

    .card-header {
        display: flex;
        justify-content: center;
    }

    .dz-message {
        background-color: red;
        border: 1px dotted grey;
        margin: 0;
        padding: 1rem;
    }

    .dz-preview.dz-image-preview {
        background: transparent;
    }
</style>

<div class="card">
    <div class="card-header">
        {{ $imageTransmorpher->getTransmorpherMedia()->differentiator }}
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $imageTransmorpher->getIdentifier() }}" action="{{ $imageTransmorpher->getApiUrl('image/upload') }}">
            @csrf
            <div class="dz-image">
{{--                <img src="{{ $imageTransmorpher->getUrl(['width' => 400, 'height' => 400]) }}" />--}}
                <img src="{{ 'http://transmorpher.test/Marco/' . $imageTransmorpher->getIdentifier() . '/w-400+h-400' }}" />
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    new Dropzone("#{{$imageTransmorpher->getIdentifier()}}", {
        {{--url: '{{ $imageTransmorpher->getApiUrl('image/upload') }}',--}}
        url: 'http://transmorpher.test/api/image/upload',
        chunking: true,
        chunkSize: 1000000, // 1MB
        // retryChunks: true, // default: 3 retries
        parallelChunkUploads: true,
        maxFilesize: 100, // MB
        maxThumbnailFilesize: 100,
        timeout: 60000, // ms
        uploadMultiple: false,
        paramName: 'image',
        idToken: null,
        init: function() {
            this.on("addedfile", function() {
                if (this.files[1]!=null){
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            fetch('{{ route('transmorpherImageToken') }}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": document.querySelector("#{{$imageTransmorpher->getIdentifier()}} > input[name='_token']").value,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $imageTransmorpher->getTransmorpherMedia()->getKey() }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
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
        chunksUploaded: function (file, done) {
            done();
        },
        success: function (file, response) {
            handleImageUploadResponse(file, response, this.options.idToken)
        },
        error: function (file, response) {
            handleImageUploadResponse(file, response, this.options.idToken)
        },
    });

    function handleImageUploadResponse(file, response, idToken) {
        fetch('{{ route('transmorpherHandleUploadResponse') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": document.querySelector("#{{$imageTransmorpher->getIdentifier()}} > input[name='_token']").value,
            },
            body: JSON.stringify({
                transmorpher_media_key: {{ $imageTransmorpher->getTransmorpherMedia()->getKey() }},
                id_token: idToken,
                response: response
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            handleDropzoneResult(data);
        });
    }

    function handleDropzoneResult(data) {
        console.log(data);
        if (!data.success) {
            form = document.querySelector("#{{ $imageTransmorpher->getIdentifier() }}");
            errorMessage = form.querySelector('.dz-error-message')
            form.querySelector('.dz-preview').classList.add('dz-error');

            errorMessage.append('<p>'+data.response+'</p>');
            console.log(errorMessage);
        }
    }
</script>