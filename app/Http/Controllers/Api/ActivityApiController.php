<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ActivityApiController extends Controller
{
    /**
     * 👥 1. USER SIDE (Abstractor): Fetch all activities for a specific ticket
     */
    public function getTicketActivities($ticket_id)
    {
        try {
            // Checks if the ticket exists and belongs strictly to the logged-in abstractor
            $ticket = Ticket::where('id', $ticket_id)
                ->where('assigned_user_id', Auth::id())
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket resource not found or unauthorized access range.'
                ], 403);
            }

            // Fetch logs array cleanly
            $activities = $ticket->activities()->orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Ticket history logs stream synchronized cleanly.',
                'count'   => $activities->count(),
                'data'    => $activities
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👥 2. USER SIDE (Abstractor): Post/Send a new activity comment message node
     */
    public function postActivityComment(Request $request, $ticket_id)
    {
        try {
            $request->validate([
                'note' => 'required|string|max:1000'
            ]);

            $ticket = Ticket::where('id', $ticket_id)
                ->where('assigned_user_id', Auth::id())
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot post trace log. Ticket context missing or unauthorized.'
                ], 403);
            }

            // Injects comment directly using structural model traits
            $activity = $ticket->activities()->create([
                'user_id'       => Auth::id(),
                'activity_type' => 'comment',
                'note'          => '📝 ' . $request->note
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity timeline milestone saved successfully.',
                'data'    => $activity
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👑 3. ADMIN SIDE: Global activities logger view panel streams
     */
    /**
     * 👑 3. ADMIN SIDE: Global activities logger view panel streams
     */
    public function getGlobalAdminLogs()
    {
        try {
            // 🔥 FIXED: 'ticket_activities.type' select kiya hai aur use alias 'activity_type' de diya hai
            $globalLogs = DB::table('ticket_activities')
                ->join('users', 'ticket_activities.user_id', '=', 'users.id')
                ->join('tickets', 'ticket_activities.ticket_id', '=', 'tickets.id')
                ->select(
                    'ticket_activities.id as log_id',
                    'tickets.order_id as ticket_order_id',
                    'users.name as triggered_by_user',
                    'ticket_activities.type as activity_type', // 👈 Maps database 'type' directly to Flutter model key
                    'ticket_activities.note',
                    'ticket_activities.created_at as tracking_timestamp'
                )
                ->orderBy('ticket_activities.id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Master pipeline activity feed loaded for system auditing dashboard.',
                'count'   => $globalLogs->count(),
                'data'    => $globalLogs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}