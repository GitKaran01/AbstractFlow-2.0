<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect(Auth::user()->role === 'admin' ? '/admin/dashboard' : '/dashboard');
        }
        return view('login');
    }

    public function adminDashboard() {
        // 1. Existing functionality (Tickets and Abstractors list)
        $tickets = Ticket::with('assignedUser')->orderBy('id', 'desc')->get();
        $abstractors = User::where('role', 'abstractor')->orderBy('id', 'desc')->get();
        
        // 2. Fetching Clients & Products directly from database tables
        $dbClients = DB::table('clients')->orderBy('name', 'asc')->get();
        $dbProducts = DB::table('products')->orderBy('name', 'asc')->get();

        // Passing all compact variables safely to the blade view without breaking anything
        return view('admin_dashboard', compact('tickets', 'abstractors', 'dbClients', 'dbProducts'));
    }

    public function abstractorDashboard()
    {
        $myTickets = Ticket::where('assigned_user_id', Auth::id())
            ->with('activities')
            ->orderBy('due_date', 'asc')
            ->get();

        // 🔥 NEW: Fetching all dynamic searcher statuses directly from the database table
        $availableStatuses = DB::table('searcher_statuses')->orderBy('name', 'asc')->get();

        return view('dashboard', compact('myTickets', 'availableStatuses'));
    }

    // 1. REGISTRATION METHOD FOR ADMIN PANEL
    public function createAbstractor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'raw_password' => $request->password,
            'role' => 'abstractor',
            'status' => true
        ]);

        return back();
    }

    // 2. INITIAL ACCEPT TRIGGER
    public function acceptTask($id)
    {
        $ticket = Ticket::where('id', $id)->where('assigned_user_id', Auth::id())->firstOrFail();
        $ticket->update(['status' => 'in_progress']);

        $ticket->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'status_change',
            'note' => '🚀 Accepted: Abstractor initialized workspace profile. Status set to In-Progress.'
        ]);

        return back();
    }

    // 3. DROPDOWN STATUS SYNCHRONIZER (🔥 UPDATED: DYNAMIC LOOKUPS OVER TABLE SLUGS)
    public function updateStatusDropdown(Request $request, $id)
    {
        // 🔥 NEW: Validates existence directly inside searcher_statuses table
        $request->validate([
            'status' => 'required|exists:searcher_statuses,slug'
        ]);

        $ticket = Ticket::where('id', $id)->where('assigned_user_id', Auth::id())->firstOrFail();

        $ticket->update(['status' => $request->status]);

        // Fetch original clean display text label from database metadata table
        $statusRow = DB::table('searcher_statuses')->where('slug', $request->status)->first();
        $displayStatus = $statusRow ? $statusRow->name : ucfirst($request->status);

        $ticket->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'status_change',
            'note' => '📈 Searcher Status shifted to: ' . $displayStatus
        ]);

        return back();
    }

    // 4. HALT TASK BLOCK ENGINE
    public function haltTask(Request $request, $id)
    {
        $request->validate(['note' => 'required|string']);
        $ticket = Ticket::where('id', $id)->where('assigned_user_id', Auth::id())->firstOrFail();

        $ticket->update(['status' => 'stalled']);

        $ticket->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'escalation',
            'note' => '🚨 HALTED EXHAUSTION: ' . $request->note
        ]);

        return back();
    }

    // 5. TIMELINE MANUAL NOTE POSTER
    public function logActivity(Request $request, $id)
    {
        $request->validate(['note' => 'required|string']);
        $ticket = Ticket::where('id', $id)->where('assigned_user_id', Auth::id())->firstOrFail();

        $ticket->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'comment',
            'note' => '📝 ' . $request->note
        ]);

        return back();
    }

    // 6. MASTER 15MB DOCUMENT DISPATCHER
    public function submitReport(Request $request, $id)
    {
        $request->validate([
            'search_date' => 'required|date',
            'final_report' => 'required|mimes:pdf|max:15360'
        ]);

        $ticket = Ticket::where('id', $id)->where('assigned_user_id', Auth::id())->firstOrFail();

        if ($request->hasFile('final_report')) {
            $path = $request->file('final_report')->store('reports', 'public');

            $ticket->update([
                'status' => 'submitted_qc',
                'pdf_path' => $path
            ]);

            $ticket->activities()->create([
                'user_id' => Auth::id(),
                'activity_type' => 'submission',
                'note' => '📤 Uploaded final title documentation. Dispatched package to Admin Quality Control.'
            ]);
        }

        return back();
    }

    // 7. AJAX MODAL API SOURCE VIEW NOTE LOGS
    public function getActivities($id)
    {
        $activities = DB::table('ticket_activities')
            ->where('ticket_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($activities);
    }

    // 8. TASK REASSIGNMENT CONTROL PIPELINE
    public function reassignTask(Request $request, $id)
    {
        $request->validate([
            'assigned_user_id' => 'required|exists:users,id'
        ]);

        $ticket = Ticket::findOrFail($id);
        $newUser = User::findOrFail($request->assigned_user_id);

        $ticket->update([
            'assigned_user_id' => $request->assigned_user_id,
            'status' => 'assigned'
        ]);

        $ticket->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'reassignment',
            'note' => '♻️ Task Reassigned: Admin manually shifted file pipeline execution stack to: ' . $newUser->name
        ]);

        return back();
    }
}