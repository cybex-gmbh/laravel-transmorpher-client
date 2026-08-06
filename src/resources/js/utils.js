import {MEDIA_TYPE, state} from './state.js';

export function getCsrfToken() {
    // Cookie is encoded in base64 and '=' will be URL encoded, therefore we need to decode it.
    return decodeURIComponent(document.cookie
        .split('; ')
        .find(cookie => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1]
    );
}

export function addConfirmEventListener({button, callback, transmorpherIdentifier}) {
    let pressedOnce = false;
    const buttonText = button.textContent;
    let timeOut;

    button.addEventListener('pointerdown', () => {
        if (pressedOnce) {
            callback();

            button.querySelector('span').textContent = buttonText;
            pressedOnce = false;
            clearTimeout(timeOut);

            return;
        }

        button.querySelector('span').textContent = state.media[transmorpherIdentifier].translations.press_again_to_confirm;

        pressedOnce = true;

        timeOut = setTimeout(() => {
            pressedOnce = false;
            button.querySelector('span').textContent = buttonText;
        }, 3000);
    });
}

export function getDateForDisplay({date}) {
    if (isNaN(date)) {
        return '';
    }

    return date.toLocaleString();
}

export function getMediaDimensions({file, mediaType, validationError}) {
    switch (mediaType) {
        case state.mediaTypes[MEDIA_TYPE.IMAGE]:
            return getImageDimensions({file, validationError});
        case state.mediaTypes[MEDIA_TYPE.DOCUMENT]:
            return Promise.resolve({width: null, height: null});
        case state.mediaTypes[MEDIA_TYPE.VIDEO]:
            return getVideoDimensions({file, validationError});
        default:
            return Promise.resolve({width: null, height: null});
    }
}

function getImageDimensions({file, validationError}) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.src = URL.createObjectURL(file);

        image.onload = function () {
            URL.revokeObjectURL(this.src);

            resolve({
                width: this.width,
                height: this.height,
            });
        };

        image.onerror = function () {
            reject(validationError);
        };
    });
}

function getVideoDimensions({file, validationError}) {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);

        video.onloadedmetadata = function () {
            URL.revokeObjectURL(this.src);
            resolve({
                width: this.videoWidth,
                height: this.videoHeight,
            });
        };

        video.onerror = function () {
            reject(validationError);
        };
    });
}

