<?php

namespace App\Core\ErrorDefinition;

enum ErrorSeverity: string
{
  case LOW = 'LOW';
  case MEDIUM = 'MEDIUM';
  case HIGH = 'HIGH';
  case CRITICAL = 'CRITICAL';
}
