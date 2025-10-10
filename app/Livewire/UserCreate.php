<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Leads\Pub;
use App\Models\Leads\Offer;
use Livewire\Attributes\Validate;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Repositories\Leads\LeadApiRepository;

class UserCreate extends Component
{
    public $user_type_client = 'user';

    public $formStep = 1;

    #[Validate]
    public $user_name = '';

    #[Validate]
    public $user_email = '';

    #[Validate]
    public $password = '';

    #[Validate]
    public $password_confirmation = '';

    #[Validate]
    public $pub_id_selected;

    #[Validate]
    public $role_selected = 'client';

    // client default value for type
    public $type_selected = 'user';

    public $type;

    public $pub_id;

    public $offers;

    public $role_list;

    public $role;

    public function mount()
    {
        $lead_api_repository = new LeadApiRepository();
        $this->offers = $lead_api_repository->getAll(new Offer());
        $this->type = $this->offers->groupBy('type')->keys();

        // User role names
        $this->role_list = Role::pluck('name')->toArray();
    }

    public function render()
    {
        return view('backend.users.livewire.user-create-lw');
    }

    public function updatedTypeSelected($offer_type)
    {
        if (!is_null($offer_type)) {
            if ($offer_type == $this->user_type_client) {
                $this->pub_id = null;
            } else {
                $offer = Offer::where('type', $offer_type)
                    ->where('provider_id', '=', env('TRACKDRIVE_PROVIDER_ID'))
                    ->get()
                    ->first();
                $this->pub_id = Pub::where('offer_id', $offer->id)
                    ->get()
                    ->sortBy('pub_list_id');
            }
        }
    }

    protected function rules()
    {
        return [
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'user_name' => 'required|min:3|max:255|unique:users,name',
            'user_email' => 'required|email:rfc,dns|unique:users,email',
            'pub_id_selected' => 'required_unless:type_selected,user',
            'role_selected' => 'required_if:type_selected,user',
        ];
    }

    public function save()
    {
        $this->validate();
        $user = new User();
        $user->name = $this->user_name;
        $user->email = $this->user_email;
        $user->password = Hash::make($this->password);

        if ($this->type_selected != $this->user_type_client) {
            $user->type = $this->type_selected;
            $user->pub_id = $this->pub_id_selected;
        } else {
            // Set user role
            $user->syncRoles($this->role_selected);
        }

        $saved = $user->save();

        if ($saved) {
            $this->formStep = 2;
        } else {
            $this->formStep = 3;
        }
    }
}
