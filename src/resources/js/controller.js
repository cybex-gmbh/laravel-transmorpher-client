import Dropzone from 'dropzone';
import {clearStatusPolling, getMedium, MEDIA_TYPE, setStatusPolling, state} from './state.js';
import {
    abortUpload,
    completeUpload,
    deleteTransmorpherMedia as deleteTransmorpherMediaRequest,
    getState,
    getUploadUrl,
    getVersions,
    reserveUploadSlot as reserveUploadSlotRequest,
    setUploadingState,
    setVersion as setVersionRequest,
    storeUploadResponse,
} from './api.js';
import {
    closeUploadConfirmModalDisplay,
    displayCardBorderState,
    displayModalState,
    displayPlaceholder,
    displayState,
    openMoreInformationModalDisplay,
    resetAgeElement,
    setAgeElement,
    updateMediaDisplay,
    updateThumbnail,
    updateVideoDisplay,
} from './ui.js';
import {addConfirmEventListener, getDateForDisplay, getMediaDimensions} from './utils.js';

export function setupComponent({transmorpherIdentifier}) {
    Dropzone.autoDiscover = false;
    const medium = getMedium({transmorpherIdentifier});

    addConfirmEventListener({
        button: document.querySelector(`#modal-mi-${transmorpherIdentifier} .confirm-delete`),
        callback: () => deleteTransmorpherMedia({transmorpherIdentifier}),
        transmorpherIdentifier,
    });

    // Start polling if the video is still processing or an upload is in process.
    if (medium.isProcessing || medium.isUploading) {
        startPolling({transmorpherIdentifier, uploadToken: medium.latestUploadToken});
        setAgeElement({
            ageElement: document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`),
            dateTime: getDateForDisplay({date: new Date(medium.lastUpdated * 1000)}),
        });
    }

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

function startPolling({transmorpherIdentifier, uploadToken}) {
    const expirationTime = new Date();
    expirationTime.setDate(expirationTime.getDate() + 1);

    const intervalId = setInterval(async () => {
        if (new Date().getTime() > expirationTime.getTime()) {
            clearStatusPolling({transmorpherIdentifier});

            return;
        }

        const pollingInformation = await getState({transmorpherIdentifier, uploadToken});

        switch (pollingInformation.state) {
            case 'success': {
                clearStatusPolling({transmorpherIdentifier});
                displayState({transmorpherIdentifier, stateName: 'success'});
                resetAgeElement({transmorpherIdentifier});
                updateMediaDisplay({transmorpherIdentifier, thumbnailUrl: pollingInformation.thumbnailUrl, fullsizeUrl: pollingInformation.fullsizeUrl});

                await updateVersionInformation({transmorpherIdentifier});
                break;
            }
            case 'error': {
                clearStatusPolling({transmorpherIdentifier});

                if (uploadToken !== pollingInformation.latestUploadToken) {
                    startPolling({transmorpherIdentifier, uploadToken: pollingInformation.latestUploadToken});
                }

                displayState({transmorpherIdentifier, stateName: 'error', message: pollingInformation.clientMessage});
                resetAgeElement({transmorpherIdentifier});
                break;
            }
            case 'uploading': {
                displayState({transmorpherIdentifier, stateName: 'uploading', resetError: false});
                setAgeElement({
                    ageElement: document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`),
                    dateTime: getDateForDisplay({date: new Date(pollingInformation.lastUpdated)}),
                });
                break;
            }
            case 'processing': {
                displayState({transmorpherIdentifier, stateName: 'processing', resetError: false});
                setAgeElement({
                    ageElement: document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`),
                    dateTime: getDateForDisplay({date: new Date(pollingInformation.lastUpdated)}),
                });
                break;
            }
        }
    }, 5000);

    setStatusPolling({transmorpherIdentifier, intervalId});
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
            case state.mediaTypes[MEDIA_TYPE.IMAGE]:
            case state.mediaTypes[MEDIA_TYPE.DOCUMENT]:
                updateThumbnail({transmorpherIdentifier, thumbnailUrl: uploadResult.thumbnailUrl, fullSizeUrl: uploadResult.fullsizeUrl});
                break;
            case state.mediaTypes[MEDIA_TYPE.VIDEO]:
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

async function updateVersionInformation({transmorpherIdentifier}) {
    const modal = document.querySelector(`#modal-mi-${transmorpherIdentifier}`);

    // Don't update when the modal is closed or currently fetching.
    if (!modal.classList.contains('d-flex') || modal.dataset.fetching === 'true') {
        return;
    }

    modal.dataset.fetching = 'true';

    const versionList = modal.querySelector('.version-list > ul');
    const defaultVersionEntry = versionList.querySelector('.version-entry').cloneNode(true);

    // Clear list while preserving one cloneable template entry.
    versionList.replaceChildren();
    versionList.append(defaultVersionEntry);

    try {
        const versionInformation = await getVersions({transmorpherIdentifier});

        if (!versionInformation.currentVersion) {
            displayPlaceholder({transmorpherIdentifier});
            document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.add('d-hidden');

            return;
        }

        document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.remove('d-hidden');

        const stateResponse = await getState({transmorpherIdentifier});

        if (stateResponse.state === 'uploading' || stateResponse.state === 'processing') {
            clearStatusPolling({transmorpherIdentifier});
            displayState({transmorpherIdentifier, stateName: stateResponse.state});
            startPolling({transmorpherIdentifier, uploadToken: stateResponse.latestUploadToken});
        }

        const medium = getMedium({transmorpherIdentifier});
        const versions = versionInformation.state === 'success' ? versionInformation.versions : [];

        let versionAge;

        switch (medium.mediaType) {
            case state.mediaTypes[MEDIA_TYPE.IMAGE]:
            case state.mediaTypes[MEDIA_TYPE.DOCUMENT]:
                versionAge = getDateForDisplay({date: new Date(versions[versionInformation.currentVersion] * 1000)});
                updateThumbnail({transmorpherIdentifier, thumbnailUrl: versionInformation.thumbnailUrl, fullSizeUrl: versionInformation.fullsizeUrl});
                break;
            case state.mediaTypes[MEDIA_TYPE.VIDEO]:
                versionAge = getDateForDisplay({date: new Date(versions[versionInformation.currentlyProcessedVersion] * 1000)});

                if (versionInformation.currentlyProcessedVersion) {
                    updateVideoDisplay({transmorpherIdentifier, thumbnailUrl: versionInformation.thumbnailUrl});
                }
                break;
        }

        const currentVersionAgeElement = modal.querySelector('.current-version-age');
        currentVersionAgeElement.textContent = versionAge;
        currentVersionAgeElement.classList.remove('d-none');

        Object.keys(versions)
            .sort((a, b) => versions[b] - versions[a])
            .forEach(version => {
                // Don't show the currently processed or current version.
                if (version === String(versionInformation.currentlyProcessedVersion) || version === String(versionInformation.currentVersion)) {
                    return;
                }

                const versionEntry = defaultVersionEntry.cloneNode(true);
                const versionAgeElement = versionEntry.querySelector('.version-age');

                switch (medium.mediaType) {
                    case state.mediaTypes[MEDIA_TYPE.IMAGE]:
                    case state.mediaTypes[MEDIA_TYPE.DOCUMENT]: {
                        const transformations = medium.transformations;

                        versionEntry.querySelector('a').href = medium.routes.getDerivativeForVersion
                            .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
                            .replace('{version}', version)
                            .replace('{transformations?}', '');
                        versionEntry.querySelector('.dz-image img:first-of-type').src = medium.routes.getDerivativeForVersion
                            .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
                            .replace('{version}', version)
                            .replace('{transformations?}', transformations['150w']);
                        versionEntry.querySelector('.dz-image img:first-of-type').srcset = `${medium.routes.getDerivativeForVersion
                            .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
                            .replace('{version}', version)
                            .replace('{transformations?}', transformations['150w'])} 150w`;
                        break;
                    }
                    case state.mediaTypes[MEDIA_TYPE.VIDEO]:
                        // Don't show video for now, will use thumbnails later.
                        versionEntry.querySelector('.media-preview').remove();
                        break;
                }

                addConfirmEventListener({
                    button: versionEntry.querySelector('button'),
                    callback: () => setVersionForMedia({transmorpherIdentifier, version}),
                    transmorpherIdentifier,
                });
                versionAgeElement.textContent = getDateForDisplay({date: new Date(versions[version] * 1000)});

                versionList.append(versionEntry);
                versionEntry.classList.remove('d-none');
            });
    } finally {
        modal.dataset.fetching = 'false';
    }
}

async function setVersionForMedia({transmorpherIdentifier, version}) {
    const uploadingStateResponse = await getState({transmorpherIdentifier});

    if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
        openUploadConfirmModal({
            transmorpherIdentifier,
            callback: () => makeSetVersionCall({transmorpherIdentifier, version})
        });

        return;
    }

    await makeSetVersionCall({transmorpherIdentifier, version});
}

async function makeSetVersionCall({transmorpherIdentifier, version}) {
    const medium = getMedium({transmorpherIdentifier});
    const setVersionResult = await setVersionRequest({transmorpherIdentifier, version});

    if (setVersionResult.state !== 'error') {
        clearStatusPolling({transmorpherIdentifier});

        await updateVersionInformation({transmorpherIdentifier});

        switch (medium.mediaType) {
            case state.mediaTypes[MEDIA_TYPE.IMAGE]:
            case state.mediaTypes[MEDIA_TYPE.DOCUMENT]:
                updateMediaDisplay({transmorpherIdentifier, thumbnailUrl: setVersionResult.thumbnailUrl, fullsizeUrl: setVersionResult.fullsizeUrl});
                break;
            case state.mediaTypes[MEDIA_TYPE.VIDEO]:
                startPolling({transmorpherIdentifier, uploadToken: setVersionResult.upload_token});
                break;
        }

        displayState({transmorpherIdentifier, stateName: setVersionResult.state});

        return;
    }

    clearStatusPolling({transmorpherIdentifier});
    displayModalState({transmorpherIdentifier, stateName: setVersionResult.state, message: setVersionResult.clientMessage});
}

export function openMoreInformationModal({transmorpherIdentifier}) {
    openMoreInformationModalDisplay({transmorpherIdentifier});
    updateVersionInformation({transmorpherIdentifier});
}

async function deleteTransmorpherMedia({transmorpherIdentifier}) {
    const deleteResult = await deleteTransmorpherMediaRequest({transmorpherIdentifier});

    if (deleteResult.state !== 'error') {
        clearStatusPolling({transmorpherIdentifier});
        displayModalState({transmorpherIdentifier, stateName: 'success'});
        displayCardBorderState({transmorpherIdentifier, stateName: 'processing'});

        await updateVersionInformation({transmorpherIdentifier});

        displayPlaceholder({transmorpherIdentifier});

        document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge').classList.add('d-hidden');
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.add('d-hidden');

        return;
    }

    clearStatusPolling({transmorpherIdentifier});
    displayModalState({transmorpherIdentifier, stateName: deleteResult.state, message: deleteResult.clientMessage});
}

function openUploadConfirmModal({transmorpherIdentifier, callback}) {
    const modal = document.querySelector(`#modal-uc-${transmorpherIdentifier}`);
    const dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
    const previewElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview ~ .dz-preview`);

    modal.classList.add('d-flex');

    if (previewElement) {
        previewElement.style.display = 'none';
    }

    modal.querySelector('.badge-error').onclick = async function () {
        if (previewElement) {
            previewElement.style.display = 'block';
        }

        document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.remove('d-flex');

        // If there is an upload in progress, remove it.
        if (dropzone.files[0]?.status === 'uploading') {
            // If a version was restored, show the default message.
            if (!dropzone.files[1]) {
                document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';
            }

            dropzone.removeFile(dropzone.files[0]);
        } else if (dropzone.files[0]) {
            // If the file is not uploading, the overwrite button was clicked after finishing upload.
            document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'none';

            if (previewElement) {
                previewElement.style.display = 'block';
            }
        }

        await abortUpload({transmorpherIdentifier});
        callback();
    };
}

export async function closeUploadConfirmModal({transmorpherIdentifier}) {
    closeUploadConfirmModalDisplay({transmorpherIdentifier});

    const stateResponse = await getState({transmorpherIdentifier});

    clearStatusPolling({transmorpherIdentifier});
    displayState({transmorpherIdentifier, stateName: stateResponse.state});
    startPolling({transmorpherIdentifier, uploadToken: stateResponse.latestUploadToken});
}

