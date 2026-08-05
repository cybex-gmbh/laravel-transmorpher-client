import {getMedium} from './state.js';
import {getCsrfToken} from './utils.js';

function withDefaultHeaders(options = {}) {
    return {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            ...(options.headers ?? {}),
        },
    };
}

async function request(method, url, options = {}) {
    return fetch(url, withDefaultHeaders({
        ...options,
        method,
    }));
}

async function requestJson(method, url, options = {}) {
    const response = await request(method, url, options);

    return response.json();
}

export async function setUploadingState(transmorpherIdentifier, uploadToken) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.setUploadingState.replace('{transmorpherUpload}', uploadToken);

    await request('POST', url);
}

export async function getUploadUrl(transmorpherIdentifier, chunkIndex, done) {
    const dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
    const uploadToken = dropzone.options.uploadToken;
    const medium = getMedium(transmorpherIdentifier);

    const chunkUploadUrl = medium.routes.chunkUrl
        .replace('{transmorpherUpload}', uploadToken)
        .replace('{chunkNumber}', chunkIndex);

    const chunkUploadUrlResponse = await requestJson('GET', chunkUploadUrl);

    if (chunkUploadUrlResponse.state === 'error') {
        done(chunkUploadUrlResponse);

        return null;
    }

    return chunkUploadUrlResponse.url;
}

export async function completeUpload(transmorpherIdentifier, uploadToken) {
    const medium = getMedium(transmorpherIdentifier);
    const completeUploadUrl = medium.routes.completeUpload.replace('{transmorpherUpload}', uploadToken);

    return requestJson('POST', completeUploadUrl);
}

export async function abortUpload(transmorpherIdentifier) {
    const medium = getMedium(transmorpherIdentifier);
    const abortUploadUrl = medium.routes.abortUpload.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    await request('DELETE', abortUploadUrl);
}

export async function reserveUploadSlot(transmorpherIdentifier) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.uploadToken.replace('{transmorpherMedia}', medium.transmorpherMediaKey);
    const dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;

    return requestJson('POST', url, {
        body: JSON.stringify({
            filename: dropzone.files[0].name,
        }),
    });
}

export async function getState(transmorpherIdentifier, uploadToken = null) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.state.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson('POST', url, {
        body: JSON.stringify({
            upload_token: uploadToken,
        }),
    });
}

export async function storeUploadResponse(transmorpherIdentifier, uploadToken, response, httpCode) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.handleUploadResponse.replace('{transmorpherUpload}', uploadToken);

    return requestJson('POST', url, {
        body: JSON.stringify({
            response,
            http_code: httpCode,
        }),
    });
}

export async function getVersions(transmorpherIdentifier) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.getVersions.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson('GET', url);
}

export async function setVersion(transmorpherIdentifier, version) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.setVersion.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson('POST', url, {
        body: JSON.stringify({
            version,
        }),
    });
}

export async function deleteTransmorpherMedia(transmorpherIdentifier) {
    const medium = getMedium(transmorpherIdentifier);
    const url = medium.routes.delete.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson('POST', url);
}
