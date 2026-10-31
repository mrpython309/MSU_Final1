<?php

namespace App;

enum AssetCondition: string
{
    case NEW = 'new';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case DISPOSED = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::GOOD => 'Good',
            self::FAIR => 'Fair',
            self::POOR => 'Poor',
            self::DISPOSED => 'Disposed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'success',
            self::GOOD => 'primary',
            self::FAIR => 'warning',
            self::POOR => 'danger',
            self::DISPOSED => 'danger',
        };
    }
}
