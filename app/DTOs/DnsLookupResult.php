<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

final readonly class DnsLookupResult
{
    /**
     * @param  array<string>  $recordTypes
     */
    public function __construct(
        public bool $hasRecords,
        public array $recordTypes,
        public ?string $error,
        public Carbon $checkedAt,
    ) {}

    /**
     * @param  array<string>  $recordTypes
     */
    public static function withRecords(array $recordTypes): self
    {
        return new self(
            hasRecords: true,
            recordTypes: $recordTypes,
            error: null,
            checkedAt: now(),
        );
    }

    public static function withoutRecords(): self
    {
        return new self(
            hasRecords: false,
            recordTypes: [],
            error: null,
            checkedAt: now(),
        );
    }

    public static function withError(string $error): self
    {
        return new self(
            hasRecords: false,
            recordTypes: [],
            error: $error,
            checkedAt: now(),
        );
    }

    /**
     * @param  array<string>  $recordTypes
     */
    public static function fromCache(
        bool $hasRecords,
        array $recordTypes,
        ?string $error,
        Carbon $checkedAt
    ): self {
        return new self(
            hasRecords: $hasRecords,
            recordTypes: $recordTypes,
            error: $error,
            checkedAt: $checkedAt,
        );
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }

    public function isSuccessful(): bool
    {
        return $this->error === null;
    }

    /**
     * @return array{has_records: bool, record_types: array<string>, error: string|null, checked_at: string}
     */
    public function toArray(): array
    {
        return [
            'has_records' => $this->hasRecords,
            'record_types' => $this->recordTypes,
            'error' => $this->error,
            'checked_at' => $this->checkedAt->toISOString(),
        ];
    }
}
