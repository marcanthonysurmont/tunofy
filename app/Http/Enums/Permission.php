<?php

namespace App\Http\Enums;

enum Permission: string
{
    case VIEW = 'viewer';
    case CONTRIBUTE = 'contributor';
    case EDIT = 'editor';
}
