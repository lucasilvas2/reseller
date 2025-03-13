<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DealershipsController extends Controller
{
    protected Dealership $dealershipModel;

    public function __construct(Dealership $dealershipModel)
    {
        $this->dealershipModel = $dealershipModel;
    }

    public function index(): \Inertia\Response
    {
        $dealerships = $this->dealershipModel->all();

        return Inertia::render('Admin/Dealerships/Index', [
            'dealerships' => $dealerships,
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Dealerships/Create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required',
        ]);

        $this->dealershipModel->create($request->all());

        return redirect()->route('admin.dealerships.index');
    }

    public function edit(int $id)
    {
        $dealership = $this->dealershipModel->findOrFail($id);

        return Inertia::render('Admin/Dealerships/Edit', [
            'dealership' => $dealership,
        ]);
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $user = $this->dealershipModel->find($id);

        $user->update([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone_number' => $request->get('phone_number'),
        ]);

        return redirect()->route('admin.dealerships.index');
    }
}
