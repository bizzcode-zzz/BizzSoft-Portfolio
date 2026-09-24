<?php

namespace App\Enums;

enum CustomizationRequestStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case NeedsInformation = 'needs_information';
    case QuoteSent = 'quote_sent';
    case QuoteDeclined = 'quote_declined';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case ReadyForReview = 'ready_for_review';
    case RevisionRequested = 'revision_requested';
    case Completed = 'completed';
    case RequestDeclined = 'request_declined';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::NeedsInformation => 'Needs Information',
            self::QuoteSent => 'Quote Sent',
            self::QuoteDeclined => 'Quote Declined',
            self::Accepted => 'Accepted',
            self::InProgress => 'In Progress',
            self::ReadyForReview => 'Ready for Review',
            self::RevisionRequested => 'Revision Requested',
            self::Completed => 'Completed',
            self::RequestDeclined => 'Request Declined',
            self::Cancelled => 'Cancelled',
        };
    }
}