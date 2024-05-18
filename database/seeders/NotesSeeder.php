<?php

namespace Database\Seeders;

use App\Models\Note;
use Illuminate\Database\Seeder;

class NotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Note::create([
            'quotes' => '<ol><li>All Prices are in Kenya shillings</li><li>50% deposit is mandatory before commence of the works</li><li>All required permits must be acquired before commence of works</li></ol>',
            'invoices' => '<ol><li>All Prices are in Kenya shillings</li><li>50% deposit is mandatory before commence of the works</li><li>All required permits must be acquired before commence of works</li></ol>',
        ]);
    }
}
