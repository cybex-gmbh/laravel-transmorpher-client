<?php

namespace Transmorpher\Enums;

enum ClientResponse: int
{
    case NO_CONNECTION = -1;
    case NOT_AUTHENTICATED = 401;
    case NOT_FOUND = 404;
    case SERVER_ERROR = 500;
    case VALIDATION_ERROR = 422;

    /**
     * Get the response for a specific case.
     *
     * @param array $body
     *
     * @return array
     */
    public function getResponse(array $body): array
    {
        // Server exception message is in 'message' field.
        return match ($this) {
            ClientResponse::NO_CONNECTION => [
                'success' => false,
                'clientMessage' => trans('transmorpher::errors.no_server_connection'),
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            ClientResponse::NOT_AUTHENTICATED => [
                'success' => false,
                'clientMessage' => $body['message'],
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            ClientResponse::NOT_FOUND => [
                'success' => false,
                'clientMessage' => trans('transmorpher::errors.upload_canceled_or_no_longer_valid'),
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            ClientResponse::SERVER_ERROR => [
                'success' => false,
                'clientMessage' => trans('transmorpher::errors.server_error'),
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            ClientResponse::VALIDATION_ERROR => [
                'success' => false,
                'clientMessage' => $body['message'],
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
        };
    }

    /**
     * @param array $body
     * @param int $code
     *
     * @return array
     */
    public static function getDefaultResponse(array $body, int $code): array
    {
        return [
            'success' => false,
            'clientMessage' => trans('transmorpher::errors.unexpected_error'),
            'serverResponse' => $body['message'],
            'httpCode' => $code,
        ];
    }
}
