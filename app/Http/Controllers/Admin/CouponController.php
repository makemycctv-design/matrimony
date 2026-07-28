<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CouponRequest;
use App\Models\Coupon;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('coupons.view'), 403);

        return Inertia::render('admin/coupons/index', [
            'coupons' => Coupon::query()->latest('id')->get()->map(fn (Coupon $c) => [
                'uuid' => $c->uuid,
                'code' => $c->code,
                'type' => $c->type->value,
                'value' => $c->value,
                'value_display' => $c->type === CouponType::Percent ? $c->value.'%' : '₹'.number_format($c->value / 100, 2),
                'min_amount_paise' => $c->min_amount_paise,
                'max_redemptions' => $c->max_redemptions,
                'redeemed_count' => $c->redeemed_count,
                'per_user_limit' => $c->per_user_limit,
                'is_referral' => $c->is_referral,
                'is_active' => $c->is_active,
                'starts_at' => $c->starts_at?->toIso8601String(),
                'expires_at' => $c->expires_at?->toIso8601String(),
            ]),
            'canManage' => $request->user()->can('coupons.manage'),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        if (Coupon::where('code', strtoupper($request->input('code')))->exists()) {
            throw ValidationException::withMessages(['code' => 'That coupon code already exists.']);
        }

        $coupon = Coupon::create($request->toAttributes());
        $this->audit->log('coupon.created', $coupon, "Coupon {$coupon->code} created");

        return back()->with('success', 'Coupon created.');
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->toAttributes());
        $this->audit->log('coupon.updated', $coupon, "Coupon {$coupon->code} updated");

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        abort_unless($request->user()->can('coupons.manage'), 403);
        $coupon->delete();

        return back()->with('success', 'Coupon deactivated.');
    }
}
