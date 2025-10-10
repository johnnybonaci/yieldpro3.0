<?php

namespace App\Exports\Leads;

use App\Support\Collection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CallsQaExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(public Collection $collections)
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $issues = request()->get('select_issues_types', null);
        if (is_string($issues)) {
            $issues = explode(',', $issues);
        }
        request()->merge(['select_issues_types' => $issues]);

        return $this->collections;
    }

    public function headings(): array
    {
        return [
            'vendors_td',
            'buyer_id',
            'buyers',
            'phone_id',
            'durations',
            'o_durations',
            'created_at',
            'offers',
            'pub_list_id',
            'date_sale',
            'traffic_source_id',
            'ad_quality_error',
            'not_interested',
            'not_qualified',
            'call_dropped',
            'ivr',
            'hold_durations',
            'o_hold_durations',
            'status_td',
            'reached_agent',
            'caller_hung_up',
        ];
    }

    public function title(): string
    {
        return 'Qa_Report';
    }
}
