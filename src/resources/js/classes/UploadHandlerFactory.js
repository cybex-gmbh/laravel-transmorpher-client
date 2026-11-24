import DefaultUploadHandler from './UploadHandler/DefaultUploadHandler.js';
import S3MultiPartUploadHandler from './UploadHandler/S3MultiPartUploadHandler.js';

export default class UploadHandlerFactory {
    static create(uploadHandler) {
        switch (uploadHandler) {
            case 's3-multi-part':
                return new S3MultiPartUploadHandler();
            default:
                return new DefaultUploadHandler();
        }
    }
}

