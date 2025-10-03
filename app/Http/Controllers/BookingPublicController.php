<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\ServiceType;

class BookingPublicController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::where('active', true)
            ->orderBy('name')
            ->pluck('name');

        return view('booking_portal.index', compact('serviceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'   => ['required','string','max:150'],
            'email'           => ['required','email','max:150'],
            'contact_number'  => ['required','string','max:60'],
            'service_type'    => ['required','string','max:120'],
            'preferred_date'  => ['required','date','after_or_equal:today'],
            'preferred_time'  => ['required','string','max:20'],
            'notes'           => ['nullable','string'],
        ]);

        Booking::create($data);

        return redirect()
            ->route('booking.portal')
            ->with('success','Booking submitted. We will contact you soon.');
    }
}