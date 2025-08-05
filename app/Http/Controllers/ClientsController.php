<?php

namespace App\Http\Controllers;

use App\Mail\InvitationClientAccountEmail;
use App\Mail\InvitationCreateAccountEmail;
use App\Models\Client;
use App\Models\Store;
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
            ->where('store_id', Auth::user()->store_id)
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
        $store = Store::find(Auth::user()->store_id);

        if(empty($user)){
            $rawPassword = uniqid(mt_rand(10, 20), true);
            $user = $this->userModel->create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone,
                'password' => bcrypt($rawPassword),
                'store_id' => Auth::user()->store_id,
                'deleted_at' => now(),
            ]);

            $user->assignRole('user');


            $this->sendInvitationCreateCount($rawPassword, $user->email, $user->name, $store->name, route('login'));
            $sendFirtInvitation = true;
        }

        $this->clientModel->firstOrCreate(
            ['user_id' => $user->id],
            ['store_id' => Auth::user()->store_id]
        );



        if(!$sendFirtInvitation){
            $this->sendInvitationClientAccount(
                $user->email,
                $user->name,
                $store->name,
                route('login')
            );
        }

        return redirect()->route('clients.index');
    }

    private function sendInvitationCreateCount(string $rawPassword, string $email,string $username, string $storeName, string $url): void
    {
        Mail::to($email)->send(new InvitationCreateAccountEmail(
            $username,
            $storeName,
            $rawPassword,
            $url
        ));
    }

    private function sendInvitationClientAccount(string $email, string $username, string $storeName, string $invitationUrl): void
    {
        Mail::to($email)->send(new InvitationClientAccountEmail(
            $username,
            $storeName,
            $invitationUrl
        ));
    }

    public function destroy(int $id)
    {
        $client = $this->clientModel
            ->where('store_id', Auth::user()->store_id)
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

    /**
     * API endpoint for client search (used by ClientSelector component)
     */
    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $clients = Client::where('store_id', Auth::user()->store_id)
            ->with('user')
            ->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->user->name,
                    'email' => $client->user->email,
                    'phone' => $client->user->phone_number
                ];
            });

        return response()->json(['data' => $clients]);
    }

    /**
     * API endpoint for quick client creation (used by ClientSelector component)
     */
    public function apiStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20'
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone,
                'password' => bcrypt('password'), // Default password
                'store_id' => Auth::user()->store_id
            ]);

            $client = Client::create([
                'user_id' => $user->id,
                'store_id' => Auth::user()->store_id
            ]);

            $clientData = [
                'id' => $client->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number
            ];

            return response()->json([
                'data' => $clientData,
                'message' => 'Cliente criado com sucesso!'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar cliente',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX endpoint for client search (internal use by components)
     */
    public function ajaxSearch(Request $request)
    {
        return $this->apiSearch($request);
    }

    /**
     * AJAX endpoint for quick client creation (internal use by components)
     */
    public function ajaxStore(Request $request)
    {
        return $this->apiStore($request);
    }
}

