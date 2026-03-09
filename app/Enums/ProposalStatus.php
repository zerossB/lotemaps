<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            ProposalStatus::Draft => 'Draft',
            ProposalStatus::Sent => 'Sent',
            ProposalStatus::Accepted => 'Accepted',
            ProposalStatus::Rejected => 'Rejected',
            ProposalStatus::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            ProposalStatus::Draft => 'zinc',
            ProposalStatus::Sent => 'blue',
            ProposalStatus::Accepted => 'green',
            ProposalStatus::Rejected => 'red',
            ProposalStatus::Expired => 'orange',
        };
    }

    /** @return list<self> */
    public static function transitionsFrom(self $status): array
    {
        return match ($status) {
            ProposalStatus::Draft => [ProposalStatus::Sent, ProposalStatus::Rejected],
            ProposalStatus::Sent => [ProposalStatus::Accepted, ProposalStatus::Rejected, ProposalStatus::Expired],
            ProposalStatus::Accepted => [],
            ProposalStatus::Rejected => [ProposalStatus::Draft],
            ProposalStatus::Expired => [ProposalStatus::Draft],
        };
    }
}
