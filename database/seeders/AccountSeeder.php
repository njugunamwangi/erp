<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            'name' => 'NCBA BANK',
            'number' => 5758560017,
            'bank_name' => 'NCBA BANK KENYA PLC',
            'bic_swift_code' => 'CBAFKENX',
            'iban' => 07,
            'currency_id' => 80,
            'enabled' => true,
        ]);
    }
}
