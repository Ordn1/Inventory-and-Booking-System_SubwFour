(function(){
    'use strict';

    function $(sel, ctx=document){ return ctx.querySelector(sel); }

    function lineChart(canvas, dataPoints, options = {}) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width = canvas.clientWidth * (window.devicePixelRatio||1);
        const h = canvas.height = canvas.clientHeight * (window.devicePixelRatio||1);
        ctx.scale(window.devicePixelRatio||1, window.devicePixelRatio||1);

        ctx.clearRect(0,0,w,h);
        const pad = 28;
        const innerW = (w/(window.devicePixelRatio||1)) - pad*2;
        const innerH = (h/(window.devicePixelRatio||1)) - pad*2;

        const maxV = Math.max( (options.minY||0), Math.max(...dataPoints.map(p=>p.y), 1) );
        const minV = options.minY ?? 0;

        ctx.lineWidth = 1;
        ctx.strokeStyle = '#333';
        ctx.beginPath();
        ctx.moveTo(pad, pad + innerH);
        ctx.lineTo(pad + innerW, pad + innerH);
        ctx.stroke();

        const xStep = dataPoints.length > 1 ? innerW / (dataPoints.length - 1) : 0;

        ctx.lineWidth = 2;
        const grad = ctx.createLinearGradient(0,pad,0,pad+innerH);
        grad.addColorStop(0,'#7c3aed');
        grad.addColorStop(1,'#9333ea');

        ctx.strokeStyle = grad;
        ctx.beginPath();
        dataPoints.forEach((p,i)=>{
            const x = pad + i * xStep;
            const norm = (p.y - minV) / (maxV - minV || 1);
            const y = pad + innerH - norm * innerH;
            if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
        });
        ctx.stroke();

        // Fill
        ctx.lineTo(pad + innerW, pad + innerH);
        ctx.lineTo(pad, pad + innerH);
        ctx.closePath();
        const fillGrad = ctx.createLinearGradient(0,pad,0,pad+innerH);
        fillGrad.addColorStop(0,'rgba(124,58,237,.32)');
        fillGrad.addColorStop(1,'rgba(124,58,237,0)');
        ctx.fillStyle = fillGrad;
        ctx.fill();

        // Dots
        ctx.fillStyle = '#fff';
        dataPoints.forEach((p,i)=>{
            const x = pad + i * xStep;
            const norm = (p.y - minV) / (maxV - minV || 1);
            const y = pad + innerH - norm * innerH;
            ctx.beginPath();
            ctx.arc(x,y,3,0,Math.PI*2);
            ctx.fill();
        });

        // Labels (X)
        ctx.fillStyle = '#888';
        ctx.font = '10px Poppins, sans-serif';
        dataPoints.forEach((p,i)=>{
            const x = pad + i * xStep;
            const label = p.label;
            ctx.fillText(label, x- (ctx.measureText(label).width/2), pad + innerH + 12);
        });
    }

    function barChart(canvas, dataPoints) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width = canvas.clientWidth * (window.devicePixelRatio||1);
        const h = canvas.height = canvas.clientHeight * (window.devicePixelRatio||1);
        ctx.scale(window.devicePixelRatio||1, window.devicePixelRatio||1);
        ctx.clearRect(0,0,w,h);

        const pad = 28;
        const innerW = (w/(window.devicePixelRatio||1)) - pad*2;
        const innerH = (h/(window.devicePixelRatio||1)) - pad*2;

        const maxV = Math.max(...dataPoints.map(p=>p.y), 1);
        const barW = innerW / (dataPoints.length * 1.6);

        // Axis
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(pad, pad + innerH);
        ctx.lineTo(pad + innerW, pad + innerH);
        ctx.stroke();

        dataPoints.forEach((p,i)=>{
            const x = pad + i * (barW * 1.6) + (barW*0.3);
            const hVal = (p.y / maxV) * innerH;
            const y = pad + innerH - hVal;

            const grad = ctx.createLinearGradient(0,y,0,y+hVal);
            grad.addColorStop(0,'#ef4444');
            grad.addColorStop(1,'#dc2626');
            ctx.fillStyle = grad;
            ctx.fillRect(x, y, barW, hVal);

            ctx.fillStyle = '#fff';
            ctx.font = '10px Poppins, sans-serif';
            ctx.fillText(p.y, x + barW/2 - ctx.measureText(p.y).width/2, y - 4);

            ctx.fillStyle = '#888';
            ctx.fillText(p.label, x + barW/2 - ctx.measureText(p.label).width/2, pad + innerH + 12);
        });
    }

    function prepareDaily(raw) {
        // Ensure 7 days continuity
        const map = {};
        raw.forEach(r=> map[r.date] = r.count);
        const out = [];
        for (let i=6;i>=0;i--){
            const d = new Date();
            d.setDate(d.getDate()-i);
            const key = d.toISOString().slice(0,10);
            out.push({
                label: key.slice(5), // MM-DD
                y: map[key] || 0
            });
        }
        return out;
    }

    function prepareMonthly(raw) {
        // Expect last 6 months (some may be missing)
        const now = new Date();
        const needed = [];
        for (let i=5;i>=0;i--){
            const d = new Date(now.getFullYear(), now.getMonth()-i, 1);
            needed.push(d.toISOString().slice(0,7)); // YYYY-MM
        }
        const map = {};
        raw.forEach(r=> map[r.month] = r.count);
        return needed.map(k=> ({
            label: k.slice(2), // YY-MM
            y: map[k] || 0
        }));
    }

    function initDashboard() {
        const root = $('#dashboardRoot');
        if (!root) return;

        const dailyRaw   = JSON.parse(root.dataset.dailyBookings || '[]');
        const monthlyRaw = JSON.parse(root.dataset.monthlyServices || '[]');

        const dailyData   = prepareDaily(dailyRaw);
        const monthlyData = prepareMonthly(monthlyRaw);

        const dailyCanvas   = $('#dailyBookingsChart');
        const monthlyCanvas = $('#monthlyServicesChart');

        function renderAll() {
            if (dailyCanvas)   lineChart(dailyCanvas, dailyData);
            if (monthlyCanvas) barChart(monthlyCanvas, monthlyData);
        }

        renderAll();
        window.addEventListener('resize', ()=> {
            clearTimeout(window.__dashRsz);
            window.__dashRsz = setTimeout(renderAll, 180);
        });

        // Simple re-render buttons (no new fetch; placeholder for future AJAX)
        document.querySelector('[data-reload-bookings]')
            ?.addEventListener('click', ()=> renderAll());
        document.querySelector('[data-reload-services]')
            ?.addEventListener('click', ()=> renderAll());
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
})();