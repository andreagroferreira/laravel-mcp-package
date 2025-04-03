<?php

namespace WizardingCode\MCPServer\Types;

enum MessageType: string
{
    case REQUEST = 'request';
    case NOTIFICATION = 'notification';
    case RESPONSE = 'response';
    case ERROR = 'error';
}