<?php

namespace App\Services\POS;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreTime
{
    public function timezone(): string
    {
        return Setting::current()->timezone ?: 'Asia/Jakarta';
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function range(string $preset, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = $this->now();

        [$localStart, $localEnd] = match ($preset) {
            'today' => [$now->startOfDay(), $now->addDay()->startOfDay()],
            'yesterday' => [$now->subDay()->startOfDay(), $now->startOfDay()],
            '7days' => [$now->subDays(6)->startOfDay(), $now->addDay()->startOfDay()],
            'custom' => [
                CarbonImmutable::parse((string) $startDate, $this->timezone())->startOfDay(),
                CarbonImmutable::parse((string) $endDate, $this->timezone())->addDay()->startOfDay(),
            ],
            default => [$now->startOfMonth(), $now->addMonthNoOverflow()->startOfMonth()],
        };

        return [$localStart->utc(), $localEnd->utc()];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable}|null */
    public function rangeFromRequest(Request $request, string $default = 'all'): ?array
    {
        $validated = $request->validate([
            'date_preset' => ['nullable', Rule::in(['all', 'today', 'yesterday', '7days', 'month', 'custom'])],
            'start_date' => ['nullable', 'required_if:date_preset,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:date_preset,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $preset = $validated['date_preset'] ?? $default;

        if ($preset === 'all') {
            return null;
        }

        return $this->range($preset, $validated['start_date'] ?? null, $validated['end_date'] ?? null);
    }

    public function localDate(CarbonImmutable $utcDate): string
    {
        return $utcDate->setTimezone($this->timezone())->format('Y-m-d');
    }
}
