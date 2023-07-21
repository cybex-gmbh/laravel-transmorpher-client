<?php

namespace Transmorpher\Enums;

enum ClientErrorResponse: int
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
            self::NO_CONNECTION => [
                'success' => false,
                'clientMessage' => 'Could not connect to server.',
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            self::NOT_AUTHENTICATED => [
                'success' => false,
                'clientMessage' => $body['message'],
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            self::NOT_FOUND => [
                'success' => false,
                'clientMessage' => 'Canceled by a new upload or the upload is no longer valid.',
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            self::SERVER_ERROR => [
                'success' => false,
                'clientMessage' => 'There was an error on the server.',
                'serverResponse' => $body['message'],
                'httpCode' => $this->value,
            ],
            self::VALIDATION_ERROR => [
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
            'clientMessage' => 'An unexpected error occurred.',
            'serverResponse' => $body['message'],
            'httpCode' => $code,
        ];
    }

    /**
     * @param array $body
     * @param int $code
     * @return array
     */
    public static function get(array $body, int $code): array
    {
        return self::tryFrom($code)?->getResponse($body) ?? self::getDefaultResponse($body, $code);
    }
}
