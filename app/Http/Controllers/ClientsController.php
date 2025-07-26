<?php

namespace App\Http\Controllers;

use App\Mail\InvitationClientAccountEmail;
use App\Mail\InvitationCreateAccountEmail;
use App\Models\Client;
use App\Models\Dealership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function index(Request $request): \Inertia\Response
    {
        $query = $this->clientModel
            ->where('dealership_id', Auth::user()->dealership_id)
            ->with('user');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Apply name filter
        if ($request->filled('name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        // Apply email filter
        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->email}%");
            });
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Apply sorting
        $sortKey = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if (in_array($sortKey, ['name', 'email', 'phone_number'])) {
            $query->join('users', 'clients.user_id', '=', 'users.id')
                  ->orderBy("users.{$sortKey}", $sortOrder)
                  ->select('clients.*');
        } else {
            $query->orderBy($sortKey, $sortOrder);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $clients = $query->paginate($perPage);

        // Transform data for frontend
        $transformedData = collect($clients->items())->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->user->name,
                'email' => $client->user->email,
                'phone_number' => $client->user->phone_number,
                'created_at' => $client->created_at,
                'created_at_formatted' => $client->created_at->format('M d, Y H:i'),
            ];
        });

        return inertia('App/Clients/Index', [
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'from' => $clients->firstItem(),
                'to' => $clients->lastItem(),
                'links' => [],
            ],
            'filters' => $request->only(['search', 'name', 'email', 'date_from', 'date_to', 'sort', 'order'])
        ]);
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
        $dealership = Dealership::find(Auth::user()->dealership_id);

        if(empty($user)){
            $rawPassword = uniqid(mt_rand(10, 20), true);
            $user = $this->userModel->create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone,
                'password' => bcrypt($rawPassword),
                'dealership_id' => Auth::user()->dealership_id,
                'deleted_at' => now(),
            ]);

            $user->assignRole('user');


            $this->sendInvitationCreateCount($rawPassword, $user->email, $user->name, $dealership->name, route('login'));
            $sendFirtInvitation = true;
        }

        $this->clientModel->firstOrCreate(
            ['user_id' => $user->id],
            ['dealership_id' => Auth::user()->dealership_id]
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

    public function destroy(int $id)
    {
        $client = $this->clientModel
            ->where('dealership_id', Auth::user()->dealership_id)
            ->find($id);

        if (!$client) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $client->delete();

        return \Inertia\Inertia::location(route('clients.index'));
    }

    public function show(int $id): \Inertia\Response
    {
        $client = $this->clientModel->with('user')->findOrFail($id);
        return inertia('App/Clients/View', compact('client'));
    }

}

