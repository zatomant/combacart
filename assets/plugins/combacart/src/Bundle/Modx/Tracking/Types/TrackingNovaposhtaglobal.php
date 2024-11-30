<?php

namespace Comba\Bundle\Modx\Tracking\Types;

class TrackingNovaposhtaglobal extends TrackingNovaposhta
{
    protected string $title = 'Нова Пошта Глобал';
    protected string $url = 'https://novaposhtaglobal.ua/';
    protected string $urltracking = 'https://novaposhtaglobal.ua/track/?num=';

    public function getSupportType(): array
    {
        return array('dt_novaposhta_global');
    }
}