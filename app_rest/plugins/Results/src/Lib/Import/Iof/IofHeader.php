<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

/**
 * What an IOF document says about itself: which message it is, which software wrote it, and which
 * kind of upload it therefore represents.
 */
class IofHeader
{
    public function __construct(
        private readonly string $rootElement,
        private readonly string $creator,
        private readonly string $iofVersion,
        private readonly string $uploadType,
        private readonly ?string $warning = null
    ) {
    }

    public function getRootElement(): string
    {
        return $this->rootElement;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function getIofVersion(): string
    {
        return $this->iofVersion;
    }

    public function getUploadType(): string
    {
        return $this->uploadType;
    }

    /**
     * Set when the upload type had to be guessed, so the guess can reach the operator instead of
     * silently deciding what happens to their splits.
     */
    public function getWarning(): ?string
    {
        return $this->warning;
    }

    public function withUploadType(string $uploadType): self
    {
        return new self($this->rootElement, $this->creator, $this->iofVersion, $uploadType);
    }
}
