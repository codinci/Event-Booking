<?php

namespace App\Enums;

enum RefundStatus: string
{
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case Sending = 'sending';
    case Sent = 'sent';
    case Completed = 'completed';
    case Failed = 'failed';
    case Review = 'review';
    case Rejected = 'rejected';

    /** @return list<self> */
    public static function unresolved(): array
    {
        return [self::AwaitingApproval, self::Approved, self::Sending, self::Sent, self::Failed, self::Review];
    }
}
