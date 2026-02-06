<?php

namespace Pdaether\BlockWire\Enums;

enum EditorContext: string
{
    case EDITOR = 'editor';
    case RENDERED = 'rendered';
    case PREVIEW = 'preview';
}
