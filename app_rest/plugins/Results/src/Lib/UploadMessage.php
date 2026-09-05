<?php

declare(strict_types = 1);

namespace Results\Lib;

use RestApi\Model\Entity\RestApiEntity;
use Results\Lib\Consts\MessageLevel;

/**
 * One thing worth telling the client about an upload.
 *
 * The code is what a client branches on or translates; the text is for a person and may change wording
 * without warning. Whatever the text names — a class, a runner, a line of the file — belongs in the
 * context as well, so nothing has to be parsed back out of a sentence.
 */
class UploadMessage
{
    private function __construct(
        private readonly string $level,
        private readonly string $code,
        private readonly string $text,
        private readonly array $context
    ) {
    }

    public static function info(string $code, string $text, array $context = []): self
    {
        return new self(MessageLevel::INFO, $code, $text, $context);
    }

    public static function warning(string $code, string $text, array $context = []): self
    {
        return new self(MessageLevel::WARNING, $code, $text, $context);
    }

    public static function error(string $code, string $text, array $context = []): self
    {
        return new self(MessageLevel::ERROR, $code, $text, $context);
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        $asArray = [
            RestApiEntity::CLASS_NAME => 'UploadMessage',
            'level' => $this->level,
            'code' => $this->code,
            'text' => $this->text,
        ];
        if ($this->context) {
            $asArray['context'] = $this->context;
        }
        return $asArray;
    }
}
