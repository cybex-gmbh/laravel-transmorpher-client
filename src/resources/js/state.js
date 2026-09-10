import UploadHandlerFactory from './classes/UploadHandlerFactory.js';

export const MEDIA_TYPE = {
    IMAGE: 'image',
    DOCUMENT: 'document',
    VIDEO: 'video',
};

export const state = {
    media: {},
    uploadHandler: null,
    statusPolling: {},
};


export function setUploadHandler({uploadHandler}) {
    state.uploadHandler = UploadHandlerFactory.create(uploadHandler);
}

export function registerMedium({transmorpherIdentifier, medium}) {
    state.media[transmorpherIdentifier] = medium;
}

export function getMedium({transmorpherIdentifier}) {
    return state.media[transmorpherIdentifier];
}

export function setStatusPolling({transmorpherIdentifier, intervalId}) {
    state.statusPolling[transmorpherIdentifier] = intervalId;
}

export function clearStatusPolling({transmorpherIdentifier}) {
    clearInterval(state.statusPolling[transmorpherIdentifier]);

    delete state.statusPolling[transmorpherIdentifier];
}
