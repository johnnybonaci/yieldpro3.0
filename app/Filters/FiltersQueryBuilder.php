<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FiltersQueryBuilder
{
    /**
     * Summary of sortsFields.
     */
    public function sortsFields(): Closure
    {
        return function ($order) {
            /** @var Builder $this */
            if (request()->filled('sort')) {
                foreach (request()->get('sort') as $sorter) {
                    if ($sorter['field'] != 'undefined') {
                        $sorter['field'] == 'published' ? $this->published($sorter) : $this->orderBy($sorter['field'], $sorter['dir']);
                    }
                }
            } else {
                $this->orderDefault($order);
            }

            return $this;
        };
    }

    public function sortsFieldsAsc(): Closure
    {
        return function ($order) {
            /** @var Builder $this */
            $this->orderAsc($order);

            return $this;
        };
    }

    /**
     * Summary of filterFields.
     */
    public function filterFields(): Closure
    {
        return function () {
            collect(request()->only(['leads_type', 'pubs_pub1list1id', 'subs_id', 'convertions_buyer1id', 'convertions_status', 'users_type', 'offers_provider1id', 'convertions_traffic1source1id', 'convertions_offer1id', 'campaign1name1id', 'convertions_phone1id', 'phone1room1logs_status', 'category', 'calls1phone1rooms_type', 'phone1room1logs_phone1room1id', 'convertions_id', 'recordings_billable', 'recordings_status', 'leads_state', 'traffic1source1id', 'recordings_insurance', 'leads_sub1id3', 'leads_cc1id', 'leads_sub1id2', 'leads_sub1id5']))->map(function ($value, $key) {
                /** @var Builder $this */
                $operator = in_array($key, ['status', 'pubs_pub1list1id', 'leads_type', 'convertions_offer1id', 'leads_state', 'traffic1source1id']) ? 'IN' : '=';
                $this->filters(__toFields($key), $operator, $value);
            });
            $table = request()->input('url_switch') == 'tracking-campaign' ? 'tracking_leads' : 'leads';

            foreach (request()->get('filter', []) as $filter) {
                if (is_string($filter)) {
                    $filter = (array) json_decode($filter);
                }
                $filter['field'] = match ($filter['field']) {
                    'buyers' => 'buyers.name',
                    'phone' => $table . '.phone',
                    'phone_bot' => 'bot_leads.phone',
                    'user_name' => 'users.name',
                    'role_name' => 'roles.name',
                    'vendors' => 'pub_lists.name',
                    'leads_type' => $table . '.type',
                    'leads_state' => $table . '.state',

                    default => $filter['field'],
                };

                /** @var Builder $this */
                $this->filters($filter['field'], $filter['type'], '%' . trim($filter['value']) . '%');
            }

            return $this;
        };
    }
}
