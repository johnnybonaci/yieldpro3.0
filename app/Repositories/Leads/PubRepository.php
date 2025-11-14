<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use App\Models\Leads\Pub;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Leads\PubList;
use Illuminate\Database\Eloquent\Builder;

class PubRepository
{
    use SavesWithResponse;
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
        $data = $default;
        foreach ($rows as $key => $value) {
            if (Str::is('keyu*', $key)) {
                $data[$rows["user_p{$value}"]] = $rows["cpl_{$value}"];
            }
        }

        return $this->saveWithResponse($pubid, 'pubs list', function ($model) use ($request, $data) {
            $model->name = $request->get('name');
            $model->cpl = $data;
        });
    }

    public function savePubsOffer(Request $request, $pub = null): array
    {
        $pub = $pub ?? new Pub();

        return $this->saveWithResponse($pub, 'pubs list', function ($model) use ($request) {
            $setup = $model->setup;
            $setup['provider'][1] = false;
            $setup['provider'][2] = (bool) $request->input('form.send_td', false);
            $setup['phone_room'][1] = false;
            $setup['phone_room'][2] = (bool) $request->input('form.pr1', false);
            $setup['phone_room'][3] = (bool) $request->input('form.pr2', false);
            $setup['phone_room'][4] = (bool) $request->input('form.pr3', false);
            $setup['phone_room']['type'] = false;
            $setup['provider']['type'] = false;
            $model->interleave = (bool) $request->input('form.interleave', false);
            $setup['call_center']['list_id'] = explode(',', $request->input('form.list_id', [1001]));
            $setup['call_center']['campaign_id'] = $request->input('form.campaign_id', 'MNACA');
            $setup['call_center']['id'] = $request->input('form.cc_id', 222);
            $setup['traffic_source']['id'] = $request->input('form.traffic_source_id', 1000);
            $model->offer_id = $request->input('offer_id');
            $model->pub_list_id = $request->input('pub_list_id');
            $model->setup = $setup;
        });
    }

    public function listPubsByOffer(int $offer_id): array
    {
        return Pub::where('offer_id', $offer_id)->orderBy('pub_list_id')->pluck('pub_list_id')->toArray();
    }
}
