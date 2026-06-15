<?php

namespace App\Enums\Reports;

enum ReportType: string
{
    case SF1 = 'sf1';
    case SF2 = 'sf2';
    case SF3 = 'sf3';
    case SF4 = 'sf4';
    case SF5 = 'sf5';
    case SF6 = 'sf6';
    case SF7 = 'sf7';
    case SF8 = 'sf8';
    case SF9 = 'sf9';
    case SF10 = 'sf10';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
