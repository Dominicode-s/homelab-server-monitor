<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Alert;
use App\Models\AlertRule;

class AlertController extends Controller
{
    public function index(): View
    {
        $alerts = Alert::with(['server', 'rule'])
            ->orderBy('triggered_at', 'desc')
            ->paginate(20);

        $rules = AlertRule::all();

        return view('alerts.index', compact('alerts', 'rules'));
    }

    public function show(Alert $alert): View
    {
        $alert->load(['server', 'rule']);

        return view('alerts.show', compact('alert'));
    }

    public function resolve(Alert $alert)
    {
        if ($alert->state === 'active') {
            $alert->resolve();
        }

        return back()->with('success', 'Alert resolved.');
    }
}
