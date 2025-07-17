<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Dealership;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicDealershipsController extends Controller
{
    protected Dealership $dealershipModel;
    protected Client $clientModel;

    public function __construct(Dealership $dealershipModel, Client $clientModel)
    {
        $this->dealershipModel = $dealershipModel;
        $this->clientModel = $clientModel;
    }

    public function index(): \Inertia\Response
    {
        $client = $this->clientModel->where('user_id', auth()->id())->first();
        $dealerships = $client->dealership;
        return Inertia::render('App/Dealers/Index', compact('dealerships'));
    }

    public function show(int $id): \Inertia\Response
    {
        $dealership = $this->dealershipModel->findOrFail($id);
        return Inertia::render('Public/Dealerships/Show', compact('dealership'));
    }
}
