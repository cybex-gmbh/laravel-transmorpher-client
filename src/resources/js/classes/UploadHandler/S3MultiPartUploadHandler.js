import AbstractUploadHandler from './AbstractUploadHandler.js';

export default class S3MultiPartUploadHandler extends AbstractUploadHandler {
    getDropzoneOptions({transmorpherMedium}) {
        const minChunkSize = 5 * 1024 * 1024;

        return {
            chunkSize: Math.max(minChunkSize, transmorpherMedium.chunkSize),
            binaryBody: true,
        };
    }
}

