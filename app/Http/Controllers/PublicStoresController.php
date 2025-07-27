<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicStoresController extends Controller
{
    protected Store $storeModel;
    protected Client $clientModel;

    public function __construct(Store $storeModel, Client $clientModel)
    {
        $this->storeModel = $storeModel;
        $this->clientModel = $clientModel;
    }

    public function index(): \Inertia\Response
    {
        $client = $this->clientModel->where('user_id', auth()->id())->first();
        $stores = $client->store;
        return Inertia::render('App/Stores/Index', compact('stores'));
    }

    public function show(int $id): \Inertia\Response
    {
        $store = $this->storeModel->findOrFail($id);
        return Inertia::render('Public/Stores/Show', compact('store'));
    }
}
