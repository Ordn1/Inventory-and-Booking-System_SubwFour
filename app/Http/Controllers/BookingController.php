<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\StockOut;
use App\Models\ActivityLog;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Booking::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search){
                $q->where('booking_id','like',"%{$search}%")
                  ->orWhere('customer_name','like',"%{$search}%")
                  ->orWhere('service_type','like',"%{$search}%")
                  ->orWhere('email','like',"%{$search}%");
            });
        }

        $bookings = $query->orderByDesc('created_at')->paginate(15);

        return view('bookings.index', compact('bookings','search','status'));
    }

    public function appoint($booking_id)
    {
        $booking = Booking::where('booking_id',$booking_id)->firstOrFail();

        if ($booking->status !== 'completed') {
            return back()->withErrors('Booking is not completed yet.');
        }

        if ($booking->status === 'appointed') {
            return back()->with('success','Already appointed.');
        }

        $booking->status = 'appointed';
        $booking->save();

        ActivityLog::record(
            'booking.appointed',
            $booking,
            'Booking appointed',
            ['status' => $booking->status]
        );

        return back()->with('success','Booking appointed.');
    }
}