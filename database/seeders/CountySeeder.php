<?php

namespace Database\Seeders;

use App\Models\County;
use Illuminate\Database\Seeder;

class CountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $counties = [
            ['county' => 'Mombasa', 'county_code' => '001'],
            ['county' => 'Kwale', 'county_code' => '002'],
            ['county' => 'Kilifi', 'county_code' => '003'],
            ['county' => 'Tana River', 'county_code' => '004'],
            ['county' => 'Lamu', 'county_code' => '005'],
            ['county' => 'Taita-Taveta', 'county_code' => '006'],
            ['county' => 'Garissa', 'county_code' => '007'],
            ['county' => 'Wajir', 'county_code' => '008'],
            ['county' => 'Mandera', 'county_code' => '009'],
            ['county' => 'Marsabit', 'county_code' => '010'],
            ['county' => 'Isiolo', 'county_code' => '011'],
            ['county' => 'Meru', 'county_code' => '012'],
            ['county' => 'Tharaka-Nithi', 'county_code' => '013'],
            ['county' => 'Embu', 'county_code' => '014'],
            ['county' => 'Kitui', 'county_code' => '015'],
            ['county' => 'Machakos', 'county_code' => '016'],
            ['county' => 'Makueni', 'county_code' => '017'],
            ['county' => 'Nyandarua', 'county_code' => '018'],
            ['county' => 'Nyeri', 'county_code' => '019'],
            ['county' => 'Kirinyaga', 'county_code' => '020'],
            ['county' => 'Murang\'a', 'county_code' => '021'],
            ['county' => 'Kiambu', 'county_code' => '022'],
            ['county' => 'Turkana', 'county_code' => '023'],
            ['county' => 'West Pokot', 'county_code' => '024'],
            ['county' => 'Samburu', 'county_code' => '025'],
            ['county' => 'Trans-Nzoia', 'county_code' => '026'],
            ['county' => 'Uasin Gishu', 'county_code' => '027'],
            ['county' => 'Elgeyo-Marakwet', 'county_code' => '028'],
            ['county' => 'Nandi', 'county_code' => '029'],
            ['county' => 'Baringo', 'county_code' => '030'],
            ['county' => 'Laikipia', 'county_code' => '031'],
            ['county' => 'Nakuru', 'county_code' => '032'],
            ['county' => 'Narok', 'county_code' => '033'],
            ['county' => 'Kajiado', 'county_code' => '034'],
            ['county' => 'Kericho', 'county_code' => '035'],
            ['county' => 'Bomet', 'county_code' => '036'],
            ['county' => 'Kakamega', 'county_code' => '037'],
            ['county' => 'Vihiga', 'county_code' => '038'],
            ['county' => 'Bungoma', 'county_code' => '039'],
            ['county' => 'Busia', 'county_code' => '040'],
            ['county' => 'Siaya', 'county_code' => '041'],
            ['county' => 'Kisumu', 'county_code' => '042'],
            ['county' => 'Homa Bay', 'county_code' => '043'],
            ['county' => 'Migori', 'county_code' => '044'],
            ['county' => 'Kisii', 'county_code' => '045'],
            ['county' => 'Nyamira', 'county_code' => '046'],
            ['county' => 'Nairobi', 'county_code' => '047'],
        ];

        foreach ($counties as $county) {
            County::create($county);
        }
    }
}
