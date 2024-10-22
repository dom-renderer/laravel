<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustmentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Stock;

class StockController extends Controller
{
    public function save(Request $request) {

        DB::beginTransaction();

        $date = date('Y-m-d', strtotime($request->date));

        if (!($date == date('Y-m-d') || $date == date('Y-m-d', strtotime('-1 day')))) {
            DB::rollBack();
            return response()->json(['error' => 'You can only alter stock of today or yesterday.']);
        }

        $store = 1;
        $requestUnitIds = $request->unit_id;
        $requestOpening = $request->opening;
        $requestClosing = $request->closing;

        if ($date == date('Y-m-d')) {
            /** Today **/

            foreach ($requestUnitIds as $index => $unitId) {

                $thisRequestOpeningStock = isset($requestOpening[$index]) ? $requestOpening[$index] : 0;
                $thisRequestClosingStock = isset($requestClosing[$index]) ? $requestClosing[$index] : 0;

                if (StockAdjustmentLog::where('is_delivery', false)->where('store_id', $store)->where('date', $date)->where('unit_id', $unitId)->exists()) {

                    $lastRecord = StockAdjustmentLog::where('store_id', $store)->where('date', $date)->where('unit_id', $unitId)->orderBy('id', 'DESC')->first();

                    /** Updating Today's Closing Stock **/

                    if ($thisRequestClosingStock > $lastRecord->closing_stock) {

                        $stockInventoryIn = Stock::where('store_id', $store)->where('unit_id', $unitId)->in()->sum('quantity');
                        $stockInventoryOut = Stock::where('store_id', $store)->where('unit_id', $unitId)->out()->sum('quantity');
                        $totalInventoryStock = $stockInventoryIn - $stockInventoryOut;

                        if ($totalInventoryStock > 0 && $totalInventoryStock >= $thisRequestClosingStock) {
                            StockAdjustmentLog::create([
                                'unit_id' => $unitId,
                                'store_id' => $store,
                                'opening_stock' => $totalInventoryStock,
                                'closing_stock' => $thisRequestClosingStock,
                                'sold_stock' => self::totalSale($totalInventoryStock, $thisRequestClosingStock),
                                'date' => $date
                            ]);

                            /** Stock addition **/

                            Stock::create([
                                'store_id' => $store,
                                'unit_id' => $unitId,
                                'type' => 0,
                                'quantity' => $thisRequestClosingStock
                            ]);

                            /** Stock addition **/
                        } else {
                            DB::rollBack();
                            return response()->json(['error' => 'You don\'t have enough stock for product : ']);
                        }

                    } else if ($thisRequestClosingStock < $lastRecord->closing_stock) {

                        StockAdjustmentLog::create([
                            'unit_id' => $unitId,
                            'store_id' => $store,
                            'opening_stock' => $lastRecord->closing_stock,
                            'closing_stock' => $thisRequestClosingStock,
                            'sold_stock' => self::totalSale($lastRecord->closing_stock, $thisRequestClosingStock),
                            'date' => $date
                        ]);

                        /** Stock deduction **/

                        Stock::create([
                            'store_id' => $store,
                            'unit_id' => $unitId,
                            'type' => 1,
                            'quantity' => $lastRecord->closing_stock - $thisRequestClosingStock
                        ]);

                        /** Stock deduction **/

                    }

                    /** Updating Today's Closing Stock **/

                } else {

                    if ($thisRequestOpeningStock == $thisRequestClosingStock) {

                        /** Opening Stock **/

                        $stockAddedFromDelivery = 0;

                        if (StockAdjustmentLog::where('is_delivery', false)->where('store_id', $store)->where('date', $date)->where('unit_id', $unitId)->exists()) {

                        }
                        
                        StockAdjustmentLog::create([
                            'unit_id' => $unitId,
                            'store_id' => $store,
                            'opening_stock' => (isset($requestOpening[$index]) ? $requestOpening[$index] : 0) + $stockAddedFromDelivery,
                            'closing_stock' => (isset($requestOpening[$index]) ? $requestOpening[$index] : 0) + $stockAddedFromDelivery
                        ]);

                        /** Opening Stock **/

                    } else {

                    }

                }
            }

            /** Today **/
        } else if ($date == date('Y-m-d', strtotime('-1 day'))) {
            /** Yesterday **/



            /** Yesterday **/
        }
    }

    public static function totalSale($opening, $closing) {
        if ($opening == $closing) {
            return $closing;
        }

        return $opening - $closing;
    }
}
