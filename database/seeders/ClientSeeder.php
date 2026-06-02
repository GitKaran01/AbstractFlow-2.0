<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder {
    public function run(): void {
        // 🔥 कुल 33 एकदम सटीक और पूरे क्लाइंट्स की लिस्ट
        $clients = [
            'Accurate Group', 
            'AMERISTAR', 
            'App Orders_SitusAMC', 
            'Bob Mackall _ JC',
            'Data-Search', 
            'Orchestrate', 
            'Easydocs123', 
            'Email Orders_SitusAMC',
            'GreyBeard Title LLC', 
            'Honor Title Search LLC', 
            'Sherri Adams', 
            'I2U Systems',
            '51titleandtaxesllc', 
            'Precision Abstract & Title Services', 
            'JC', 
            'Kidder Law',
            'TM - In Office', 
            'Mortgage Connect', 
            'Mortiles LLC', 
            'New Client',
            'Dola', 
            'ORT', 
            'Real T Solutions', 
            'Rocket Title LLC', 
            'Service Link',
            'Shuler Killen Law Firm', 
            'Dave _ JC', 
            'Reliabledocs', 
            'Snappy Abstract',
            'SSE Project _ JC', 
            'Townsgate Closing Services LLC', 
            'Vistroinfo Solutions', 
            'Commercial'
        ];

        foreach ($clients as $client) {
            DB::table('clients')->updateOrInsert(
                ['name' => $client], 
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}