<?php

declare(strict_types=1);

namespace App\Enums;

enum MoodBoardLayout: string
{
    case Grid = 'grid';
    case Collage = 'collage';
    case Masonry = 'masonry';
    case Freeform = 'freeform';
}
