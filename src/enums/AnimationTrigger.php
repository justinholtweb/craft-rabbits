<?php

namespace justinholtweb\rabbits\enums;

enum AnimationTrigger: string
{
    case Click = 'click';
    case Hover = 'hover';
    case Scroll = 'scroll-into-view';
    case Load = 'page-load';

    public function label(): string
    {
        return match ($this) {
            self::Click => 'On Click',
            self::Hover => 'On Hover',
            self::Scroll => 'Scroll Into View',
            self::Load => 'Page Load',
        };
    }
}
