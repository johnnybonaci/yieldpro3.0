<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\JornayaLead;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class JornayaLeadRepository
{
    public function __construct()
    {
    }

    /**
     * Save Jornaya Lead id.
     */
    public function create(Lead $lead, Collection $data): void
    {
        if ($this->exists($data, $lead->phone)) {
            $lead->jornaya()->createQuietly([
                'universal_lead_id' => $data['universal_lead_id'],
                'trusted_form' => $data['trusted_form'] ?? null,
            ]);
        }
    }

    /**
     * Valid if there is more than one jornaya id associated to phone.
     */
    public function exists(Collection $data, int $phone): bool
    {
        if (!empty($data['universal_lead_id'])) {
            return JornayaLead::where('universal_lead_id', $data['universal_lead_id'])
                ->where('phone_id', $phone)->doesntExist();
        }

        return false;
    }

    /**
     * Return Totals Leads from date start & date end.
     */
    public function getUniversalLeadId(string $date_start, string $date_end): Builder
    {
        $col = ['leads.phone', 'leads.first_name', 'leads.last_name', 'leads.email', 'leads.type', 'jornaya_leads.universal_lead_id', 'jornaya_leads.trusted_form', 'jornaya_leads.created_at', 'leads.date_history', 'subs.sub_id', 'pubs.pub_list_id', 'pub_lists.name as vendors_yp'];

        return JornayaLead::join('leads', 'leads.phone', '=', 'jornaya_leads.phone_id')
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->select($col)
            ->whereBetween('leads.date_history', [$date_start, $date_end])
            ->filterFields()
            ->sortsFields('leads.date_history');
    }
}
