<?php

namespace App\Exports\Leads;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class WidgetsQaExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(public Collection $collections)
    {
    }

    /**
     * @return Collection
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
            'total_calls',
            'total_reached_agent',
            'total_reached_agent_q',
            'total_ivr',
            'total_ivr_q',
            'total_avg_hold_durations',
            'total_ten_hold_durations_q',
            'total_ten_hold_durations',
            'total_avg_durations',
            'total_ad_quality_error',
            'total_call_dropped',
            'total_call_dropped_q',
            'total_not_interested',
            'total_not_interested_q',
            'total_not_qualified',
            'total_not_qualified_q',
            'total_caller_hung_up',
            'total_caller_hung_up_q',
        ];
    }

    public function title(): string
    {
        return 'Qa_Widgets';
    }
}
