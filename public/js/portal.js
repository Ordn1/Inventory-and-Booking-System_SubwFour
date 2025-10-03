(function(){
    'use strict';

    const wrap   = document.getElementById('bookingFormWrapper');
    const form   = document.getElementById('bookingInlineForm');
    const panel  = wrap?.querySelector('.booking-form-panel');
    const cancel = document.getElementById('cancelBookingFormBtn');
    const submit = document.getElementById('bookingSubmitBtn');
    const summary= document.getElementById('formErrorSummary');

    const openBtns = [
        document.getElementById('openBookingFormBtn'),
        document.getElementById('openBookingFormBtnHero'),
        document.getElementById('openBookingFormBtnBottom')
    ].filter(Boolean);

    function openForm(scrollInto = true){
        if(!wrap) return;
        wrap.setAttribute('aria-hidden','false');
        wrap.classList.add('active');
        requestAnimationFrame(()=> panel.classList.add('show'));
        openBtns.forEach(b=> b.classList.add('hidden-force'));
        const first = panel.querySelector('input,textarea,select');
        if(scrollInto) setTimeout(()=>wrap.scrollIntoView({behavior:'smooth'}),120);
        if(first) setTimeout(()=>first.focus(),320);
    }
    function closeForm(){
        if(!wrap) return;
        panel.classList.remove('show');
        setTimeout(()=>{
            wrap.classList.remove('active');
            wrap.setAttribute('aria-hidden','true');
            openBtns.forEach(b=> b.classList.remove('hidden-force'));
            window.scrollTo({top:0,behavior:'smooth'});
        },260);
    }

    openBtns.forEach(b=> b.addEventListener('click', ()=> openForm(true)));
    cancel?.addEventListener('click', closeForm);

    // ============ Validation ============
    if(form){
        function setErr(name,msg){
            const box = form.querySelector('[data-error-for="'+name+'"]');
            const input = form.querySelector('[name="'+name+'"]');
            if(box){
                box.textContent = msg || '';
                box.style.display = msg ? 'block':'none';
            }
            if(input){
                input.classList.toggle('has-error', !!msg);
            }
        }
        function clearAll(){
            summary.style.display='none';
            summary.innerHTML='';
            form.querySelectorAll('.field-error').forEach(f=>{f.textContent='';f.style.display='none';});
            form.querySelectorAll('.has-error').forEach(el=>el.classList.remove('has-error'));
        }
        function isEmail(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
        function todayISO(){ return new Date().toISOString().slice(0,10); }

        function validate(){
            clearAll();
            const data = {
                customer_name : form.customer_name.value.trim(),
                email         : form.email.value.trim(),
                contact_number: form.contact_number.value.trim(),
                service_type  : form.service_type.value.trim(),
                preferred_date: form.preferred_date.value,
                preferred_time: form.preferred_time.value,
                notes         : form.notes.value.trim()
            };
            const errs = [];

            if(!data.customer_name) errs.push(['customer_name','Full name required.']);
            else if(data.customer_name.length>150) errs.push(['customer_name','Max 150 chars.']);

            if(!data.email) errs.push(['email','Email required.']);
            else if(data.email.length>150) errs.push(['email','Max 150 chars.']);
            else if(!isEmail(data.email)) errs.push(['email','Invalid email.']);

            if(!data.contact_number) errs.push(['contact_number','Contact number required.']);
            else if(data.contact_number.length>60) errs.push(['contact_number','Max 60 chars.']);

            if(!data.service_type) errs.push(['service_type','Service type required.']);
            else if(data.service_type.length>120) errs.push(['service_type','Max 120 chars.']);

            if(!data.preferred_date) errs.push(['preferred_date','Preferred date required.']);
            else if(data.preferred_date < todayISO()) errs.push(['preferred_date','Date cannot be past.']);

            if(!data.preferred_time) errs.push(['preferred_time','Preferred time required.']);

            if(errs.length){
                errs.forEach(([f,m])=> setErr(f,m));
                summary.innerHTML = '<strong>Fix the errors:</strong><ul style="margin:6px 0 0;padding-left:18px;font-size:.6rem;">'
                    + errs.map(e=>'<li>'+e[1]+'</li>').join('')
                    + '</ul>';
                summary.style.display='block';
                return false;
            }
            return true;
        }

        form.addEventListener('submit', e=>{
            if(!validate()){
                e.preventDefault();
                const firstErr = form.querySelector('.has-error');
                if(firstErr) firstErr.focus();
            } else {
                submit.disabled = true;
                submit.textContent = 'Submitting...';
            }
        });
    }
    
    if(wrap && wrap.dataset.autoOpen === '1'){
        openForm(false);
    }
})();