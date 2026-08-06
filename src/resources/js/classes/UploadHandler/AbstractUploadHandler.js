export default class AbstractUploadHandler {
    constructor() {
        if (new.target === AbstractUploadHandler) {
            throw new Error('UploadHandler is abstract and cannot be instantiated directly.');
        }
    }

    getDropzoneOptions({transmorpherMedium}) {
        throw new Error('getDropzoneOptions() must be implemented by subclass.');
    }
}

