<?php

namespace Comba\Bundle\Modx\Tracking\Types;

/**
 * Ttacking class of meest express
 */
class TrackingMeest extends TrackingNone
{
    protected string $title = 'Meest';
    protected string $url = 'https://ua.meest.com/';
    protected string $urltracking = 'https://ua.meest.com/parcel-track?parcel_number=';

    public function getSupportType(): array
    {
        return array('dt_meest');
    }
}
