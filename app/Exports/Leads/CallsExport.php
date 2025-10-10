<?php

namespace App\Exports\Leads;

use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Repositories\Leads\CallsApiRepository;
use Maatwebsite\Excel\Concerns\FromCollection;

class CallsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $calls_api_repository = app(CallsApiRepository::class);
        $date_start = request()->get('date_start', now()->format('Y-m-d'));
        $date_end = request()->get('date_end', now()->format('Y-m-d'));
        $issues = request()->get('select_issues_types', null);
        if (is_string($issues)) {
            $issues = explode(',', $issues);
        }
        request()->merge(['select_issues_types' => $issues]);
        $calls = $calls_api_repository->calls($date_start, $date_end);

        return $calls->sortsFields('convertions.created_at')->get();
    }

    public function headings(): array
    {
        return [
            'phone',
            'state',
            'status calls',
            'insurance name',
            'revenue',
            'cpl',
            'durations',
            'calls',
            'converted',
            'terminating_phone',
            'did number',
            'date_sale',
            'Sales',
            'offers',
            'buyers',
            'vendors_td',
            'pub id',
            'Sale Conclusion',
            'Sentiment Analysis',
            'Call Ending Issues Status',
            'Call Ending Analysis',
            'Call Ending Reason',
        ];
    }

    public function map($row): array
    {
        return [
            $row->phone_id,
            $row->state,
            $row->status,
            __toAnalisys($row->multiple, 'existing_insurance_name'),
            $row->revenue,
            $row->cpl,
            $row->durations,
            $row->calls,
            $row->converted,
            $row->terminating_phone,
            $row->did_number_id,
            $row->date_sale,
            $row->billable,
            $row->offers,
            $row->buyers,
            $row->vendors_td,
            $row->pub_list_id,
            __toAnalisys($row->multiple, 'sale_analysis'),
            __toAnalisys($row->multiple, 'sentiment_analysis'),
            $this->getBooleanAnalysisData($row, 'call_ending_sooner_result'),
            __toAnalisys($row->multiple, 'call_ending_analysis'),
            $this->getCallEndingReason($row),
        ];
    }

    public function getCallEndingReason($row): string
    {
        $multiple = $row->multiple;

        if (!is_string($multiple)) {
            return '';
        }

        $multiple = json_validate($row->multiple)
            ? json_decode($row->multiple, true)
            : [];

        return $multiple['call_ending_sooner_reason']
            ?? $multiple['call_ending_sooner_reasons'][0]['category']
            ?? '';
    }

    public function getBooleanAnalysisData($row, string $key): string
    {
        $multiple = $row->multiple;

        if (!is_string($multiple)) {
            return 'N/A';
        }

        $multiple = json_validate($row->multiple)
            ? json_decode($row->multiple, true)
            : [];

        return match ($multiple[$key] ?? null) {
            true => 'YES',
            false => 'NO',
            null => 'N/A',
            default => 'N/A',
        };
    }
}
