<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\PassiveIncomeItem;
use Illuminate\Database\Seeder;

class PassiveIncomeItemSeeder extends Seeder
{
    public function run(): void
    {
        // First, ensure we have the required accounts
        $otherIncomeAccount = Account::where('code', '4500')->first();
        
        if (!$otherIncomeAccount) {
            // Create Other Income account if not exists
            $revenueParent = Account::where('code', '4000')->first();
            $otherIncomeAccount = Account::create([
                'code' => '4500',
                'name' => 'Other Income',
                'type' => 'income',
                'description' => 'Non-operating income including labour and transportation charges',
                'parent_account_id' => $revenueParent?->id,
                'is_active' => true,
            ]);
        }

        // Create Labour Income Account (sub-account)
        $labourAccount = Account::firstOrCreate(
            ['code' => '4510'],
            [
                'name' => 'Labour Charges Income',
                'type' => 'income',
                'description' => 'Income from labour charges to customers',
                'parent_account_id' => $otherIncomeAccount->id,
                'is_active' => true,
            ]
        );

        // Create Transportation Income Account (sub-account)
        $transportAccount = Account::firstOrCreate(
            ['code' => '4520'],
            [
                'name' => 'Transportation Charges Income',
                'type' => 'income',
                'description' => 'Income from transportation/delivery charges to customers',
                'parent_account_id' => $otherIncomeAccount->id,
                'is_active' => true,
            ]
        );

        // Create Service Charges Account
        $serviceAccount = Account::firstOrCreate(
            ['code' => '4530'],
            [
                'name' => 'Service Charges Income',
                'type' => 'income',
                'description' => 'Income from miscellaneous service charges',
                'parent_account_id' => $otherIncomeAccount->id,
                'is_active' => true,
            ]
        );

        // Seed Passive Income Items
        $items = [
            [
                'name' => 'Labour Charges',
                'account_id' => $labourAccount->id,
                'description' => 'Charges for installation/fitting labour work',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation Charges',
                'account_id' => $transportAccount->id,
                'description' => 'Charges for delivery and transportation',
                'is_active' => true,
            ],
            [
                'name' => 'Loading/Unloading Charges',
                'account_id' => $labourAccount->id,
                'description' => 'Charges for loading and unloading goods',
                'is_active' => true,
            ],
            [
                'name' => 'Service Charges',
                'account_id' => $serviceAccount->id,
                'description' => 'Miscellaneous service charges',
                'is_active' => true,
            ],
            [
                'name' => 'Cutting Charges',
                'account_id' => $serviceAccount->id,
                'description' => 'Charges for tile/material cutting',
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            PassiveIncomeItem::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}