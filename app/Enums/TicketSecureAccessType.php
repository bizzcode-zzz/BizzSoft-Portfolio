<?php

namespace App\Enums;

enum TicketSecureAccessType: string
{
    case Hosting = 'hosting';
    case ApplicationLogin = 'application_login';
    case SshSftp = 'ssh_sftp';
    case Database = 'database';
    case ApiToken = 'api_token';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hosting => 'Hosting',
            self::ApplicationLogin => 'Application / Script Login',
            self::SshSftp => 'SSH / SFTP',
            self::Database => 'Database',
            self::ApiToken => 'API Token',
            self::Other => 'Other',
        };
    }
}