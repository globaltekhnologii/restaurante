document.addEventListener('DOMContentLoaded', initCarousel);

let currentSlide = 0;
let slidesData = [];
let slideInterval;
const AUTO_PLAY_DELAY = 5000; // 5 segundos

async function initCarousel() {
    try {
        // Cargar anuncios desde API
        const response = await fetch('api/gestionar_publicidad.php?accion=listar');
        const anuncios = await response.json();

        // Filtrar solo activos y vigentes
        const hoy = new Date().toISOString().split('T')[0];
        slidesData = anuncios.filter(ad => {
            if (ad.activo != 1) return false;
            if (ad.fecha_fin && ad.fecha_fin < hoy) return false;
            // Fecha inicio no debería ser problema si consultamos los que ya iniciaron, 
            // pero podemos validar por seguridad
            return true;
        });

        if (slidesData.length > 0) {
            renderCarousel();
            startAutoPlay();
        } else {
            console.log('No hay anuncios activos para mostrar.');
        }

    } catch (error) {
        console.error('Error cargando publicidad:', error);
    }
}

function renderCarousel() {
    const container = document.querySelector('.ad-carousel-container');
    const track = document.querySelector('.ad-carousel-track');
    const indicatorsContainer = document.querySelector('.ad-indicators');

    container.style.display = 'block';

    // Generar Slides
    let html = '';
    slidesData.forEach((ad, index) => {
        let content = '';
        if (ad.tipo === 'video') {
            content = `<video src="${ad.archivo_url}" autoplay muted loop playsinline></video>`;
        } else {
            content = `<img src="${ad.archivo_url}" alt="${ad.titulo}">`;
        }

        // Si tiene link, envolver en <a>
        if (ad.link_destino) {
            content = `<a href="${ad.link_destino}" target="_blank">${content}</a>`;
        }

        html += `<div class="ad-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                    ${content}
                 </div>`;

        // Indicadores
        const dot = document.createElement('div');
        dot.className = `ad-dot ${index === 0 ? 'active' : ''}`;
        dot.onclick = () => goToSlide(index);
        indicatorsContainer.appendChild(dot);
    });

    track.innerHTML = html;

    // Botones navegación
    document.querySelector('.ad-prev').onclick = prevSlide;
    document.querySelector('.ad-next').onclick = nextSlide;
}

function showSlide(index) {
    const slides = document.querySelectorAll('.ad-slide');
    const dots = document.querySelectorAll('.ad-dot');

    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;

    currentSlide = index;

    // Actualizar slides
    slides.forEach(slide => slide.classList.remove('active'));
    slides[currentSlide].classList.add('active');

    // Actualizar dots
    dots.forEach(dot => dot.classList.remove('active'));
    dots[currentSlide].classList.add('active');
}

function nextSlide() {
    showSlide(currentSlide + 1);
    resetAutoPlay();
}

function prevSlide() {
    showSlide(currentSlide - 1);
    resetAutoPlay();
}

function goToSlide(index) {
    showSlide(index);
    resetAutoPlay();
}

function startAutoPlay() {
    // Solo si hay más de 1 slide y no es video (los videos suelen tener su propio tiempo)
    // Opcional: pausar si el slide actual es video
    const currentAd = slidesData[currentSlide];
    if (currentAd && currentAd.tipo === 'video') return;

    slideInterval = setInterval(() => {
        // Verificar si el siguiente es video para pausar autoplay o manejarlo diferente
        showSlide(currentSlide + 1);
    }, AUTO_PLAY_DELAY);
}

function resetAutoPlay() {
    clearInterval(slideInterval);
    startAutoPlay();
}

// Pausar autoplay cuando el mouse está encima
document.querySelector('.ad-carousel').addEventListener('mouseenter', () => {
    clearInterval(slideInterval);
});

document.querySelector('.ad-carousel').addEventListener('mouseleave', () => {
    startAutoPlay();
});
