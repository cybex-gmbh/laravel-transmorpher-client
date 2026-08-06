import {getMedium, MEDIA_TYPE} from './state.js';

export function setAgeElement({ageElement, dateTime}) {
    ageElement.textContent = dateTime;
    ageElement.closest('p').classList.remove('d-none');
}

export function resetAgeElement({transmorpherIdentifier}) {
    document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`)?.closest('p')?.classList.add('d-none');
}

export function displayState({transmorpherIdentifier, stateName, message = null, resetError = true}) {
    displayDropzoneState({transmorpherIdentifier, stateName, message, resetError});
    displayModalState({transmorpherIdentifier, stateName, message, resetError});
}

export function displayDropzoneState({transmorpherIdentifier, stateName, message = null, resetError = true}) {
    const stateInfo = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge');

    displayCardBorderState({transmorpherIdentifier, stateName});
    displayStateInformation({stateInfoElement: stateInfo, stateName, transmorpherIdentifier});

    if (message) {
        displayDropzoneErrorMessage({transmorpherIdentifier, message});

        return;
    }

    if (resetError) {
        resetModalErrorMessageDisplay({transmorpherIdentifier});
    }
}

export function displayModalState({transmorpherIdentifier, stateName, message = null, resetError = true}) {
    displayStateInformation({
        stateInfoElement: document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .badge`),
        stateName,
        transmorpherIdentifier,
    });

    if (message) {
        setModalErrorMessage({transmorpherIdentifier, message});

        return;
    }

    if (resetError) {
        resetModalErrorMessageDisplay({transmorpherIdentifier});
    }
}

export function displayStateInformation({stateInfoElement, stateName, transmorpherIdentifier}) {
    stateInfoElement.className = '';
    stateInfoElement.classList.add('badge', `badge-${stateName}`);
    stateInfoElement.querySelector('span:first-of-type').textContent = getMedium({transmorpherIdentifier}).translations[stateName];
}

export function displayCardBorderState({transmorpherIdentifier, stateName}) {
    const card = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card');

    card.className = '';
    card.classList.add('card', `border-${stateName}`);
}

export function displayDropzoneErrorMessage({transmorpherIdentifier, message}) {
    const form = document.querySelector(`#dz-${transmorpherIdentifier}`);
    const errorDisplay = form.querySelector('.error-display');

    errorDisplay.classList.remove('d-none');
    errorDisplay.querySelector('.error-message').textContent = message;
    form.querySelector('.dz-default').style.display = 'block';
}

export function setModalErrorMessage({transmorpherIdentifier, message}) {
    document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = message;
}

export function resetModalErrorMessageDisplay({transmorpherIdentifier}) {
    document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = '';
}

export function updateMediaDisplay({transmorpherIdentifier, thumbnailUrl, fullsizeUrl}) {
    switch (getMedium({transmorpherIdentifier}).mediaType) {
        case MEDIA_TYPE.IMAGE:
        case MEDIA_TYPE.DOCUMENT:
            updateThumbnail({transmorpherIdentifier, thumbnailUrl, fullSizeUrl: fullsizeUrl});
            break;
        case MEDIA_TYPE.VIDEO:
            updateVideoDisplay({transmorpherIdentifier, thumbnailUrl});
            break;
    }
}

export function updateThumbnail({transmorpherIdentifier, thumbnailUrl, fullSizeUrl}) {
    const imageElements = getPrimaryPreviewImages({transmorpherIdentifier});

    imageElements.forEach(image => {
        image.src = thumbnailUrl;
        image.srcset = getSrcSetString({transmorpherIdentifier, imageUrl: thumbnailUrl});

        const aTag = image.closest('.full-size-link');
        aTag.href = fullSizeUrl;
        aTag.classList.remove('disabled');

        // Show enlarge icon.
        image.nextElementSibling.classList.remove('d-hidden');
    });
}

function getPrimaryPreviewImages({transmorpherIdentifier}) {
    return document.querySelectorAll([
        `#dz-${transmorpherIdentifier} .media-preview .dz-image > img:first-of-type`,
        `#modal-mi-${transmorpherIdentifier} .card-side .media-preview .dz-image > img:first-of-type`,
    ].join(', '));
}

export function getSrcSetString({transmorpherIdentifier, imageUrl}) {
    const srcStrings = [];
    const transformations = getMedium({transmorpherIdentifier}).transformations;

    Object.keys(transformations).forEach(key => {
        const modifiedUrl = imageUrl.replace(/(\/).-.+(\?)/i, `$1${transformations[key]}$2`);

        srcStrings.push(`${modifiedUrl} ${key}`);
    });

    return srcStrings.join(', ');
}

export function updateVideoDisplay({transmorpherIdentifier, thumbnailUrl}) {
    const videoElements = document.querySelectorAll(`#component-${transmorpherIdentifier} video.video-transmorpher`);

    videoElements.forEach(video => {
        video.src = thumbnailUrl;
        video.querySelector('a').href = thumbnailUrl;
        video.classList.remove('d-none');
    });

    // Hide placeholder images.
    document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`)
        .forEach(placeholder => placeholder.classList.add('d-none'));
}

export function displayPlaceholder({transmorpherIdentifier}) {
    let imageElements;

    switch (getMedium({transmorpherIdentifier}).mediaType) {
        case MEDIA_TYPE.IMAGE:
        case MEDIA_TYPE.DOCUMENT:
            imageElements = getPrimaryPreviewImages({transmorpherIdentifier});
            imageElements.forEach(image => {
                const aTag = image.closest('.full-size-link');

                aTag.href = image.dataset.placeholderUrl;
                aTag.classList.add('disabled');

                // Hide enlarge icon.
                image.nextElementSibling.classList.add('d-hidden');
            });
            break;
        case MEDIA_TYPE.VIDEO:
            imageElements = document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`);

            document.querySelectorAll(`#component-${transmorpherIdentifier} video.video-transmorpher`)
                .forEach(video => video.classList.add('d-none'));
            break;
    }

    imageElements.forEach(image => {
        image.src = image.dataset.placeholderUrl;
        image.srcset = '';
        image.classList.remove('d-none');
    });

    document.querySelector(`#modal-mi-${transmorpherIdentifier} .current-version-age`).classList.add('d-none');
}

export function closeMoreInformationModal({transmorpherIdentifier}) {
    document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.remove('d-flex');
}

export function openMoreInformationModalDisplay({transmorpherIdentifier}) {
    document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.add('d-flex');
}

export function closeUploadConfirmModalDisplay({transmorpherIdentifier}) {
    document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.remove('d-flex');
    document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview ~ .dz-preview`)?.remove();

    if (document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview:not(.dz-processing)`)) {
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview`).remove();
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';
    }
}

export function closeErrorMessage({closeButton, transmorpherIdentifier}) {
    closeButton.closest('.error-display').classList.add('d-none');

    // Reset errors.
    resetModalErrorMessageDisplay({transmorpherIdentifier});
    document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .badge.badge-error`)?.classList.add('d-none');
    closeButton.closest('.card').querySelector('.badge.badge-error')?.classList.add('d-hidden');
    closeButton.closest('.card').classList.remove('border-error');
}

