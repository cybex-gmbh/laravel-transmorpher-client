import {clearStatusPolling, getMedium, setStatusPolling} from './state.js';
import {abortUpload, deleteTransmorpherMedia as deleteTransmorpherMediaRequest, getState} from './api.js';
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
} from './ui.js';
import {addConfirmEventListener, getDateForDisplay} from './utils.js';
import {createDropzone} from './dropzone.js';
import {updateVersionInformation} from './versions.js';

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
