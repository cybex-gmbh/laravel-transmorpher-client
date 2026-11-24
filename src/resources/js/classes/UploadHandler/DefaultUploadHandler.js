import AbstractUploadHandler from './AbstractUploadHandler.js';

export default class DefaultUploadHandler extends AbstractUploadHandler {
    getDropzoneOptions({transmorpherMedium}) {
        return {
            binaryBody: false,
        };
    }
}

