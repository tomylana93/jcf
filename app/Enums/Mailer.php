<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Delivery options selectable in the mail settings; Environment defers to the configured MAIL_MAILER.
 */
enum Mailer: string
{
    case Environment = 'environment';
    case Log = 'log';
    case Smtp = 'smtp';
}
