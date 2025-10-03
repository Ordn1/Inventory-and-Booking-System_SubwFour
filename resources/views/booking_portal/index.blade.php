@extends('booking_layout')

@section('title','Booking Portal - SubWFour')

@section('head')
<link rel="stylesheet" href="{{ asset('css/portal.css') }}">
<script src="{{ asset('js/portal.js') }}" defer></script>
@endsection

@section('content')
<div class="portal-shell">

    <!-- Top Minimal Nav -->
    <header class="portal-topbar">
        <div class="topbar-inner">
            <div class="brand">
                <img src="{{ asset('images/SubWFourLogo.png') }}" alt="SubWFour" class="brand-mark">
            </div>
            <nav class="mini-nav">
                <a href="#services" class="mini-link">Services</a>
                <a href="#process" class="mini-link">Process</a>
                <a href="#why" class="mini-link">Why Us</a>
                <a href="#contact" class="mini-link">Contact</a>
                <button id="openBookingFormBtn" type="button" class="btn btn-primary top-cta">
                    <i class="bi bi-calendar-plus"></i> Book Now
                </button>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="portal-hero expanded-hero" id="hero">
        <div class="hero-grid">
            <div class="hero-main">
                <img src="{{ asset('images/SubWFourFullLogo.png') }}" alt="SubWFour Full Logo" class="hero-logo">
                <h1 class="portal-title">Premium Car Audio & Installation</h1>
                <p class="portal-subtitle hero-sub">
                    Precision speaker upgrades, subwoofer fabrication, full custom audio system design & tuning.
                    Trusted installation workflow from booking to final demonstration.
                </p>

                @if(session('success'))
                    <div class="alert alert-success hero-alert">{{ session('success') }}</div>
                @endif
                @if($errors->any() && old('_from')==='createBooking')
                    <div class="alert alert-danger hero-alert">
                        <ul class="m-0 ps-3" style="font-size:.7rem;">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="hero-actions">
                    <button type="button"
                            id="openBookingFormBtnHero"
                            class="btn btn-primary hero-book-btn">
                        <i class="bi bi-calendar-plus"></i> Book Now
                    </button>
                    <a href="#process" class="btn btn-secondary hero-secondary">
                        <i class="bi bi-arrow-down-circle"></i> How It Works
                    </a>
                </div>

                <div class="trust-metrics">
                    <div class="metric">
                        <span class="metric-value">Lifetime</span>
                        <span class="metric-label">Warranty Support</span>
                    </div>
                    <div class="metric">
                        <span class="metric-value">Flexible</span>
                        <span class="metric-label">Booking Options</span>
                    </div>
                    <div class="metric">
                        <span class="metric-value">Transparent</span>
                        <span class="metric-label">Pricing Policy</span>
                    </div>
                </div>
            </div>
            <div class="hero-side">
                <div class="side-card glass-tile">
                    <h3 class="side-heading"><i class="bi bi-music-note-beamed"></i> Audio Precision</h3>
                    <p class="side-desc">
                        Every installation is calibrated for clarity, depth, and balanced staging. From entry upgrades to
                        full engineered builds—done right the first time.
                    </p>
                    <ul class="side-list">
                        <li><i class="bi bi-check-circle-fill"></i> Custom Enclosures</li>
                        <li><i class="bi bi-check-circle-fill"></i> DSP Tuning & Setup</li>
                        <li><i class="bi bi-check-circle-fill"></i> Noise Dampening</li>
                        <li><i class="bi bi-check-circle-fill"></i> OEM Integration</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- INLINE BOOKING FORM -->
    <div id="bookingFormWrapper"
         class="booking-form-wrapper"
         aria-hidden="true"
         data-auto-open="{{ ($errors->any() && old('_from')==='createBooking') ? '1':'0' }}">
        <form id="bookingInlineForm"
              action="{{ route('booking.portal.store') }}"
              method="POST"
              class="booking-form-panel"
              novalidate>
            @csrf
            <input type="hidden" name="_from" value="createBooking">

            <h2 class="booking-form-title">Booking Request</h2>

            <div id="formErrorSummary" class="portal-alert error" style="display:none;font-size:.65rem;"></div>

            <div class="form-row uniform">
                <div class="form-group" style="margin-right:25px;">
                    <label>Full Name *</label>
                    <input name="customer_name" class="form-input" required value="{{ old('customer_name') }}">
                    <div class="field-error" data-error-for="customer_name"></div>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input name="email" type="email" class="form-input" required value="{{ old('email') }}">
                    <div class="field-error" data-error-for="email"></div>
                </div>
            </div>

            <div class="form-row uniform">
                <div class="form-group" style="margin-right:25px;">
                    <label>Contact Number *</label>
                    <input name="contact_number" class="form-input" required value="{{ old('contact_number') }}">
                    <div class="field-error" data-error-for="contact_number"></div>
                </div>
                <div class="form-group">
                    <label>Service Type *</label>
                    <select name="service_type" class="form-input" required>
                        <option value="">-- select service --</option>
                        @foreach(($serviceTypes ?? []) as $st)
                            <option value="{{ $st }}" @selected(old('service_type') == $st)>{{ $st }}</option>
                        @endforeach
                        <option value="Other" @selected(old('service_type') === 'Other')>Other</option>
                    </select>
                    <div class="field-error" data-error-for="service_type"></div>
                </div>
            </div>

            <div class="form-row uniform">
                <div class="form-group" style="margin-right:25px;">
                    <label>Preferred Date *</label>
                    <input type="date"
                           name="preferred_date"
                           class="form-input"
                           required
                           value="{{ old('preferred_date', now()->format('Y-m-d')) }}">
                    <div class="field-error" data-error-for="preferred_date"></div>
                </div>
                <div class="form-group">
                    <label>Preferred Time *</label>
                    <input type="time"
                           name="preferred_time"
                           class="form-input"
                           required
                           value="{{ old('preferred_time') }}">
                    <div class="field-error" data-error-for="preferred_time"></div>
                </div>
            </div>

            <div class="form-row single">
                <div class="form-group">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="notes" rows="3" class="form-input" style="resize:vertical;">{{ old('notes') }}</textarea>
                    <div class="field-error" data-error-for="notes"></div>
                </div>
            </div>

            <div class="note" style="margin-top:6px;">
                We’ll review availability & confirm via your preferred contact method.
            </div>

            <div class="button-row" style="margin-top:18px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" id="cancelBookingFormBtn" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" id="bookingSubmitBtn">Submit Booking</button>
            </div>
        </form>
    </div>

    <!-- SERVICES -->
    <section class="section-block" id="services">
        <div class="section-head">
            <h2 class="section-title">Core Services</h2>
            <p class="section-sub">
                From subtle clarity boosts to fully engineered immersive systems—tailored to your preferences.
            </p>
        </div>
        <div class="card-grid services-grid">
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-speaker"></i></div>
                <h3>Speaker Upgrades</h3>
                <p>High-fidelity driver replacements, proper mounting & tuning for clearer stage & detail.</p>
            </div>
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-boombox"></i></div>
                <h3>Subwoofer Installs</h3>
                <p>Custom or stealth enclosures tuned for tight, musical, or deep low-end response.</p>
            </div>
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-diagram-3"></i></div>
                <h3>Full System Builds</h3>
                <p>Amplifiers, DSP, wiring architecture & stage design for balanced acoustic imaging.</p>
            </div>
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-sliders2-vertical"></i></div>
                <h3>DSP Calibration</h3>
                <p>Time-alignment, crossover & EQ tuning for accurate staging & tonal balance.</p>
            </div>
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-shield-lock"></i></div>
                <h3>Noise Dampening</h3>
                <p>Reduce panel resonance & road noise with strategic deadening for purer sound.</p>
            </div>
            <div class="svc-card">
                <div class="svc-icon gradient"><i class="bi bi-layers"></i></div>
                <h3>Custom Fabrication</h3>
                <p>Panels, pods & integration work that looks factory—refined and functional.</p>
            </div>
        </div>
    </section>

    <!-- PROCESS -->
    <section class="section-block alt-surface" id="process">
        <div class="section-head">
            <h2 class="section-title">Our Process</h2>
            <p class="section-sub">Clear, documented, customer-first from first contact to final handover.</p>
        </div>
        <div class="process-timeline" style="display: flex; justify-content: center;">
            <div class="p-step">
                <div class="p-badge">1</div>
                <h4>Booking</h4>
                <p>Choose service & slot via form, phone, Facebook, or a walk-in request.</p>
            </div>
            <div class="p-step">
                <div class="p-badge">2</div>
                <h4>Check-In</h4>
                <p>Vehicle condition review, goals discussion & confirmation of time estimate.</p>
            </div>
            <div class="p-step">
                <div class="p-badge">3</div>
                <h4>Installation</h4>
                <p>Clean wiring & calibrated setup. Progress handled with care & documentation.</p>
            </div>
            <div class="p-step">
                <div class="p-badge">4</div>
                <h4>Check-Out</h4>
                <p>Feature walkthrough, test session, warranty briefing & final adjustments.</p>
            </div>
            <div class="p-step">
                <div class="p-badge">5</div>
                <h4>Payment</h4>
                <p>Transparent invoice. Flexible payment options & optional deposits for large builds.</p>
            </div>
        </div>
        <div class="motto">
            <i class="bi bi-quote"></i>
            <span>"If you're not satisfied, do not pay."</span>
        </div>
    </section>

    <!-- WHY US -->
    <section class="section-block" id="why">
        <div class="section-head">
            <h2 class="section-title">Why Choose SubWFour</h2>
            <p class="section-sub">Focused on long-term reliability, acoustic accuracy & customer trust.</p>
        </div>
        <div class="why-grid" style="display: flex; justify-content: center;">
            <div class="why-card">
                <i class="bi bi-award-fill"></i>
                <h3>Lifetime Coverage</h3>
                <p>Support for installation workmanship for the lifetime of the system.</p>
            </div>
            <div class="why-card">
                <i class="bi bi-cash-coin"></i>
                <h3>Transparent Pricing</h3>
                <p>Clarity before you commit—estimates with realistic timeframes & no hidden fees.</p>
            </div>
            <div class="why-card">
                <i class="bi bi-hand-thumbs-up-fill"></i>
                <h3>Satisfaction First</h3>
                <p>We stand by our motto. You sign off only when you’re genuinely satisfied.</p>
            </div>
            <div class="why-card">
                <i class="bi bi-chat-dots-fill"></i>
                <h3>Flexible Contact</h3>
                <p>Reach us through the portal, social channels, phone or on-site consult.</p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="final-cta" id="contact">
        <div class="cta-inner">
            <h2 class="cta-title">Ready To Elevate Your Drive?</h2>
            <p class="cta-sub">Schedule your audio upgrade consultation now.</p>
            <button type="button" id="openBookingFormBtnBottom" class="btn btn-primary cta-btn">
                <i class="bi bi-calendar-plus"></i> Start Booking
            </button>
        </div>
    </section>

    <footer class="portal-footer">
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} SubWFour Audio. All rights reserved.</span>
            <span class="foot-meta">Quality • Clarity • Craftsmanship</span>
        </div>
    </footer>
</div>
@endsection