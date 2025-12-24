<?php

namespace Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use Subscriptions\Models\Invoice;
use Illuminate\Http\Request;

/**
 * Invoice Controller
 */
class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['user', 'subscription.plan']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(25);
        $statuses = config('subscriptions.invoice_statuses', []);

        return view('subscriptions::invoices.index', compact('invoices', 'statuses'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'subscription.plan']);

        return view('subscriptions::invoices.show', compact('invoice'));
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Invoice $invoice)
    {
        $invoice->markAsPaid();

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid.',
            'data' => $invoice,
        ]);
    }

    /**
     * Send invoice to customer.
     */
    public function send(Invoice $invoice)
    {
        // Send invoice email logic here
        // Mail::to($invoice->user)->send(new InvoiceMail($invoice));

        return response()->json([
            'success' => true,
            'message' => 'Invoice sent successfully.',
        ]);
    }

    /**
     * Download invoice as PDF.
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['user', 'subscription.plan']);

        // Generate PDF logic here
        // For now, return the invoice view
        return view('subscriptions::invoices.pdf', compact('invoice'));
    }
}

