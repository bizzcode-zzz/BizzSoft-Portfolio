<?php

namespace App\Enums;

enum CustomizationSecureAccessType: string
{
    case Hosting = 'hosting';
    case SshSftp = 'ssh_sftp';
    case Database = 'database';
    case ApiToken = 'api_token';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hosting => 'Hosting',
            self::SshSftp => 'SSH / SFTP',
            self::Database => 'Database',
            self::ApiToken => 'API Token',
            self::Other => 'Other',
        };
    }
}