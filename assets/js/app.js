/**
 * Mobile Gallery Lucky Draw - Main Interactive App & Canvas Wheel Engine
 */

class LuckyWheelApp {
    constructor() {
        this.canvas = document.getElementById('wheelCanvas');
        this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
        
        // Wheel state
        this.segments = [];
        this.currentAngle = 0; // In Radians
        this.isSpinning = false;
        this.soundEnabled = true;
        this.audioCtx = null;
        this.verifiedCoupon = null;

        // Color palette for segments
        this.colors = [
            '#e12826', '#f5af19', '#8e44ad', '#27ae60', 
            '#2980b9', '#d35400', '#16a085', '#c0392b'
        ];

        this.init();
    }

    init() {
        if (!this.canvas) return;

        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());

        this.bindEvents();
        this.loadPrizes();
        this.loadRecentWinners();
    }

    resizeCanvas() {
        const rect = this.canvas.parentElement.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        const size = Math.min(rect.width, rect.height) - 16;
        
        this.canvas.width = size * dpr;
        this.canvas.height = size * dpr;
        this.canvas.style.width = `${size}px`;
        this.canvas.style.height = `${size}px`;
        
        if (this.ctx) {
            this.ctx.scale(dpr, dpr);
        }
        this.drawWheel();
    }

    bindEvents() {
        // Coupon Check button
        const btnCheck = document.getElementById('btnCheckCoupon');
        const inputCoupon = document.getElementById('inputCouponNumber');
        
        if (btnCheck && inputCoupon) {
            btnCheck.addEventListener('click', () => this.checkCoupon());
            inputCoupon.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.checkCoupon();
            });
        }

        // Spin Now button
        const btnSpin = document.getElementById('btnSpinNow');
        if (btnSpin) {
            btnSpin.addEventListener('click', () => this.startSpinFlow());
        }

        // Sound Toggle button
        const btnSound = document.getElementById('btnSoundToggle');
        if (btnSound) {
            btnSound.addEventListener('click', () => {
                this.soundEnabled = !this.soundEnabled;
                btnSound.innerHTML = this.soundEnabled ? '🔊 Sound On' : '🔇 Sound Off';
            });
        }

        // Modal Close / Spin Again button
        const btnCloseModal = document.getElementById('btnCloseModal');
        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', () => {
                document.getElementById('celebrationModal').classList.remove('active');
                this.resetForm();
            });
        }

        // Print Result button
        const btnPrintResult = document.getElementById('btnPrintResult');
        if (btnPrintResult) {
            btnPrintResult.addEventListener('click', () => window.print());
        }
    }

    // Web Audio Synthesizer for tick and fanfare sounds
    initAudio() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
            }
        }
    }

    playTickSound() {
        if (!this.soundEnabled) return;
        this.initAudio();
        if (!this.audioCtx) return;

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(600, this.audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(200, this.audioCtx.currentTime + 0.05);

            gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.05);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);
            osc.start();
            osc.stop(this.audioCtx.currentTime + 0.05);
        } catch (e) {}
    }

    playWinFanfare() {
        if (!this.soundEnabled) return;
        this.initAudio();
        if (!this.audioCtx) return;

        const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
        notes.forEach((freq, idx) => {
            try {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime + idx * 0.12);
                
                gain.gain.setValueAtTime(0.3, this.audioCtx.currentTime + idx * 0.12);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + idx * 0.12 + 0.4);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start(this.audioCtx.currentTime + idx * 0.12);
                osc.stop(this.audioCtx.currentTime + idx * 0.12 + 0.4);
            } catch (e) {}
        });
    }

    async loadPrizes() {
        try {
            const res = await fetch('/api/prizes.php');
            const data = await res.json();

            if (data.success && data.prizes && data.prizes.length > 0) {
                this.segments = data.prizes;
            } else {
                this.segments = [
                    { name: 'Car (Hyundai i20)', type: 'special' },
                    { name: 'Bluetooth Headphones', type: 'regular' },
                    { name: 'Scooty (Activa 6G)', type: 'special' },
                    { name: 'Smart Watch', type: 'regular' },
                    { name: 'Wireless Earphones', type: 'regular' },
                    { name: 'Power Bank', type: 'regular' },
                    { name: 'Bluetooth Speaker', type: 'regular' },
                    { name: 'Mobile Accessories', type: 'regular' }
                ];
            }
            this.drawWheel();
        } catch (e) {
            console.error("Failed to load prizes:", e);
        }
    }

    async loadRecentWinners() {
        const grid = document.getElementById('recentWinnersGrid');
        if (!grid) return;

        try {
            const res = await fetch('/api/winners.php?limit=6');
            const data = await res.json();

            if (data.success && data.winners && data.winners.length > 0) {
                grid.innerHTML = data.winners.map(w => `
                    <div class="winner-card ${w.prize_type === 'special' ? 'special-winner' : ''}">
                        <span class="winner-coupon-badge">#${w.coupon_number}</span>
                        <div class="winner-info">
                            <div class="winner-name">${this.escapeHtml(w.customer_name)}</div>
                            <div class="winner-prize-name">🏆 ${this.escapeHtml(w.prize_name)}</div>
                        </div>
                    </div>
                `).join('');
            } else {
                grid.innerHTML = '<div style="color:var(--text-muted); font-size:0.9rem;">No winners recorded yet. Be the first to spin!</div>';
            }
        } catch (e) {}
    }

    drawWheel() {
        if (!this.ctx || this.segments.length === 0) return;

        const size = parseFloat(this.canvas.style.width);
        const center = size / 2;
        const radius = center - 8;
        const numSegments = this.segments.length;
        const arc = (2 * Math.PI) / numSegments;

        this.ctx.clearRect(0, 0, size, size);

        for (let i = 0; i < numSegments; i++) {
            const angle = this.currentAngle + i * arc;

            // Draw segment slice
            this.ctx.beginPath();
            this.ctx.moveTo(center, center);
            this.ctx.arc(center, center, radius, angle, angle + arc);
            this.ctx.closePath();

            const isSpecial = this.segments[i].type === 'special';
            this.ctx.fillStyle = isSpecial ? '#e12826' : this.colors[i % this.colors.length];
            this.ctx.fill();

            this.ctx.lineWidth = 2;
            this.ctx.strokeStyle = '#f5af19';
            this.ctx.stroke();

            // Draw text label
            this.ctx.save();
            this.ctx.translate(center, center);
            this.ctx.rotate(angle + arc / 2);
            this.ctx.textAlign = 'right';
            this.ctx.fillStyle = '#ffffff';
            this.ctx.font = 'bold 13px Poppins, sans-serif';
            
            let label = this.segments[i].name;
            if (label.length > 18) {
                label = label.substring(0, 16) + '..';
            }
            this.ctx.fillText(label, radius - 20, 5);
            this.ctx.restore();
        }
    }

    async checkCoupon() {
        const input = document.getElementById('inputCouponNumber');
        const num = parseInt(input.value.trim());

        if (!num || num <= 0) {
            alert('Please enter a valid numeric Coupon Number.');
            return;
        }

        const btnCheck = document.getElementById('btnCheckCoupon');
        btnCheck.disabled = true;
        btnCheck.innerText = 'Checking...';

        try {
            const res = await fetch(`/api/coupon.php?number=${num}`);
            const data = await res.json();

            btnCheck.disabled = false;
            btnCheck.innerText = 'Check Coupon';

            if (!data.success) {
                alert(data.error || 'Invalid Coupon Number.');
                return;
            }

            if (data.already_won) {
                alert(`Coupon #${num} (${data.customer_name}) has ALREADY WON: ${data.winning_prize}`);
                return;
            }

            if (!data.eligible) {
                alert(`Coupon #${num} is currently ineligible for drawing.`);
                return;
            }

            // Display confirmation details
            this.verifiedCoupon = data;
            document.getElementById('confirmCouponNo').innerText = `#${data.coupon_number}`;
            document.getElementById('confirmCustomerName').innerText = data.customer_name;
            document.getElementById('confirmMobile').innerText = data.mobile_masked;
            
            document.getElementById('customerConfirmBox').classList.add('active');
            document.getElementById('btnSpinNow').disabled = false;

        } catch (e) {
            btnCheck.disabled = false;
            btnCheck.innerText = 'Check Coupon';
            alert('Server error verifying coupon. Please check connection.');
        }
    }

    async startSpinFlow() {
        if (!this.verifiedCoupon || this.isSpinning) return;

        const btnSpin = document.getElementById('btnSpinNow');
        btnSpin.disabled = true;
        btnSpin.innerText = 'SPINNING...';
        this.isSpinning = true;

        try {
            // Call authoritative backend API FIRST
            const res = await fetch('/api/spin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ coupon_number: this.verifiedCoupon.coupon_number })
            });

            const spinResult = await res.json();

            if (!spinResult.success) {
                alert(spinResult.error || 'Spin failed.');
                this.isSpinning = false;
                btnSpin.disabled = false;
                btnSpin.innerText = 'SPIN NOW';
                return;
            }

            // Animate wheel to land on winning prize
            this.animateWheelToResult(spinResult);

        } catch (e) {
            alert('Network error executing draw.');
            this.isSpinning = false;
            btnSpin.disabled = false;
            btnSpin.innerText = 'SPIN NOW';
        }
    }

    animateWheelToResult(spinResult) {
        // Find index of winning prize in segments
        let targetIdx = this.segments.findIndex(s => s.name.toLowerCase() === spinResult.prize.toLowerCase());
        if (targetIdx === -1) {
            targetIdx = 0; // Default segment fallback
        }

        const numSegments = this.segments.length;
        const arc = (2 * Math.PI) / numSegments;

        // Pointer indicator is at 1.5 * PI (270 degrees / Top center)
        const pointerAngle = 1.5 * Math.PI;

        // Calculate target angle so pointer lands in middle of target segment
        const segmentCenterAngle = targetIdx * arc + arc / 2;
        let targetFinalAngle = pointerAngle - segmentCenterAngle;

        // Add 5 to 7 full rotations for dynamic spin animation
        const totalRotations = 6 * 2 * Math.PI;
        const startAngle = this.currentAngle;
        const totalDistance = (targetFinalAngle - (startAngle % (2 * Math.PI))) + totalRotations;

        const duration = 5000; // 5 seconds spin
        const startTime = performance.now();
        let lastTickSegment = -1;

        const animate = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(1, elapsed / duration);

            // Ease out cubic physics curve for acceleration + deceleration
            const easeOut = 1 - Math.pow(1 - progress, 3);
            this.currentAngle = startAngle + totalDistance * easeOut;

            // Play tick sound when passing segment lines
            const normalizedAngle = (pointerAngle - this.currentAngle) % (2 * Math.PI);
            const currentPassSegment = Math.floor(((normalizedAngle < 0 ? normalizedAngle + 2 * Math.PI : normalizedAngle)) / arc);
            
            if (currentPassSegment !== lastTickSegment) {
                this.playTickSound();
                lastTickSegment = currentPassSegment;
            }

            this.drawWheel();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Spin complete!
                this.isSpinning = false;
                this.playWinFanfare();
                this.showResultModal(spinResult);
                this.loadRecentWinners();
            }
        };

        requestAnimationFrame(animate);
    }

    showResultModal(spinResult) {
        document.getElementById('resCouponNo').innerText = `#${spinResult.coupon_number}`;
        document.getElementById('resCustomerName').innerText = spinResult.customer_name;
        document.getElementById('resPrizeName').innerText = spinResult.prize;
        document.getElementById('resPrizeType').innerText = spinResult.prize_type === 'special' ? '🌟 SPECIAL MAJOR PRIZE' : '🎁 REGULAR PRIZE';
        document.getElementById('resPrizeEmoji').innerText = spinResult.prize_type === 'special' ? (spinResult.prize.includes('Car') ? '🚗' : '🛵') : '🎧';
        
        document.getElementById('celebrationModal').classList.add('active');

        if (window.confetti) {
            window.confetti.start(4500);
        }
    }

    resetForm() {
        this.verifiedCoupon = null;
        document.getElementById('inputCouponNumber').value = '';
        document.getElementById('customerConfirmBox').classList.remove('active');
        const btnSpin = document.getElementById('btnSpinNow');
        btnSpin.disabled = true;
        btnSpin.innerText = 'SPIN NOW';
    }

    escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new LuckyWheelApp();
});
