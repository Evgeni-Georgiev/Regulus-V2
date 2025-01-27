<?php

namespace App\Enums;

enum CMCResponseStatusEnum: string
{
    case SUCCESS = 'success';
    case FAILED_API_ERROR = 'failed_api_error';
    case FAILED_DB_ERROR = 'failed_db_error';
    case FAILED_UNKNOWN_ERROR = 'failed_unknown_error';
}
