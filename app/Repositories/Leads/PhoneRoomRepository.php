<?php

namespace App\Repositories\Leads;

use Exception;
use Carbon\Carbon;
use App\Models\Leads\Lead;
use Illuminate\Http\Request;
use App\Models\Leads\PhoneRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Leads\CallsPhoneRoom;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SettingsRepositoryInterface;
use App\Support\Collection as PersonalCollection;

class PhoneRoomRepository extends EloquentRepository implements SettingsRepositoryInterface
{
    public const VENDOR_YP = 'pub_lists.name as vendors_yp';

    /**
     * Summary of Resource.
     */
    public function resourceTr(Collection $data, Model $model): array
    {
        $list_id = $data['pubs']['call_center']['list_id'];

        $response = [
            'campaign_id' => $data['pubs']['call_center']['campaign_id'],
            'source' => $data['traffic_source_id'],
            'source_id' => $data['sub_id'],
            'user' => __toHashValidated(env($model->config['env_user']), $model->api_user),
            'pass' => __toHashValidated(env($model->config['env_key']), $model->api_key),
            'function' => 'add_lead',
            'phone_number' => $data['phone'],
            'phone_code' => '1',
            'list_id' => $list_id[array_rand($list_id)],
            'dnc_check' => 'Y',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'state' => $data['state'],
            'email' => $data['email'],
            'duplicate_check' => 'DUPNAMEPHONECAMP',
            'add_to_hopper' => 'N',
            'usacan_prefix_check' => 'Y',
            'usacan_areacode_check' => 'Y',
            'custom_fields' => 'Y',
            'subID' => $data['sub_id'],
            'vendor_lead_code' => $data['pub_id'],
            'address1' => $data['universal_lead_id'],
            'address2' => $data['trusted_form'],
            'jornaya' => $data['universal_lead_id'],
            'TrustedForm' => $data['trusted_form'],
            'postal_code' => $data['zip_code'],
        ];
        if ($data['pub_id'] == '127') {
            $response['add_to_hopper'] = 'Y';
            $response['hopper_priority'] = '99';
            $response['hopper_local_call_time_check'] = 'Y';
        }
        if (is_array($data['data']) && !empty($data['data']['dob'])) {
            $dob = date_parse($data['data']['dob']);

            if ($dob && isset($dob['year'], $dob['month'], $dob['day']) && checkdate($dob['month'], $dob['day'], $dob['year'])) {
                try {
                    $response['date_of_birth'] = Carbon::createFromDate($dob['year'], $dob['month'], $dob['day'])->format('Y-m-d');
                } catch (Exception $e) {
                    Log::error('DIALER:Error al crear la fecha de nacimiento: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * Summary of Resource.
     */
    public function resource(Collection $data, Model $model): array
    {
        $phoneRoom = $data['pubs']['phone_room'];

        if (isset($phoneRoom['3']) && $phoneRoom['3'] === true) {
            return $this->resourceAcq($data, $model);
        }

        if (isset($phoneRoom['2']) && $phoneRoom['2'] === true) {
            return $this->resourceTr($data, $model);
        }

        return $this->resourceTr($data, $model);
    }

    /**
     * Summary of Resource.
     */
    public function resourceAcq(Collection $data, Model $model): array
    {
        $list_id = $data['pubs']['call_center']['list_id'];
        $response = [
            'vendor_lead_code' => $data['pub_id'],
            'auth_token' => __toHashValidated(env($model->config['env_key']), $model->api_key),
            'phone_number' => $data['phone'],
            'list_id' => $list_id[array_rand($list_id)],
            'check_dup' => '3',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'state' => $data['state'],
            'email' => $data['email'],
            'sub_id' => 'MN1',
        ];

        return $response;
    }

    /**
     * Summary of Resource.
     */
    public function resourceConvoso2(Collection $data, Model $model): array
    {
        $list_id = $data['pubs']['call_center']['list_id'];
        $response = [
            'auth_token' => __toHashValidated(env($model->config['env_key']), $model->api_key),
            'list_id' => $list_id[array_rand($list_id)],
            'phone_number' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'state' => $data['state'],
            'journaya_id' => $data['universal_lead_id'],
            'trusted_form_id' => $data['trusted_form'],
            'leadsource' => 'Favio',
            'datasource' => 'Favio Realtime',
        ];

        return $response;
    }

    /**
     * Summary of Resource.
     */
    public function resourceConvoso(Collection $data, Model $model): array
    {
        $list_id = $data['pubs']['call_center']['list_id'];
        $response = [
            'auth_token' => __toHashValidated(env($model->config['env_key']), $model->api_key),
            'phone_number' => $data['phone'],
            'list_id' => $list_id[array_rand($list_id)],
            'check_dup' => '3',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'state' => $data['state'],
            'email' => $data['email'],
            'campaign_sub1_id' => $data['sub_id'],
            'hopper_priority' => '75',
            'leadsource' => 'aca-madirect-cmp2',
            'dnc_check' => '1',
            'pid' => $data['pub_id'],
            'jeaadid' => $data['universal_lead_id'],
            'hopper' => '1',
            'status' => 'NEW',
            'phone_code' => '1',
        ];

        return $response;
    }

    /**
     * Return Totals Leads from date start & date end.
     */
    public function logs(string $date_start, string $date_end): Builder
    {
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70, 72];

        Config::get('services.trackdrive.pub_id_exception');
        $pubs_lists = array_merge($pubs_lists, Config::get('services.trackdrive.pub_id_exception'));

        $col = ['leads.phone', 'leads.first_name', 'leads.last_name', 'leads.email', 'leads.type', 'leads.yp_lead_id', 'subs.sub_id', 'pubs.pub_list_id', self::VENDOR_YP, 'phone_room_logs.created_at', 'phone_room_logs.log', 'phone_room_logs.status', 'phone_room_logs.phone_room_lead_id', 'phone_room_logs.request'];

        return $this->getLeads($date_start, $date_end, $col, $pubs_lists);
    }

    public function getLeads(string $date_start, string $date_end, array $col, array $pubs_lists): Builder
    {
        return Lead::leftJoin('phone_room_logs', 'leads.phone', '=', 'phone_room_logs.phone_id')
            ->join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->select($col)
            ->whereBetween('leads.date_history', [$date_start, $date_end])
            ->whereNotIn('leads.pub_id', $pubs_lists)
            ->filterFields();
    }

    public function widget(string $date_start, string $date_end): array
    {
        $pubs_lists = [1, 2, 3, 4, 5, 64, 66, 67, 68, 69, 70, 72];
        Config::get('services.trackdrive.pub_id_exception');
        $pubs_lists = array_merge($pubs_lists, Config::get('services.trackdrive.pub_id_exception'));
        $col = ['leads.phone', 'phone_room_logs.id'];

        $leads = $this->getLeads($date_start, $date_end, $col, $pubs_lists);
        $total = $leads->count();
        $success = $leads;
        $success = $success->where('phone_room_logs.status', 1)->count();
        $leads = $this->getLeads($date_start, $date_end, $col, $pubs_lists);
        $rejected = $leads->where('phone_room_logs.status', 0)->count();
        $leads = $this->getLeads($date_start, $date_end, $col, $pubs_lists);
        $sent = $leads->whereNotNull('phone_room_logs.id')->count();
        $leads = $this->getLeads($date_start, $date_end, $col, $pubs_lists);
        $no_sent = $leads->whereNull('phone_room_logs.id')->count();

        return [
            'average' => [
                'total' => $total,
                'success' => $success,
                'rejected' => $rejected,
                'sent' => $sent,
                'no_sent' => $no_sent,
            ],
            'totals_diff' => [
                'total' => 0,
                'success' => 0,
                'rejected' => 0,
                'sent' => 0,
                'no_sent' => 0,
            ],
        ];
    }

    public function metrics(): PersonalCollection
    {
        $sort = request()->input('sort', [['field' => 'revenue', 'dir' => 'desc']]);
        $fields = $sort[0]['field'];
        $dir = $sort[0]['dir'] == 'desc' ? true : false;
        $data = Lead::join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('calls_phone_rooms', 'leads.phone', '=', 'calls_phone_rooms.phone')
            ->join('status_phone_rooms', 'status_phone_rooms.code', '=', DB::Raw('JSON_UNQUOTE(JSON_EXTRACT(calls_phone_rooms.`data`, "$.Status"))'))
            ->leftJoin('convertions', 'convertions.phone_id', '=', 'leads.phone')
            ->select(
                'leads.phone',
                'leads.first_name',
                'leads.last_name',
                'leads.email',
                'leads.pub_id',
                'subs.sub_id',
                'pubs.pub_list_id',
                self::VENDOR_YP,
                'calls_phone_rooms.created_at',
                'calls_phone_rooms.data',
                'calls_phone_rooms.type',
                'status_phone_rooms.code',
                'status_phone_rooms.category'
            )
            ->selectRaw('CONCAT(subs.sub_id,"",pubs.pub_list_id) as sub_pub')
            ->selectRaw('count(leads.phone) as record_count')
            ->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(calls_phone_rooms. `data`, "$.""Call Count"""))) AS call_count')
            ->selectRaw('(SUM(leads.cpl) +  SUM(convertions.cpl)) AS total_cpl')
            ->selectRaw('SUM(revenue) AS revenue')
            ->where('leads.type', '!=', 'MC')
            ->filterFields()
            ->groupBy('leads.pub_id', 'leads.sub_id', 'status_phone_rooms.category')
            ->get();

        $data = $data->groupBy('sub_pub')->map(function ($lead) {
            $metric = $lead->first();
            $record_count = $lead->sum('record_count');
            $cpl = $lead->sum('cpl');
            $rev = $lead->sum('revenue');
            $profit = $rev - $cpl;
            $call_count = $lead->sum('call_count');
            $contact = $lead->where('category', '2')->first();
            $contact = $contact ? $contact->call_count : 0;
            $transfer = $lead->where('category', '1')->first();
            $transfer = $transfer ? $transfer->call_count : 0;
            $record = [];
            $record['phone'] = $metric->phone;
            $record['first_name'] = $metric->first_name;
            $record['last_name'] = $metric->last_name;
            $record['email'] = $metric->email;
            $record['pub_id'] = $metric->pub_id;
            $record['sub_id'] = $metric->sub_id;
            $record['pub_list_id'] = $metric->pub_list_id;
            $record['vendors_yp'] = $metric->vendors_yp;
            $record['created_at'] = $metric->created_at;
            $record['data'] = $metric->data;
            $record['type'] = $metric->type;
            $record['code'] = $metric->code;
            $record['cpl'] = $cpl;
            $record['revenue'] = $rev;
            $record['category'] = $metric->category;
            $record['sub_pub'] = $metric->sub_pub;
            $record['record_count'] = $record_count;
            $record['call_count'] = $call_count;
            $record['avg_dials'] = $record_count > 0 ? (round($call_count / $record_count, 2)) : 0;
            $record['contact_rate'] = $record_count > 0 ? (round($contact / $record_count, 2)) : 0;
            $record['transfer_rate'] = $record_count > 0 ? (round($transfer / $record_count, 2)) : 0;
            $record['cost_record'] = $record_count > 0 ? (round($cpl / $record_count, 2)) : 0;
            $record['rev_record'] = $record_count > 0 ? (round($rev / $record_count, 2)) : 0;
            $record['profit_record'] = $record_count > 0 ? (round($profit / $record_count, 2)) : 0;

            return $record;
        })->sortBy($fields, SORT_REGULAR, $dir)->values();

        return new PersonalCollection($data);
    }

    public function reports(string $date_start, string $date_end): Builder
    {
        return Lead::join('subs', 'subs.id', '=', 'leads.sub_id')
            ->join('pubs', 'pubs.id', '=', 'leads.pub_id')
            ->join('pub_lists', 'pub_lists.id', '=', 'pubs.pub_list_id')
            ->join('calls_phone_rooms', 'leads.phone', '=', 'calls_phone_rooms.phone')
            ->join('status_phone_rooms', 'status_phone_rooms.code', '=', DB::Raw('JSON_UNQUOTE(JSON_EXTRACT(calls_phone_rooms.`data`, "$.Status"))'))
            ->join('phone_room_logs', 'leads.phone', '=', 'phone_room_logs.phone_id')

            ->select(
                'leads.phone',
                'leads.first_name',
                'leads.last_name',
                'leads.email',
                'leads.pub_id',
                'phone_room_logs.created_at',
                'subs.sub_id',
                'pubs.pub_list_id',
                self::VENDOR_YP,
                'calls_phone_rooms.type',
                'calls_phone_rooms.created_at as created',
                'calls_phone_rooms.updated_at as updated',
                'status_phone_rooms.code',
                'status_phone_rooms.category',
                'status_phone_rooms.description'
            )
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(calls_phone_rooms. `data`, "$.""Call Count""")) AS call_count')
            ->whereBetween('phone_room_logs.created_at', [$date_start, $date_end])
            ->filterFields()
            ->sortsFields('created_at');
    }

    public function getPhoneRoom(): Builder
    {
        return PhoneRoom::query();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getQuery(): Builder
    {
        return $this->getPhoneRoom();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function save(Request $request, Model $model): array
    {
        return $this->savePhoneRoom($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getDefaultSortField(): string
    {
        return 'id';
    }

    public function savePhoneRoom(Request $request, PhoneRoom $phone_room): array
    {
        $icon = 'success';
        $message = 'The Phone Room has been successfully updated';
        $phone_room->name = $request->get('name') ?? '';
        $phone_room->service = $request->get('service') ?? '';
        $phone_room->config = $request->get('config') ?? '';
        $phone_room->active = $request->get('active');

        if (!empty($request->get('api_key'))) {
            $phone_room->api_key = __toHash($request->get('api_key'));
        }
        if (!empty($request->get('api_user'))) {
            $phone_room->api_user = __toHash($request->get('api_user'));
        }
        $phone_room->updated_at = now();

        $save = $phone_room->save();
        if (!$save) {
            $icon = 'error';
            $message = 'The Phone Room has not been updated';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }

    public function apiProcess(array $item): void
    {
        $lead = CallsPhoneRoom::find($item['phone']);
        if ($lead) {
            if (!empty(array_diff($item['data'], $lead->data))) {
                $lead->data = $item['data'];
                $lead->save();
            }
        } else {
            $lead = new CallsPhoneRoom();
            $lead->data = $item['data'];
            $lead->phone = $item['phone'];
            $lead->type = $item['type'];
            $lead->save();
        }
    }
}
