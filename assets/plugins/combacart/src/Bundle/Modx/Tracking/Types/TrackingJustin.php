<?php

namespace Comba\Bundle\Modx\Tracking\Types;

/**
 * Tracking class of
 */
class TrackingJustin extends TrackingNone
{
    protected string $title = 'Justin';
    protected string $url = 'https://justin.ua/';
    protected string $urltracking = 'https://justin.ua/tracking?number=';

    public function getSupportType(): array
    {
        return array('dt_justin');
    }
}
