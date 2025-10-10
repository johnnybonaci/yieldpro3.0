<?php

namespace App\Http\Controllers\Backend\Users;

use App\Models\User;
use App\Models\Leads\Offer;
use Illuminate\Http\Request;
use App\Models\Leads\PubList;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Repositories\Leads\LeadApiRepository;

class UserController extends Controller
{
    private string $user_type_client = 'user';

    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected UserRepository $user_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $offers = $this->lead_api_repository->getAll(new Offer());
        $pub_id = $this->lead_api_repository->getAll(new PubList());
        $type = $offers->groupBy('type')->keys();
        $breadcrumb = 'Users';

        return view('backend/users/index', compact('breadcrumb', 'offers', 'pub_id', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $breadcrumb = 'Create User';

        return view('backend/users/user-create', compact('breadcrumb'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
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
            'name' => 'required|min:3|max:255|unique:users,name',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'pub_id_selected' => 'required_unless:type_selected,user',
            'role_selected' => 'required_if:type_selected,user',
        ]);

        $user = new User();

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));

        if ($this->user_type_client !== $request->input('type_selected')) {
            $user->type = $request->input('type_selected');
            $user->pub_id = $request->input('pub_id_selected');
        } else {
            $user->syncRoles($request->input('role_selected'));
        }

        $user->save();

        return $user;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->user_repository->show();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $breadcrumb = 'Edit User';

        return view('backend/users/user-edit', compact('breadcrumb', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => [
                'sometimes',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'name' => 'required|min:3|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email:rfc,dns|unique:users,email,' . $user->id,
            'pub_id_selected' => 'required_unless:type_selected,user',
            'role_selected' => 'required_if:type_selected,user',
        ]);

        $user->email = $request->input('email');
        $user->name = $request->input('name');

        if (!empty($request->input('password'))) {
            $user->password = Hash::make($request->input('password'));
        }

        if ($this->user_type_client !== $request->input('type_selected')) {
            $user->type = $request->input('type_selected');
            $user->pub_id = $request->input('pub_id_selected');
        } else {
            $user->type = null;
            $user->pub_id = null;
            // Set user role
            $user->syncRoles($request->input('role_selected'));
        }

        $saved = $user->save();

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }
}
