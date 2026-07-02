<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\DayOpening;
use App\Models\Sale;
use App\Services\DayEndService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly DayEndService $dayEnd,
    ) {}

    /**
     * The POS screen + today's invoices.
     */
    public function create()
    {
        $today = now()->toDateString();

        // First sign-in of the trading day: capture the balance brought
        // forward before any sales are recorded. (Skip once the day is locked
        // — the open screen would bounce straight back here.)
        $opening = DayOpening::forDate($today);
        if (! $opening && ! $this->dayEnd->alreadyApproved($today)) {
            return redirect()->route('day.open');
        }

        $invoices = Sale::with('user')
            ->completed()
            ->forDate($today)
            ->latest()
            ->get();

        return view('pos.create', [
            'businessDate' => $today,
            'paymentMethods' => PaymentMethod::options(),
            'invoices' => $invoices,
            'opening' => $opening,
        ]);
    }

    /**
     * Record a single POS invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_date' => ['nullable', 'date'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'customer_name' => ['nullable', 'required_if:payment_method,'.PaymentMethod::Credit->value, 'string', 'max:255'],
            'customer_phone' => ['nullable', 'required_if:payment_method,'.PaymentMethod::Credit->value, 'string', 'max:30'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_via' => ['nullable', 'in:cash,bank,mobile_money'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Add at least one item to the invoice.',
            'customer_name.required_if' => 'A customer name is required for credit (outstanding debt) sales.',
            'customer_phone.required_if' => 'A customer phone number is required for credit (outstanding debt) sales.',
        ]);

        $sale = $this->sales->record($validated, Auth::user());

        return redirect()->route('pos.create')->with(
            'success',
            "Invoice {$sale->reference} recorded — ZMW ".number_format((float) $sale->total_amount, 2),
        );
    }

    /**
     * Void an unreconciled invoice.
     */
    public function void(Sale $sale)
    {
        $this->sales->void($sale);

        return back()->with('success', "Invoice {$sale->reference} voided.");
    }
}
