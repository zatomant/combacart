<?php

namespace Comba\Bundle\Modx\Tracking\Types;

/**
 * Tracking class of Autolux
 */
class TrackingAutolux extends TrackingNone
{
    protected string $title = 'Автолюкс';
    protected string $url = 'https://autolux-post.com.ua/';
    protected string $urltracking = 'https://autolux-post.com.ua/tracking/';

    public function getSupportType(): array
    {
        return array('dt_autolux');
    }
}
