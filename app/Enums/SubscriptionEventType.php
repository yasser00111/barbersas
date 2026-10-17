<?php

namespace App\Enums;

enum SubscriptionEventType: string
{
    case Created = 'created';
    case Activated = 'activated';
    case Renewed = 'renewed';
    case Expanded = 'expanded';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Reactivated = 'reactivated';
    case Cancelled = 'cancelled';
    case PlanChanged = 'plan_changed';
}
