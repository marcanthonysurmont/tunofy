<?php

namespace App\Http\Enums;

enum VoteType: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';
    case KILL = 'kill';
}