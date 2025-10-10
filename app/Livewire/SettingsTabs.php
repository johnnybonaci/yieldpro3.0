<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Route;

class SettingsTabs extends Component
{
    public $tab;

    public $users;

    public $offers;

    public $providers;

    public $traffic_sources;

    public $sub_id;

    public function render()
    {
        $routeName = Route::currentRouteName();
        $this->tab = array_slice(explode('.', $routeName), -1)[0];

        return view('backend.settings.livewire.tabs-lw');
    }

    public function mount($users, $offers, $providers, $traffic_sources, $sub_id)
    {
        $this->users = $users;
        $this->offers = $offers;
        $this->providers = $providers;
        $this->traffic_sources = $traffic_sources;
        $this->sub_id = $sub_id;
    }

    public function viewTab($t)
    {
        $this->redirectRoute('settings.' . $t);
    }
}
