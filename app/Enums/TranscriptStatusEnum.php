<?php

namespace App\Enums;

enum TranscriptStatusEnum: int
{
    case PROCESSED = 1;

    case AVAILABLE_TO_DOWNLOAD = 2;

    case TRANSCRIBING = 3;

    case FAILED = 4;

    public function description(): string
    {
        return match ($this) {
            self::PROCESSED => 'View',
            self::AVAILABLE_TO_DOWNLOAD => 'Transcribe Call',
            self::TRANSCRIBING => 'Transcribe in progress',
            self::FAILED => 'Error',
        };
    }
}
