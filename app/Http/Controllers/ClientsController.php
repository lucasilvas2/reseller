<?php

namespace App\Http\Controllers;

use App\Mail\InvitationClientAccountEmail;
use App\Mail\InvitationCreateAccountEmail;
use App\Models\Client;
use App\Models\Dealership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClientsController extends Controller
{
    protected User $userModel;
    protected Client $clientModel;

    public function __construct(User $user, Client $client)
    {
        $this->userModel = $user;
        $this->clientModel = $client;
    }

    public function index(): \Inertia\Response
    {
        $clients = $this->clientModel->where('dealership_id', auth()->user()->dealership_id)->with('user')->get();
        return inertia('App/Clients/Index', compact('clients'));
    }

    public function create(): \Inertia\Response
    {
        return inertia('App/Clients/Create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
        ]);

        $sendFirtInvitation = false;

        $user = $this->userModel->where('email', $request->email)->first();
        $dealership = auth()->user()->dealerships()->first();

        if(empty($user)){
            $rawPassword = uniqid(mt_rand(10, 20), true);
            $user = $this->userModel->create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($rawPassword),
                'dealership_id' => auth()->user()->dealership_id,
                'deleted_at' => now(),
            ]);

            $user->assignRole('user');


            $this->sendInvitationCreateCount($rawPassword, $user->email, $user->name, $dealership->name, route('login'));
            $sendFirtInvitation = true;
        }

        $this->clientModel->firstOrCreate(
            ['user_id' => $user->id],
            ['dealership_id' => auth()->user()->dealership_id]
        );



        if(!$sendFirtInvitation){
            $this->sendInvitationClientAccount(
                $user->email,
                $user->name,
                $dealership->name,
                route('login')
            );
        }

        return redirect()->route('clients.index');
    }

    private function sendInvitationCreateCount(string $rawPassword, string $email,string $username, string $dealershipName, string $url): void
    {
        Mail::to($email)->send(new InvitationCreateAccountEmail(
            $username,
            $dealershipName,
            $rawPassword,
            $url
        ));
    }

    private function sendInvitationClientAccount(string $email, string $username, string $dealershipName, string $invitationUrl): void
    {
        Mail::to($email)->send(new InvitationClientAccountEmail(
            $username,
            $dealershipName,
            $invitationUrl
        ));
    }

    public function delete(int $id): \Illuminate\Http\RedirectResponse
    {
        $client = $this->clientModel->findOrFail($id);
        $user = $client->user;

        if ($user) {
            $user->delete();
        }

        $client->delete();

        return redirect()->route('clients.index');
    }

}

