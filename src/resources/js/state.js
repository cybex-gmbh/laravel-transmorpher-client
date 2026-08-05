export const MEDIA_TYPE = {
    IMAGE: 'IMAGE',
    DOCUMENT: 'DOCUMENT',
    VIDEO: 'VIDEO',
};

export const state = {
    mediaTypes: {},
    media: {},
    uploadHandler: '',
    statusPolling: {},
};

export function getMedium(transmorpherIdentifier) {
    return state.media[transmorpherIdentifier];
}

export function setStatusPolling(transmorpherIdentifier, intervalId) {
    state.statusPolling[transmorpherIdentifier] = intervalId;
}

export function clearStatusPolling(transmorpherIdentifier) {
    clearInterval(state.statusPolling[transmorpherIdentifier]);

    delete state.statusPolling[transmorpherIdentifier];
}

