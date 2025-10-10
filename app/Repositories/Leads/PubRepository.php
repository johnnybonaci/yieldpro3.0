<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Pub;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Leads\PubList;
use Illuminate\Database\Eloquent\Builder;

class PubRepository
{
    public function __construct()
    {
    }

    /**
     * Summary of getPubId.
     */
    public function getPubId(int $offer_id, int $pub_list_id): ?Pub
    {
        return Pub::where('offer_id', $offer_id)->where('pub_list_id', $pub_list_id)->first();
    }

    public function findById(int $id): ?Pub
    {
        return Pub::find($id);
    }

    public function findPubList(int $id): ?PubList
    {
        return PubList::find($id);
    }

    public function getPubList(): Builder
    {
        return PubList::with('pubs.offers');
    }

    public function savePubs(Request $request, $pubid = null): array
    {
        $pubid = $pubid ?? new PubList();

        $rows = $request->get('form', []);
        $default = [1 => 0];
        foreach ($rows as $key => $value) {
            if (Str::is('keyu*', $key)) {
                $data[$rows["user_p{$value}"]] = $rows["cpl_{$value}"];
            }
        }
        $data = isset($data) ? $data : $default;
        $icon = 'success';
        $message = 'The pubs list has been saved successfully';
        $pubid->name = $request->get('name');
        $pubid->cpl = $data;
        $save = $pubid->save();
        if (!$save) {
            $icon = 'error';
            $message = 'The pub list has not been saved';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }

    public function savePubsOffer(Request $request, $pub = null): array
    {
        $pub = $pub ?? new Pub();
        $setup = $pub->setup;
        $setup['provider'][1] = false;
        $setup['provider'][2] = (bool) $request->input('form.send_td', false);
        $setup['phone_room'][1] = false;
        $setup['phone_room'][2] = (bool) $request->input('form.pr1', false);
        $setup['phone_room'][3] = (bool) $request->input('form.pr2', false);
        $setup['phone_room'][4] = (bool) $request->input('form.pr3', false);
        $setup['phone_room']['type'] = false;
        $setup['provider']['type'] = false;
        $pub->interleave = (bool) $request->input('form.interleave', false);
        $setup['call_center']['list_id'] = explode(',', $request->input('form.list_id', [1001]));
        $setup['call_center']['campaign_id'] = $request->input('form.campaign_id', 'MNACA');
        $setup['call_center']['id'] = $request->input('form.cc_id', 222);
        $setup['traffic_source']['id'] = $request->input('form.traffic_source_id', 1000);
        $pub->offer_id = $request->input('offer_id');
        $pub->pub_list_id = $request->input('pub_list_id');
        $pub->setup = $setup;
        $save = $pub->save();
        $icon = 'success';
        $message = 'The pubs list has been saved successfully';
        if (!$save) {
            $icon = 'error';
            $message = 'The pub list has not been saved';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }

    public function listPubsByOffer(int $offer_id): array
    {
        return Pub::where('offer_id', $offer_id)->orderBy('pub_list_id')->pluck('pub_list_id')->toArray();
    }
}
