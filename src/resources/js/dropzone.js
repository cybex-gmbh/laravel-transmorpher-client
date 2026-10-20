import Dropzone from 'dropzone';
import {clearStatusPolling, getMedium, MEDIA_TYPE, state} from './state.js';
import {
    completeUpload,
    getState,
    getUploadUrl,
    reserveUploadSlot as reserveUploadSlotRequest,
    setUploadingState,
    storeUploadResponse,
} from './api.js';
import {displayState, resetAgeElement, updateThumbnail} from './ui.js';
import {getMediaDimensions} from './utils.js';
import {openUploadConfirmModal, startPolling, updateVersionInformation} from './controller.js';

export function createDropzone({transmorpherIdentifier}) {
    Dropzone.autoDiscover = false;
    const medium = getMedium({transmorpherIdentifier});

    const dz = new Dropzone(`#dz-${transmorpherIdentifier}`, {
        url: 'placeholder', // URL is set dynamically for each chunk.
        method: 'PUT',
        acceptedFiles: medium.acceptedFileTypes,
        chunking: true,
        forceChunking: true,
        chunkSize: medium.chunkSize,
        maxFilesize: medium.maxFilesize,
        maxThumbnailFilesize: medium.maxThumbnailFilesize,
        timeout: 60000,
        uploadMultiple: false,
        paramName: 'file',
        uploadToken: null,
        dictDefaultMessage: medium.translations.drop_files_to_upload,
        dictFileTooBig: medium.translations.max_file_size_exceeded,
        dictInvalidFileType: medium.translations.invalid_file_type,
        createImageThumbnails: false,
        ...state.uploadHandler.getDropzoneOptions({transmorpherMedium: medium}),
        init: function () {
            this.on('processing', async function () {
                await setUploadingState({transmorpherIdentifier, uploadToken: this.options.uploadToken});

                clearStatusPolling({transmorpherIdentifier});
                displayState({transmorpherIdentifier, stateName: 'uploading', resetError: false});
                startPolling({transmorpherIdentifier, uploadToken: this.options.uploadToken});
            });

            this.on('sending', function (file, xhr, formData) {
                // Add identifier to request body.
                formData?.append('identifier', transmorpherIdentifier);
            });
        },
        thumbnail: async function (file) {
            // Dropzone sometimes (small files) manages to calculate width and height, if not, we have to calculate it ourselves.
            if (!file.width || !file.height) {
                try {
                    const dimensions = await getMediaDimensions({file, mediaType: medium.mediaType, validationError: medium.translations.validation_error});
                    file.width = dimensions.width;
                    file.height = dimensions.height;
                } catch (error) {
                    file.done(error);
                    return;
                }
            }

            if ((medium.maxWidth && file.width > medium.maxWidth) || (medium.maxHeight && file.height > medium.maxHeight)) {
                file.done(medium.translations.max_dimensions_exceeded);

                return;
            }

            if ((medium.minWidth && file.width < medium.minWidth) || (medium.minHeight && file.height < medium.minHeight)) {
                file.done(medium.translations.min_dimensions_subceeded);

                return;
            }

            // Since testing floating point values for equality is problematic, we define an upper bound on the rounding error.
            if (medium.ratio && Math.abs(file.width / file.height - medium.ratio) > 0.0000000001) {
                file.done(medium.translations.invalid_ratio);

                return;
            }

            const uploadingStateResponse = await getState({transmorpherIdentifier});

            if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
                openUploadConfirmModal({
                    transmorpherIdentifier,
                    callback: () => reserveUploadSlot({transmorpherIdentifier, done: file.done})
                });

                return;
            }

            await reserveUploadSlot({transmorpherIdentifier, done: file.done});
        },
        accept: function (file, done) {
            file.done = done;

            // Remove previous elements to maintain a clean overlay.
            this.element.querySelector('.dz-default').style.display = 'none';
            const errorElement = this.element.querySelector('.dz-error');

            if (errorElement) {
                errorElement.remove();
            }

            this.emit('thumbnail', file);
        },
        canceled: async function (file) {
            await storeUploadResponse({
                transmorpherIdentifier,
                uploadToken: this.options.uploadToken,
                response: {
                    state: 'error',
                    clientMessage: medium.translations.upload_canceled,
                    message: this.options.dictUploadCanceled,
                },
                httpCode: file.xhr?.status,
            });
        },
        success: async function (file) {
            this.element.classList.add('is-completing-upload');

            const completeUploadResponse = await completeUpload({transmorpherIdentifier, uploadToken: this.options.uploadToken});
            await handleUploadResponse({file, response: completeUploadResponse, transmorpherIdentifier, uploadToken: this.options.uploadToken});

            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.classList.remove('is-completing-upload');
        },
        error: function (file, response) {
            handleUploadResponse({file, response, transmorpherIdentifier, uploadToken: this.options.uploadToken});
        },
    });

    const originalSubmitRequest = dz.submitRequest.bind(dz);

    // Overwrite Dropzone submitRequest to set a dynamic URL for each chunk.
    dz.submitRequest = async function (xhr, formData, files) {
        const file = files?.[0];
        const chunk = file?.upload?.chunks?.find(candidateChunk => candidateChunk.xhr === xhr);
        const chunkIndex = (chunk?.dataBlock?.chunkIndex ?? 0) + 1;

        const chunkUploadUrl = await getUploadUrl({transmorpherIdentifier, chunkIndex, uploadToken: this.options.uploadToken, done: file?.done});

        if (!chunkUploadUrl) {
            return;
        }

        xhr.open(this.options.method, chunkUploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');

        return originalSubmitRequest(xhr, formData, files);
    };

    return dz;
}

