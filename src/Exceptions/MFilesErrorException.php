<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Exceptions;

use CodebarAg\MFiles\DTO\MFilesError;
use Exception as BaseException;
use Throwable;

final class MFilesErrorException extends BaseException
{
    public function __construct(
        public readonly MFilesError $error,
        ?Throwable $previous = null,
    ) {
        // The message is left byte-for-byte as M-Files reported it so existing log
        // greps and assertions keep matching. Only the previously-possible empty
        // message is replaced, because "" tells an on-call engineer nothing.
        $message = $error->exceptionMessage !== ''
            ? $error->exceptionMessage
            : self::describe($error);

        parent::__construct($message, $error->status, $previous);
    }

    /**
     * The HTTP status M-Files responded with, or 0 when it could not be determined.
     */
    public function status(): int
    {
        return $this->error->status;
    }

    /**
     * The M-Files error code, or null when the payload carried none.
     */
    public function errorCode(): ?string
    {
        return $this->error->errorCode !== '' ? $this->error->errorCode : null;
    }

    /**
     * Whether the failure was an authentication/authorisation rejection.
     */
    public function isAuthenticationFailure(): bool
    {
        return in_array($this->error->status, [401, 403], strict: true);
    }

    /**
     * A single-line summary safe to put in a log line, including the request context
     * that `getMessage()` deliberately omits.
     */
    public function context(): string
    {
        return self::describe($this->error);
    }

    private static function describe(MFilesError $error): string
    {
        $parts = array_filter([
            $error->status !== 0 ? "HTTP {$error->status}" : null,
            $error->exceptionName !== '' ? $error->exceptionName : null,
            $error->errorCode !== '' ? "code {$error->errorCode}" : null,
            $error->method !== '' && $error->url !== '' ? "{$error->method} {$error->url}" : null,
            $error->exceptionMessage !== '' ? $error->exceptionMessage : null,
        ]);

        return $parts === []
            ? 'The M-Files request failed without a diagnosable response.'
            : 'M-Files request failed: '.implode(' | ', $parts);
    }
}
