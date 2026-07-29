<?php

namespace App\Core\ErrorDefinition;

enum ErrorCategory: string
{
    case VALIDATION = 'VALIDATION';
    case AUTHENTICATION = 'AUTHENTICATION';
    case AUTHORIZATION = 'AUTHORIZATION';
    case NOT_FOUND = 'NOT_FOUND';
    case BUSINESS_RULE = 'BUSINESS_RULE';
    case WORKFLOW = 'WORKFLOW';
    case INTEGRATION = 'INTEGRATION';
    case SYSTEM = 'SYSTEM';
}
