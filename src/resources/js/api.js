import {getMedium} from './state.js';
import {getCsrfToken} from './utils.js';
import {routes} from "./routes";

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

async function request({method, url, options = {}}) {
    return fetch(url, withDefaultHeaders({
        ...options,
        method,
    }));
}

async function requestJson({method, url, options = {}}) {
    const response = await request({method, url, options});

    return response.json();
}

export async function setUploadingState({uploadToken}) {
    const url = routes.uploads.setState.replace('{transmorpherUpload}', uploadToken);

    await request({method: 'POST', url});
}

export async function getUploadUrl({chunkIndex, uploadToken, done}) {
    const chunkUploadUrl = routes.uploads.url
        .replace('{transmorpherUpload}', uploadToken)
        .replace('{chunkNumber}', chunkIndex);

    const chunkUploadUrlResponse = await requestJson({method: 'GET', url: chunkUploadUrl});

    if (chunkUploadUrlResponse.state === 'error') {
        done(chunkUploadUrlResponse);

        return null;
    }

    return chunkUploadUrlResponse.url;
}

export async function completeUpload({uploadToken}) {
    const completeUploadUrl = routes.uploads.complete.replace('{transmorpherUpload}', uploadToken);

    return requestJson({method: 'POST', url: completeUploadUrl});
}

export async function abortUpload({uploadToken}) {
    const abortUploadUrl = routes.uploads.abort.replace('{transmorpherUpload}', uploadToken);

    await request({method: 'DELETE', url: abortUploadUrl});
}

export async function reserveUploadSlot({transmorpherIdentifier, filename}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = routes.media.reserveUpload.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({
        method: 'POST',
        url,
        options: {
            body: JSON.stringify({
                filename,
            }),
        },
    });
}

export async function getState({transmorpherIdentifier, uploadToken = null}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = routes.media.getState.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({
        method: 'POST',
        url,
        options: {
            body: JSON.stringify({
                upload_token: uploadToken,
            }),
        },
    });
}

export async function storeUploadResponse({uploadToken, response, httpCode}) {
    const url = routes.uploads.handleResponse.replace('{transmorpherUpload}', uploadToken);

    return requestJson({
        method: 'POST',
        url,
        options: {
            body: JSON.stringify({
                response,
                http_code: httpCode,
            }),
        },
    });
}

export async function getVersions({transmorpherIdentifier}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = routes.versions.get.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({method: 'GET', url});
}

export async function setVersion({transmorpherIdentifier, version}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = routes.versions.set
        .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
        .replace('{version}', version);

    return requestJson({method: 'PATCH', url});
}

export async function deleteMedia({transmorpherIdentifier}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = routes.media.delete.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({method: 'DELETE', url});
}
