<?php

namespace App\Pipelines\MediaAlpha\Enums;

enum LeadStatus: string
{
    case PROCESSING = 'processing';

    case COMPLETED = 'completed';

    case COMPLETED_NO_BUYERS = 'completed_no_buyers';

    case COMPLETED_NO_REVENUE = 'completed_no_revenue';

    case FAILED = 'failed';
}
