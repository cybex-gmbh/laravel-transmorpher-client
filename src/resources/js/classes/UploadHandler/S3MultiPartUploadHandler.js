import AbstractUploadHandler from './AbstractUploadHandler.js';

export default class S3MultiPartUploadHandler extends AbstractUploadHandler {
    getDropzoneOptions() {
        return {
            binaryBody: true,
        };
    }
}

