<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css"/>

<form method="POST" id="testvideo" class="dropzone" action="http://transmorpher.test/api/video/upload"/>

</form>

<script type="text/javascript">
    Dropzone.options.testvideo = {
        url: 'http://transmorpher.test/api/video/upload',
        maxFilesize: 80, // MB
        maxThumbnailFilesize: 80,
        timeout: 60000, // ms
        uploadMultiple: false,
        paramName: 'video',
        headers: {
            'Authorization': 'Bearer 2|ruWCzoepgETQOQNWwbHIKcQbODtjtoA6NuISQzy4'
        },
        params: {
            upload_token: '643e48448c1b4'
        },
        success: function () {
            alert('worked');
        },
        error: function () {
            alert('failed');
        }
    }
</script>