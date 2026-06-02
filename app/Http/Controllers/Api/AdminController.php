<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller {

    // 🔥 ALIAS MAPPER: Syncs directly with admin users board endpoint
    public function syncAdminUsersBoard() {
        return $this->getAbstractors();
    }

    // 🔥 FIXED: Dynamic payload injector without database schema alteration
    public function getAbstractors() {
        try {
            // Selecting only existing valid schema columns
            $users = User::where('role', 'abstractor')
                ->select('id', 'name', 'email') 
                ->orderBy('name', 'asc')
                ->get();

            // 🔥 VIRTUAL KEY INJECTION: Appends 'raw_password' on the fly for Flutter models parsing safety
            $abstractors = $users->map(function($user) {
                return [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'raw_password' => 'Encrypted/Hidden' // Prevents mobile runtime mapping breaks
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active Abstractors workflow stream loaded successfully.',
                'count'   => $abstractors->count(),
                'data'    => $abstractors
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 MONITORING PANEL METHOD: Track active tracking workflow segments
    public function syncAdminMonitoringPanel() {
        try {
            $tickets = Ticket::orderBy('created_at', 'desc')->get();

            $formattedTickets = $tickets->map(function($ticket) {
                $assignedUser = User::find($ticket->assigned_user_id);

                return [
                    'id'               => $ticket->id,
                    'order_id'         => $ticket->order_id,
                    'client_name'      => $ticket->client_name,
                    'loan_number'      => $ticket->loan_number,
                    'product_type'     => $ticket->product_type,
                    'property_address' => $ticket->property_address,
                    'city'             => $ticket->city,
                    'state'            => $ticket->state,
                    'county'           => $ticket->county,
                    'parcel_id'        => $ticket->parcel_id,
                    'status'           => $ticket->status,
                    'total_cost'       => $ticket->total_cost,
                    'due_date'         => $ticket->due_date,
                    'pdf_path'         => $ticket->pdf_path ?? null,
                    'assigned_user'    => $assignedUser ? [
                        'id'   => $assignedUser->id,
                        'name' => $assignedUser->name
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active workflow management tickets stream loaded successfully.',
                'data'    => $formattedTickets
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
    // 🔥 CREATE NEW ABSTRACTOR ACCOUNT
    public function createUser(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'abstractor',
            'raw_password' => $request->password // Kept safe for UI credential verification views
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Abstractor account created successfully.', 
            'user' => $user
        ], 201);
    }

    // 🔥 SUBMIT NEW TICKET TO SYSTEM (Manual Intake Form Payload Router)
    public function createTicket(Request $request) {
        $validated = $request->validate([
            'order_id'         => 'required|string',
            'client_name'      => 'required|string',
            'loan_number'      => 'nullable|string|max:255', 
            'product_type'     => 'required|string',
            'property_address' => 'required|string',
            'city'             => 'required|string',
            'state'            => 'required|string',
            'county'           => 'required|string',
            'parcel_id'        => 'nullable|string|max:255',  
            'due_date'         => 'required',
            'borrower_name'    => 'required|string',
            'co_borrower_name' => 'nullable|string',
            'search_rate'      => 'nullable|numeric',
            'copy_rate'        => 'nullable|numeric',
            'total_cost'       => 'nullable|numeric',
            'assigned_user_id' => 'nullable|integer',
        ]);

        if (!empty($validated['assigned_user_id'])) {
            $validated['status'] = 'assigned';
        } else {
            $validated['status'] = 'unassigned';
        }

        $ticket = Ticket::create($validated);

        if ($ticket->assigned_user_id) {
            $ticket->activities()->create([
                'user_id' => auth()->id() ?? 1, 
                'note' => "Ticket manual setup complete. Initial assignment to abstractor ID: {$ticket->assigned_user_id}.",
                'activity_type' => 'system' 
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Ticket successfully created from Excel source data.', 
            'ticket' => $ticket
        ], 201);
    }

    // 🔥 REASSIGN STALLED TASK TO NEW ABSTRACTOR
    public function reassignTicket(Request $request, $id) {
        $request->validate([
            'assigned_user_id' => 'required|exists:users,id',
            'reason' => 'required|string'
        ]);

        $ticket = Ticket::findOrFail($id);
        $oldUser = $ticket->assignedUser ? $ticket->assignedUser->name : 'Unassigned';
        $newUser = User::findOrFail($request->assigned_user_id);

        $ticket->update([
            'assigned_user_id' => $request->assigned_user_id,
            'status' => 'assigned',
            'qc_notes' => $request->reason
        ]);

        $ticket->activities()->create([
            'user_id' => auth()->id() ?? 1,
            'note' => "Reassigned from {$oldUser} to {$newUser->name}. Reason: " . $request->reason,
            'activity_type' => 'system'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket successfully reassigned.'
        ]);
    }

    // 🔥 REVIEW PDF PACKET DISPATCH (QC Checklist Engine)
    public function reviewTicket(Request $request, $id) {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string'
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($request->action === 'approve') {
            $ticket->update(['status' => 'completed', 'qc_notes' => $request->notes]);
            $msg = "Ticket approved and completed.";
        } else {
            $ticket->update(['status' => 'assigned', 'qc_notes' => $request->notes]); 
            $msg = "Ticket rejected during QC check.";
        }

        $ticket->activities()->create([
            'user_id' => auth()->id() ?? 1,
            'note' => $msg . " Notes: " . $request->notes,
            'activity_type' => 'system'
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }








    /**
     * 📄 Streams the completed ticket PDF report directly to Flutter Client
     */
    public function streamTicketPdf($id)
    {
        try {
            // 1. Fetch ticket records from database mapping
            $ticket = Ticket::findOrFail($id);

            // 2. Check if the PDF path pointer actually exists
            if (!$ticket->pdf_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Abstractor has not submitted a PDF package for this file node yet.'
                ], 404);
            }

            // 3. Resolve the physical absolute system path indicator
            // Hum strictly verify karenge ki public disk par file physically mapped hai ya nahi
            if (!Storage::disk('public')->exists($ticket->pdf_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Physical file attachment missing from storage disk repository.'
                ], 404);
            }

            // 4. Extract raw file parameters string for HTTP mapping headers
            $filePath = Storage::disk('public')->path($ticket->pdf_path);
            $fileName = basename($filePath);
            
            // 5. 🔥 STREAM BINARY PACKAGES DIRECTLY (Bypasses traditional JSON parsing lags)
            return response()->file($filePath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Cache-Control'       => 'no-cache, private',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}