import {clearStatusPolling, getMedium, MEDIA_TYPE} from './state.js';
import {getState, getVersions, setVersion as setVersionRequest} from './api.js';
import {displayModalState, displayPlaceholder, displayState, updateMediaDisplay, updateThumbnail, updateVideoDisplay} from './ui.js';
import {addConfirmEventListener, getDateForDisplay} from './utils.js';
import {openUploadConfirmModal, startPolling} from './controller.js';

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

