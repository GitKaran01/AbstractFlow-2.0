<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearcherStatusSeeder extends Seeder {
    public function run(): void {
        $statuses = [
            'Additional Search', 'Abstractor', 'Address not found', 'As per Client Info',
            'Bid Rejected / Cancelled', 'Upgrade to CO', 'Bid Request', 'Borrower not found',
            'Cancelled', 'Clarification', 'Completed', 'Escalated to Client',
            'Fee Approval Pending', 'Fee Approved', 'Filing Only', 'Hold',
            'Hold for Fee Approval', 'In Progress', 'Pending', 'QC',
            'Quote Request', 'Recheck', 'Recording'
        ];

        foreach ($statuses as $status) {
            DB::table('searcher_statuses')->updateOrInsert(
                ['slug' => Str::slug($status, '_')], // e.g. "address_not_found"
                ['name' => $status, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}