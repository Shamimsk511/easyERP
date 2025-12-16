<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Unit;

class UnitConversionService
{
    /**
     * Rounding threshold - values >= this round up, below rounds down
     */
    protected const ROUNDING_THRESHOLD = 0.8;

    /**
     * Convert base quantity to alternative unit breakdown
     * 
     * Example: 110 sft with:
     * - Pieces (pcs): 1 pcs = 0.6670 sft
     * - Box: 1 box = 16.6670 sft (25 pcs)
     * 
     * Returns: ['boxes' => 6, 'pieces' => 15, 'display' => '6 box + 15 pcs']
     * 
     * @param float $baseQty Quantity in base unit
     * @param Product $product
     * @return array{display: string, boxes: float, pieces: float, breakdown: array, base_qty: float}
     */
    public function convertToAlternativeUnits(float $baseQty, Product $product): array
    {
        $product->load(['baseUnit', 'alternativeUnits']);
        
        $baseSymbol = $product->baseUnit?->symbol ?? 'unit';
        
        $result = [
            'display' => number_format($baseQty, 2) . ' ' . $baseSymbol,
            'boxes' => 0,
            'pieces' => 0,
            'breakdown' => [],
            'base_qty' => $baseQty,
        ];

        if ($baseQty <= 0) {
            return $result;
        }

        $altUnits = $product->alternativeUnits
            ->sortByDesc(fn($u) => $u->pivot->conversion_factor);

        if ($altUnits->isEmpty()) {
            return $result;
        }

        $remaining = $baseQty;
        $breakdown = [];

        foreach ($altUnits as $unit) {
            $factor = (float) $unit->pivot->conversion_factor;
            
            if ($factor <= 0 || $remaining < $factor * 0.1) {
                continue;
            }

            $rawQty = $remaining / $factor;
            $wholeQty = $this->applyRounding($rawQty);

            if ($wholeQty > 0) {
                $symbolLower = strtolower($unit->symbol);
                $isBox = str_contains($symbolLower, 'box') || 
                         str_contains($symbolLower, 'ctn') ||
                         str_contains($symbolLower, 'carton') ||
                         $factor >= 10;

                $breakdown[] = [
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'unit_symbol' => $unit->symbol,
                    'quantity' => $wholeQty,
                    'conversion_factor' => $factor,
                    'is_box' => $isBox,
                ];

                if ($isBox) {
                    $result['boxes'] = $wholeQty;
                } else {
                    $result['pieces'] = $wholeQty;
                }

                $remaining -= ($wholeQty * $factor);
            }
        }

        // Handle remaining base units with rounding
        if ($remaining > 0.001) {
            $roundedRemainder = $this->applyRounding($remaining);
            
            if ($roundedRemainder > 0) {
                $breakdown[] = [
                    'unit_id' => $product->base_unit_id,
                    'unit_name' => $product->baseUnit->name ?? 'Base',
                    'unit_symbol' => $baseSymbol,
                    'quantity' => $roundedRemainder,
                    'conversion_factor' => 1,
                    'is_base' => true,
                ];
            }
        }

        $result['breakdown'] = $breakdown;
        $result['display'] = $this->formatBreakdown($breakdown);

        return $result;
    }

    /**
     * Apply the 0.8 rounding rule
     * >= 0.8 fractional part rounds up, otherwise floors
     */
    protected function applyRounding(float $value): int
    {
        $fractional = $value - floor($value);
        return $fractional >= self::ROUNDING_THRESHOLD
            ? (int) ceil($value)
            : (int) floor($value);
    }

    /**
     * Format breakdown array to display string
     */
    protected function formatBreakdown(array $breakdown): string
    {
        if (empty($breakdown)) {
            return '0';
        }

        $parts = [];
        foreach ($breakdown as $item) {
            $qty = is_int($item['quantity']) 
                ? $item['quantity'] 
                : number_format($item['quantity'], 2);
            $parts[] = $qty . ' ' . $item['unit_symbol'];
        }

        return implode(' + ', $parts);
    }

    /**
     * Convert from alternative unit back to base unit
     */
    public function convertToBaseUnit(float $qty, int $unitId, Product $product): float
    {
        if ($unitId === $product->base_unit_id) {
            return $qty;
        }

        $altUnit = $product->alternativeUnits()
            ->where('unit_id', $unitId)
            ->first();

        if (!$altUnit) {
            return $qty;
        }

        return $qty * $altUnit->pivot->conversion_factor;
    }

    /**
     * Get all available units for a product
     */
    public function getProductUnits(Product $product): array
    {
        $product->load(['baseUnit', 'alternativeUnits']);

        $units = [];

        // Add base unit first
        if ($product->baseUnit) {
            $units[] = [
                'id' => $product->base_unit_id,
                'name' => $product->baseUnit->name,
                'symbol' => $product->baseUnit->symbol,
                'conversion_factor' => 1,
                'is_base' => true,
            ];
        }

        // Add alternative units
        foreach ($product->alternativeUnits as $unit) {
            $units[] = [
                'id' => $unit->id,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'conversion_factor' => $unit->pivot->conversion_factor,
                'is_base' => false,
                'is_sales_unit' => $unit->pivot->is_sales_unit,
                'is_purchase_unit' => $unit->pivot->is_purchase_unit,
            ];
        }

        return $units;
    }

    /**
     * Calculate display for invoice item quantity
     * 
     * @param float $baseQty Quantity in base unit (sft)
     * @param Product $product
     * @return string e.g., "6 box + 15 pcs"
     */
    public function getDisplayForQty(float $baseQty, Product $product): string
    {
        $result = $this->convertToAlternativeUnits($baseQty, $product);
        return $result['display'];
    }

    /**
     * Calculate quantity breakdown for real-time display
     */
    public function calculateForDisplay(float $qty, int $unitId, Product $product): array
    {
        // First convert to base unit
        $baseQty = $this->convertToBaseUnit($qty, $unitId, $product);
        
        // Then get breakdown
        return $this->convertToAlternativeUnits($baseQty, $product);
    }
}