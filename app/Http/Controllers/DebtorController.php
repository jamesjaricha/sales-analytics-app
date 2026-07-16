<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtorController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    /**
     * Clients with outstanding debts: every unpaid (or partly paid) credit
     * or split invoice, grouped by customer, with who recorded it and when.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $invoices = Sale::with(['user', 'payments.receivedBy', 'salePayments'])
            ->completed()
            ->whereIn('payment_method', [PaymentMethod::Credit->value, PaymentMethod::Split->value])
            ->where('amount_due', '>', 0)
            ->when($search !== '', fn ($q) => $q->where('customer_name', 'like', "%{$search}%"))
            ->orderByDesc('business_date')
            ->orderByDesc('id')
            ->get();

        $debtors = $invoices
            ->groupBy(fn (Sale $s) => mb_strtolower(trim((string) $s->customer_name)))
            ->map(fn ($group) => [
                'name' => $group->first()->customer_name ?: 'Unknown customer',
                'total_due' => (float) $group->sum(fn (Sale $s) => (float) $s->amount_due),
                'invoices' => $group,
            ])
            ->sortByDesc('total_due')
            ->values();

        return view('debtors.index', [
            'debtors' => $debtors,
            'totalOutstanding' => (float) $invoices->sum(fn (Sale $s) => (float) $s->amount_due),
            'invoiceCount' => $invoices->count(),
            'search' => $search,
        ]);
    }

    /**
     * Record a repayment from a debtor against one of their credit invoices.
     */
    public function storePayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank,mobile_money'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $this->sales->receivePayment(
            $sale,
            (float) $validated['amount'],
            $validated['payment_method'],
            Auth::user(),
            $validated['note'] ?? null,
        );

        $remaining = (float) $sale->fresh()->amount_due;

        return redirect()->route('debtors.index')->with(
            'success',
            'Payment of ZMW '.number_format((float) $payment->amount, 2)." received against {$sale->reference}"
            .($remaining > 0 ? ' — ZMW '.number_format($remaining, 2).' still owing.' : ' — debt fully settled.'),
        );
    }
}
