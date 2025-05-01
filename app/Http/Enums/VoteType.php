<?php

enum VoteType: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';
    case KILL = 'kill';
}