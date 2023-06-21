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
        $response = [
            'state' => UploadState::ERROR->value,
            'message' => $body['message'],
            'httpCode' => $this->value,
        ];

        // Server exception message is in 'message' field.
        $response['clientMessage'] = match ($this) {
            ClientResponse::NO_CONNECTION => 'Could not connect to server.',
            ClientResponse::NOT_FOUND => 'Canceled by a new upload or the upload is no longer valid.',
            ClientResponse::SERVER_ERROR => 'There was an error on the server.',
            ClientResponse::NOT_AUTHENTICATED,
            ClientResponse::VALIDATION_ERROR => $body['message'],
        };

        return $response;
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
            'state' => UploadState::ERROR->value,
            'clientMessage' => 'An unexpected error occurred.',
            'message' => $body['message'],
            'httpCode' => $code,
        ];
    }
}
