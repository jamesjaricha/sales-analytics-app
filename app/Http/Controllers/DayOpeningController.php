<?php

namespace App\Http\Controllers;

use App\Models\DayOpening;
use App\Services\DayEndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DayOpeningController extends Controller
{
    public function __construct(private readonly DayEndService $dayEnd) {}

    /**
     * Start-of-day prompt: capture the cash already in the drawer (balance
     * brought forward) before any sales are recorded.
     */
    public function create()
    {
        $today = now()->toDateString();

        // A locked day cannot be (re)opened
        if ($this->dayEnd->alreadyApproved($today)) {
            return redirect()->route('pos.create');
        }

        return view('day-end.open', [
            'businessDate' => $today,
            'opening' => DayOpening::forDate($today),
        ]);
    }

    /**
     * Save (or correct) today's balance brought forward.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);

        $today = now()->toDateString();

        if ($this->dayEnd->alreadyApproved($today)) {
            return redirect()->route('pos.create');
        }

        $opening = DayOpening::forDate($today) ?? new DayOpening(['business_date' => $today]);
        $opening->opening_balance = (float) $validated['opening_balance'];
        $opening->opened_by = Auth::id();
        $opening->save();

        return redirect()->route('pos.create')->with(
            'success',
            'Day opened — balance brought forward ZMW '.number_format((float) $validated['opening_balance'], 2),
        );
    }
}
