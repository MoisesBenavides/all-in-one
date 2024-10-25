class ParkingTimer {
    constructor() {
        this.timeLeft = 300; // 5 minutos
        this.timer = null;
        this.isRedirecting = false;
        this.initializeTimer();
    }

    initializeTimer() {
        // crear container de timer si no existe
        if (!document.getElementById('timerContainer')) {
            this.createTimerElements();
        }
        
        // comenzar la cuenta regresiva
        this.start();

        // beforeunload handler para prevenir navegacion i
      
    }

    createTimerElements() {
        // Timer container
        const timerContainer = document.createElement('div');
        timerContainer.className = 'w-full max-w-[600px] mt-4 p-4 bg-white border border-black';
        timerContainer.id = 'timerContainer';

        // Timer texto
        const timerText = document.createElement('p');
        timerText.className = 'text-center mb-2 font-bold';
        timerText.id = 'timerText';

        // container de la barra de progreso
        const progressBar = document.createElement('div');
        progressBar.className = 'w-full bg-gray-200 h-4 rounded-full overflow-hidden';
        
        // relleno de la barra
        const progress = document.createElement('div');
        progress.className = 'bg-blue-600 h-full rounded-full transition-all duration-1000 ease-linear';
        progress.id = 'timerProgress';
        progress.style.width = '100%';

        
        progressBar.appendChild(progress);
        timerContainer.appendChild(timerText);
        timerContainer.appendChild(progressBar);

        // insertarse despues de el formulario
        const parkingForm = document.getElementById('parkingForm');
        if (parkingForm && parkingForm.parentNode) {
            parkingForm.parentNode.insertBefore(timerContainer, parkingForm.nextSibling);
        }
    }

    start() {
        this.updateTimer();

        this.timer = setInterval(() => {
            this.timeLeft--;
            this.updateTimer();

            if (this.timeLeft <= 0) {
                this.redirectToParking();
            }
        }, 1000);
    }

    updateTimer() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;
        const timerText = document.getElementById('timerText');
        const progress = document.getElementById('timerProgress');
        
        if (timerText) {
            timerText.textContent = `Tiempo restante: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        if (progress) {
            const percentage = (this.timeLeft / 300) * 100;
            progress.style.width = `${percentage}%`;
            
            // mandejo de el color segun cuanto tiempo paso :)
            if (percentage <= 25) {
                progress.className = 'bg-red-600 h-full rounded-full transition-all duration-1000 ease-linear';
            } else if (percentage <= 50) {
                progress.className = 'bg-yellow-600 h-full rounded-full transition-all duration-1000 ease-linear';
            }
        }
    }

    redirectToParking() {
        this.stop();
        this.isRedirecting = true;

        // chequear si ruta esta definida
        if (typeof rutaRedireccion === 'undefined') {
            console.error('Error: rutaRedireccion no está definida');
            return;
        }

        try {
            // mensaje antes de reidirig
            const timerText = document.getElementById('timerText');
            if (timerText) {
                timerText.textContent = 'Tiempo agotado. Redirigiendo...';
            }

            
            setTimeout(() => {
                window.location.replace(rutaRedireccion);
            }, 500);
        } catch (error) {
            console.error('Error al redireccionar:', error);
        }
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

