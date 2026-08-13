<x-admin-layout title="Official Receipts">
    <div class="finance-page mx-auto max-w-[1450px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Official Receipts', 'subtitle' => 'Permanent online and onsite receipts. Reversals retain the original OR number and record.'])
        <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_200px_auto]"><input name="q" value="{{ request('q') }}" placeholder="Search OR, transaction, or family" class="rounded-xl border-slate-300 text-sm"><select name="status" class="rounded-xl border-slate-300 text-sm"><option value="">All statuses</option><option value="ISSUED" @selected(request('status')==='ISSUED')>Issued</option><option value="REVERSED" @selected(request('status')==='REVERSED')>Reversed</option></select><button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Filter</button></form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Official receipt</th><th class="px-5 py-3">Family</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($receipts as $receipt)
                            <tr>
                                <td data-label="Official receipt" class="px-5 py-4"><p class="font-extrabold text-slate-900">{{ $receipt->official_receipt_number }}</p><p class="text-xs text-slate-500">{{ $receipt->issued_at?->format('M d, Y g:i A') }}<br>{{ $receipt->transaction?->transaction_number }}</p></td>
                                <td data-label="Family" class="px-5 py-4"><p class="font-bold text-slate-800">{{ $receipt->transaction?->family?->name }}</p><p class="text-xs text-slate-500">{{ $receipt->transaction?->family?->email }}</p></td>
                                <td data-label="Payment" class="px-5 py-4"><p class="font-extrabold text-slate-900">₱{{ number_format((float)$receipt->transaction?->amount,2) }}</p><div class="mt-1 flex flex-wrap gap-1"><span @class(['rounded-full px-2 py-0.5 text-[11px] font-bold','bg-sky-50 text-sky-700'=>$receipt->transaction?->source==='ONLINE','bg-amber-50 text-amber-700'=>$receipt->transaction?->source==='ONSITE'])>{{ $receipt->transaction?->payment_source_label }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $receipt->transaction?->payment_method_label }}</span></div></td>
                                <td data-label="Status" class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold','bg-emerald-100 text-emerald-700'=>$receipt->status==='ISSUED','bg-rose-100 text-rose-700'=>$receipt->status==='REVERSED'])>{{ $receipt->status }}</span></td>
                                <td data-label="Action" class="px-5 py-4 text-right"><a href="{{ route('admin.finance.receipts.show',$receipt) }}" class="font-bold text-emerald-700">View →</a></td>
                            </tr>
                        @empty
                            <tr><td data-label="" colspan="5" class="p-12 text-center text-slate-500">No official receipts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $receipts->links() }}</div>
        </div>
    </div>
</x-admin-layout>
