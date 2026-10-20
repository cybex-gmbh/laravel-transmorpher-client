import {clearStatusPolling, getMedium, MEDIA_TYPE, setStatusPolling} from './state.js';
import {
    abortUpload,
    deleteTransmorpherMedia as deleteTransmorpherMediaRequest,
    getState,
    getVersions,
    setVersion as setVersionRequest,
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
import {addConfirmEventListener, getDateForDisplay} from './utils.js';
import {createDropzone} from './dropzone.js';

export function setupComponent({transmorpherIdentifier}) {
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

    createDropzone({transmorpherIdentifier});
}

export function startPolling({transmorpherIdentifier, uploadToken}) {
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

export async function updateVersionInformation({transmorpherIdentifier}) {
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
            case MEDIA_TYPE.IMAGE:
            case MEDIA_TYPE.DOCUMENT:
                versionAge = getDateForDisplay({date: new Date(versions[versionInformation.currentVersion] * 1000)});
                updateThumbnail({transmorpherIdentifier, thumbnailUrl: versionInformation.thumbnailUrl, fullsizeUrl: versionInformation.fullsizeUrl});
                break;
            case MEDIA_TYPE.VIDEO:
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
                    case MEDIA_TYPE.IMAGE:
                    case MEDIA_TYPE.DOCUMENT: {
                        const transformations = medium.transformations;
                        const fullSizeLink = versionEntry.querySelector('a.full-size-link');
                        const enlargeIcon = versionEntry.querySelector('.enlarge-icon');

                        fullSizeLink.href = medium.routes.getDerivativeForVersion
                            .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
                            .replace('{version}', version)
                            .replace('{transformations?}', '');
                        fullSizeLink.classList.remove('disabled');
                        enlargeIcon.classList.remove('d-hidden');

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
                    case MEDIA_TYPE.VIDEO:
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
            case MEDIA_TYPE.IMAGE:
            case MEDIA_TYPE.DOCUMENT:
                updateMediaDisplay({transmorpherIdentifier, thumbnailUrl: setVersionResult.thumbnailUrl, fullsizeUrl: setVersionResult.fullsizeUrl});
                break;
            case MEDIA_TYPE.VIDEO:
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

export function openUploadConfirmModal({transmorpherIdentifier, callback}) {
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
