<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * `415`: the `file` of a send has an extension the API does not send, and no `file_type` says what
 * it is. Send `file_type` (`image`, `video`, `audio`, `document` or `sticker`), or a file with a
 * supported extension. The API has no stable code for it: `errorCode` is `null`.
 */
class UnsupportedMediaTypeException extends ApiException
{
    /**
     * The extensions the API sends, when the answer lists them (`jpg`, `pdf`, `mp4`…).
     *
     * @var list<string>
     */
    public readonly array $supportedFileTypes;

    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(int $status, mixed $body, array $headers, string $method, string $url, ?int $now = null)
    {
        parent::__construct($status, $body, $headers, $method, $url, $now);
        $types = \is_array($body) && \is_array($body['supported_file_types'] ?? null) ? $body['supported_file_types'] : [];
        $this->supportedFileTypes = array_values(array_filter($types, 'is_string'));
    }
}
