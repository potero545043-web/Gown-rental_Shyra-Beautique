<?php

namespace App\Services;

use App\Models\Gown;
use Illuminate\Support\Collection;

/**
 * Session-backed gown cart used by customer, employee, and owner booking flows.
 *
 * Both roles keep their selection in the session so they can add several gowns
 * from the catalog and submit ONE reservation with a single agreement and
 * payment (Shopee/Lazada style). Customer and employee carts are kept in
 * separate session keys so their selections never bleed into each other.
 */
class ReservationCart
{
    public const SESSION_KEY = 'reservation_cart';
    public const STAFF_SESSION_KEY = 'staff_reservation_cart';
    public const OWNER_SESSION_KEY = 'owner_reservation_cart';

    /** Session key for a role so each account type has an independent cart. */
    public static function keyFor(string $role): string
    {
        return match ($role) {
            'customer' => self::SESSION_KEY,
            'employee' => self::STAFF_SESSION_KEY,
            'owner' => self::OWNER_SESSION_KEY,
            default => throw new \InvalidArgumentException('Unsupported reservation cart role.'),
        };
    }

    public function __construct(private string $key)
    {
    }

    /** Convenience factory so callers do not repeat the prefix rules. */
    public static function forRole(string $role): self
    {
        return new self(self::keyFor($role));
    }

    /** @return array<int, int> The deduplicated gown ids currently in the cart. */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', (array) session($this->key, []))));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function isEmpty(): bool
    {
        return $this->ids() === [];
    }

    /** Adds a gown once. Re-adding an already-present gown is a no-op. */
    public function add(int $gownId): void
    {
        $ids = $this->ids();
        if (!in_array($gownId, $ids, true)) {
            $ids[] = $gownId;
        }
        session([$this->key => $ids]);
    }

    public function remove(int $gownId): void
    {
        session([$this->key => array_values(array_diff($this->ids(), [$gownId]))]);
    }

    public function clear(): void
    {
        session()->forget($this->key);
    }

    public function has(int $gownId): bool
    {
        return in_array($gownId, $this->ids(), true);
    }

    /**
     * The cart's gowns, in the order they were added, excluding archived or
     * retired pieces so a stale id never reaches checkout.
     */
    public function gowns(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }

        return Gown::with('category')
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->where('status', '!=', 'retired')
            ->get()
            ->sortBy(fn(Gown $gown) => array_search($gown->id, $ids, true))
            ->values();
    }

    /** Combined rental price of every gown in the cart. */
    public function total(): float
    {
        return round($this->gowns()->sum(fn(Gown $gown) => (float) $gown->rental_price), 2);
    }
}
