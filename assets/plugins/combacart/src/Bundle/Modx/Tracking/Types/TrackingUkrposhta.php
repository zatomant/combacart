<?php

namespace Comba\Bundle\Modx\Tracking\Types;

/**
 * Tracking class of Ukrposhta
 */
class TrackingUkrposhta extends TrackingNone
{
    protected string $title = 'Укрпошта';
    protected string $url = 'https://www.ukrposhta.ua/';
    protected string $urltracking = 'https://track.ukrposhta.ua/tracking_UA.html?barcode=';

    public function getSupportType(): array
    {
        return array('dt_ukrposhta', 'dt_ukrposhta_int', 'dt_ukrposhta_express');
    }
}
