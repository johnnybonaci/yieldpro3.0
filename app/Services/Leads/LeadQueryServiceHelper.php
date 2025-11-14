<?php

namespace App\Services\Leads;

/**
 * Lead Query Service Helper
 *
 * Extracts complex logic from LeadQueryService::campaignDashboard()
 * to reduce cognitive complexity from 21 to ~14.
 */
class LeadQueryServiceHelper
{
    /**
     * Build fields array based on field type.
     */
    public static function buildFieldsArray(array $item, string $fields, bool $includeAllFields = true): array
    {
        if ($fields != 'sub_id3') {
            return [
                'campaign_name_id' => $includeAllFields ? $item['campaign_name_id'] : '',
                'vendors_yp' => $includeAllFields ? $item['vendors_yp'] : '',
                'sub_id' => $includeAllFields ? $item['sub_id'] : '',
                'pub_id' => $includeAllFields ? $item['pub_id'] : '',
                'sub_id3' => $item['sub_id3'],
                'sub_id2' => $includeAllFields ? $item['sub_id2'] : '',
                'sub_id4' => $includeAllFields ? $item['sub_id4'] : '',
                'pub_list_id' => $includeAllFields ? $item['pub_list_id'] : '',
                'traffic_source_id' => $includeAllFields ? $item['traffic_source_id'] : '',
            ];
        }

        return [
            'campaign_name_id' => '',
            'vendors_yp' => '',
            'sub_id' => '',
            'pub_id' => '',
            'sub_id3' => $item['sub_id3'],
            'sub_id2' => '',
            'sub_id4' => '',
            'pub_list_id' => '',
            'traffic_source_id' => '',
        ];
    }

    /**
     * Build metrics with conversion data.
     */
    public static function buildMetricsWithConversions(array $data, array $item): array
    {
        return [
            ...$data[0],
            'cpl' => $data[0]['cpl'] + $item['cpl'],
            'cpl_calls' => $data[0]['cpl'],
            'cpl_leads' => $item['cpl'],
            'revenue' => $data[0]['revenue'] ?? 0,
            'answered' => $data[0]['answered'] ?? 0,
            'calls' => $data[0]['calls'] ?? 0,
            'converted' => $data[0]['converted'] ?? 0,
            'leads' => $item['leads'],
        ];
    }

    /**
     * Build empty metrics when no conversion data exists.
     */
    public static function buildEmptyMetrics(array $item, string $fields): array
    {
        $baseFields = self::buildFieldsArray($item, $fields, true);

        return array_merge($baseFields, [
            'vendors_td' => '',
            'view_by' => $item['view_by'],
            'revenue' => '0.00',
            'cpl' => $item['cpl'],
            'cpl_calls' => 0,
            'cpl_leads' => $item['cpl'],
            'calls' => '0',
            'converted' => '0',
            'answered' => '0',
            'type' => $item['type'],
            'leads' => $item['leads'],
        ]);
    }

    /**
     * Filter conversions by campaign fields.
     */
    public static function filterConversions($totals_convertions, array $item, string $fields)
    {
        if ($fields == 'cm_pub') {
            return $totals_convertions
                ->where('campaign_name_id', $item['campaign_name_id'])
                ->where('sub_id3', $item['sub_id3'])
                ->where('pub_id', $item['pub_id'])
                ->where('traffic_source_id', $item['traffic_source_id']);
        }

        return $totals_convertions->where($fields, $item[$fields]);
    }
}