async function reserveUploadSlot({transmorpherIdentifier, done}) {
    const dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
    const getUploadTokenResult = await reserveUploadSlotRequest({transmorpherIdentifier, filename: dropzone.files[0].name});

    if (getUploadTokenResult.state === 'error') {
        done(getUploadTokenResult);

        return;
    }

    dropzone.options.uploadToken = getUploadTokenResult.upload_token;

    done();
}

async function handleUploadResponse({file, response, transmorpherIdentifier, uploadToken}) {
    clearStatusPolling({transmorpherIdentifier});

    let uploadResult = response;

    if (uploadToken) {
        uploadResult = await storeUploadResponse({
            transmorpherIdentifier,
            uploadToken,
            response,
            httpCode: response?.httpCode ?? file.xhr?.status,
        });
    }

    await displayUploadResult({uploadResult, transmorpherIdentifier, uploadToken});

    // Remove the uploaded file to reset the state.
    const dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;

    if (dropzone.files[0]) {
        dropzone.removeFile(dropzone.files[0]);
    }
}

async function displayUploadResult({uploadResult, transmorpherIdentifier, uploadToken}) {
    resetAgeElement({transmorpherIdentifier});

    // Check for undefined, which happens when dropzone directly rejects the file.
    if (uploadResult.state !== undefined && uploadResult.state !== 'error') {
        document.querySelector(`#dz-${transmorpherIdentifier}`).classList.remove('dz-started');
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.remove('d-hidden');

        await updateVersionInformation({transmorpherIdentifier});

        switch (getMedium({transmorpherIdentifier}).mediaType) {
            case MEDIA_TYPE.IMAGE:
            case MEDIA_TYPE.DOCUMENT:
                updateThumbnail({transmorpherIdentifier, thumbnailUrl: uploadResult.thumbnailUrl, fullsizeUrl: uploadResult.fullsizeUrl});
                break;
            case MEDIA_TYPE.VIDEO:
                startPolling({transmorpherIdentifier, uploadToken});
                break;
        }

        displayState({transmorpherIdentifier, stateName: uploadResult.state});
    } else {
        displayState({transmorpherIdentifier, stateName: 'error', message: uploadResult.clientMessage ?? uploadResult});

        // Start polling for updates when the upload was aborted due to another upload.
        if (uploadResult.httpCode === 404) {
            clearStatusPolling({transmorpherIdentifier});
            startPolling({transmorpherIdentifier, uploadToken: uploadResult.latestUploadToken});
            displayState({transmorpherIdentifier, stateName: 'uploading'});
        }
    }

    document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone.options.uploadToken = null;
}
