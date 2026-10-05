<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\TiktokAccount;
use App\Models\TiktokOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NoUplineAssignmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'creator_username_normalized' => ['required', 'string', 'max:255'],
            'creator_username' => ['nullable', 'string', 'max:255'],
            'affiliate_id' => ['required', 'integer', 'exists:affiliates,id'],
        ]);

        $normalizedUsername = strtolower(trim(str_replace('@', '', $data['creator_username_normalized'])));
        $displayUsername = $data['creator_username'] ?: $normalizedUsername;
        $affiliate = Affiliate::findOrFail($data['affiliate_id']);

        $result = DB::transaction(function () use ($normalizedUsername, $displayUsername, $affiliate) {
            $account = TiktokAccount::query()
                ->where('username_normalized', $normalizedUsername)
                ->first();

            if ($account) {
                $account->update([
                    'affiliate_id' => $affiliate->id,
                    'status' => 'active',
                ]);
            } else {
                $account = TiktokAccount::create([
                    'affiliate_id' => $affiliate->id,
                    'username' => $displayUsername,
                    'username_normalized' => $normalizedUsername,
                    'status' => 'active',
                ]);
            }

            $backfilledOrders = TiktokOrder::query()
                ->where('creator_username_normalized', $normalizedUsername)
                ->where('sales_source', 'no_upline')
                ->update([
                    'affiliate_id' => $affiliate->id,
                    'sales_source' => 'mapped_affiliate',
                ]);

            return [
                'account' => $account,
                'backfilled_orders' => $backfilledOrders,
            ];
        });

        Log::notice('No-upline TikTok username assigned to affiliate by admin.', [
            'admin_user_id' => $request->user()->id,
            'username_normalized' => $normalizedUsername,
            'affiliate_id' => $affiliate->id,
            'affiliate_code' => $affiliate->affiliate_code,
            'backfilled_orders' => $result['backfilled_orders'],
        ]);

        return back()->with('success', sprintf(
            '@%s telah dikaitkan dengan %s (%s). %d order sejarah dikemaskini kepada affiliate ini. Jalankan semula Commission Run untuk period berkaitan supaya komisen dikira.',
            $normalizedUsername,
            $affiliate->name,
            $affiliate->affiliate_code ?: '-',
            $result['backfilled_orders']
        ));
    }
}
