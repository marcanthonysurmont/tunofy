<?php

namespace App\Http\Enums;

enum Permission: string
{
    case VIEW = 'view';
    case CONTRIBUTE = 'contribute';
    case EDIT = 'edit';
}