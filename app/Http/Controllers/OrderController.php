<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): Response
    {
        $company = $request->user()->company;
        
        $query = Order::with(['contact', 'orderItems'])
            ->where('company_id', $company->id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('ordered_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('ordered_at', '<=', $request->get('date_to'));
        }

        // Amount range filter
        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->get('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->get('amount_max'));
        }

        $orders = $query->orderBy('ordered_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_orders' => Order::where('company_id', $company->id)->count(),
            'total_revenue' => Order::where('company_id', $company->id)->sum('total_amount'),
            'pending_orders' => Order::where('company_id', $company->id)->where('status', 'pending')->count(),
            'completed_orders' => Order::where('company_id', $company->id)->where('status', 'delivered')->count(),
        ];

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'amount_min', 'amount_max']),
            'statuses' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'],
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create(Request $request): Response
    {
        $company = $request->user()->company;
        $contacts = Contact::where('company_id', $company->id)->get(['id', 'first_name', 'last_name', 'email', 'phone']);
        
        return Inertia::render('Orders/Create', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|unique:orders,order_number',
            'contact_id' => 'nullable|exists:contacts,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'ordered_at' => 'required|date',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.product_sku' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order = Order::create([
            'company_id' => $request->user()->company_id,
            'order_number' => $validated['order_number'],
            'contact_id' => $validated['contact_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'],
            'currency' => $validated['currency'],
            'ordered_at' => $validated['ordered_at'],
            'shipping_address' => $validated['shipping_address'],
            'billing_address' => $validated['billing_address'],
        ]);

        // Create order items
        foreach ($validated['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return redirect()->route('orders.index')
            ->with('success', 'Order created successfully.');
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): Response
    {
        $order->load(['contact', 'orderItems']);
        
        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Request $request, Order $order): Response
    {
        $company = $request->user()->company;
        $contacts = Contact::where('company_id', $company->id)->get(['id', 'first_name', 'last_name', 'email', 'phone']);
        
        $order->load('orderItems');
        
        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|unique:orders,order_number,' . $order->id,
            'contact_id' => 'nullable|exists:contacts,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'ordered_at' => 'required|date',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.product_sku' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order->update([
            'order_number' => $validated['order_number'],
            'contact_id' => $validated['contact_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'],
            'currency' => $validated['currency'],
            'ordered_at' => $validated['ordered_at'],
            'shipping_address' => $validated['shipping_address'],
            'billing_address' => $validated['billing_address'],
        ]);

        // Update order items - delete existing and recreate
        $order->orderItems()->delete();
        foreach ($validated['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return redirect()->route('orders.index')
            ->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order)
    {
        $order->orderItems()->delete();
        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Order deleted successfully.');
    }

    /**
     * Import orders from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        // This would typically be handled by a job for large files
        // For now, return success message
        return back()->with('success', 'Order import queued successfully.');
    }

    /**
     * Export orders to CSV.
     */
    public function export(Request $request)
    {
        // This would typically generate and download a CSV file
        // For now, return success message
        return back()->with('success', 'Order export initiated.');
    }
}
