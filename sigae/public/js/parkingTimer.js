class ParkingTimer {
    constructor() {
        this.timeLeft = 300; // 5 minutos
        this.timer = null;
        this.start();
    }

    start() {
        // Crear elementos del timer
        const timerContainer = document.createElement('div');
        timerContainer.className = 'w-full max-w-[600px] mt-4 p-4 bg-white border border-black';
        timerContainer.id = 'timerContainer';

        const progressBar = document.createElement('div');
        progressBar.className = 'w-full bg-gray-200 h-4 rounded-full';
        
        const progress = document.createElement('div');
        progress.className = 'bg-blue-600 h-4 rounded-full transition-all duration-1000';
        progress.style.width = '100%';
        progress.id = 'timerProgress';

        const timerText = document.createElement('p');
        timerText.className = 'text-center mb-2';
        timerText.id = 'timerText';

        progressBar.appendChild(progress);
        timerContainer.appendChild(timerText);
        timerContainer.appendChild(progressBar);

        // Insertar después del form
        const parkingForm = document.getElementById('parkingForm');
        parkingForm.parentNode.insertBefore(timerContainer, parkingForm.nextSibling);

        this.timeLeft = 300;
        this.updateTimer();

        this.timer = setInterval(() => {
            this.timeLeft--;
            this.updateTimer();

            if (this.timeLeft <= 0) {
                this.stop();
                window.location.href = '{{ path("aioParking") }}';
            }
        }, 1000);
    }

    updateTimer() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;
        const timerText = document.getElementById('timerText');
        const progress = document.getElementById('timerProgress');
        
        if (timerText && progress) {
            timerText.textContent = `Tiempo restante: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            progress.style.width = `${(this.timeLeft / 300) * 100}%`;
        }
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

