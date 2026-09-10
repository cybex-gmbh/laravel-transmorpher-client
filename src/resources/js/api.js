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

export async function setUploadingState({transmorpherIdentifier, uploadToken}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = medium.routes.setUploadingState.replace('{transmorpherUpload}', uploadToken);

    await request({method: 'POST', url});
}

export async function getUploadUrl({transmorpherIdentifier, chunkIndex, uploadToken, done}) {
    const medium = getMedium({transmorpherIdentifier});

    const chunkUploadUrl = medium.routes.chunkUrl
        .replace('{transmorpherUpload}', uploadToken)
        .replace('{chunkNumber}', chunkIndex);

    const chunkUploadUrlResponse = await requestJson({method: 'GET', url: chunkUploadUrl});

    if (chunkUploadUrlResponse.state === 'error') {
        done(chunkUploadUrlResponse);

        return null;
    }

    return chunkUploadUrlResponse.url;
}

export async function completeUpload({transmorpherIdentifier, uploadToken}) {
    const medium = getMedium({transmorpherIdentifier});
    const completeUploadUrl = medium.routes.completeUpload.replace('{transmorpherUpload}', uploadToken);

    return requestJson({method: 'POST', url: completeUploadUrl});
}

export async function abortUpload({transmorpherIdentifier, uploadToken}) {
    const medium = getMedium({transmorpherIdentifier});
    const abortUploadUrl = medium.routes.abortUpload.replace('{transmorpherUpload}', uploadToken);

    await request({method: 'DELETE', url: abortUploadUrl});
}

export async function reserveUploadSlot({transmorpherIdentifier, filename}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = medium.routes.uploadToken.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

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
    const url = medium.routes.state.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

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

export async function storeUploadResponse({transmorpherIdentifier, uploadToken, response, httpCode}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = medium.routes.handleUploadResponse.replace('{transmorpherUpload}', uploadToken);

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
    const url = medium.routes.getVersions.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({method: 'GET', url});
}

export async function setVersion({transmorpherIdentifier, version}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = medium.routes.setVersion
        .replace('{transmorpherMedia}', medium.transmorpherMediaKey)
        .replace('{version}', version);

    return requestJson({method: 'PATCH', url});
}

export async function deleteTransmorpherMedia({transmorpherIdentifier}) {
    const medium = getMedium({transmorpherIdentifier});
    const url = medium.routes.delete.replace('{transmorpherMedia}', medium.transmorpherMediaKey);

    return requestJson({method: 'DELETE', url});
}
