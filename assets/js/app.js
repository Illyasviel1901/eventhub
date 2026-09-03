'use strict';

const browserTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
const secureCookie = window.location.protocol === 'https:' ? '; Secure' : '';
document.cookie = `eventhub_timezone=${encodeURIComponent(browserTimeZone)}; Path=/; Max-Age=31536000; SameSite=Lax${secureCookie}`;

document.querySelectorAll('[data-browser-timezone]').forEach((element) => {
    element.textContent = browserTimeZone;
});

const localDateTimeFormatter = new Intl.DateTimeFormat('ro-RO', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
});

document.querySelectorAll('time[data-local-datetime]').forEach((element) => {
    const unixTimestamp = Number(element.dataset.localDatetime);
    const date = new Date(unixTimestamp * 1000);

    if (Number.isFinite(unixTimestamp) && !Number.isNaN(date.getTime())) {
        element.textContent = localDateTimeFormatter.format(date);
        element.title = `${browserTimeZone} (UTC${formatTimeZoneOffset(date)})`;
    }
});

function formatTimeZoneOffset(date) {
    const offsetMinutes = -date.getTimezoneOffset();
    const sign = offsetMinutes >= 0 ? '+' : '-';
    const absoluteMinutes = Math.abs(offsetMinutes);
    const hours = String(Math.floor(absoluteMinutes / 60)).padStart(2, '0');
    const minutes = String(absoluteMinutes % 60).padStart(2, '0');

    return `${sign}${hours}:${minutes}`;
}

const navigationButton = document.querySelector('.nav-toggle');
const navigation = document.querySelector('.main-nav');

if (navigationButton && navigation) {
    navigationButton.addEventListener('click', () => {
        const isOpen = navigationButton.getAttribute('aria-expanded') === 'true';
        navigationButton.setAttribute('aria-expanded', String(!isOpen));
        navigation.classList.toggle('is-open', !isOpen);
    });
}

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const mainImage = gallery.querySelector('[data-gallery-main]');
    const thumbnails = [...gallery.querySelectorAll('[data-gallery-thumbnail]')];
    const previousButton = gallery.querySelector('[data-gallery-prev]');
    const nextButton = gallery.querySelector('[data-gallery-next]');
    let activeIndex = Math.max(0, thumbnails.findIndex((thumbnail) => thumbnail.classList.contains('is-active')));

    const selectImage = (index) => {
        if (!mainImage || thumbnails.length === 0) return;
        activeIndex = (index + thumbnails.length) % thumbnails.length;
        const selected = thumbnails[activeIndex];
        mainImage.src = selected.dataset.src;
        mainImage.alt = selected.dataset.alt;
        thumbnails.forEach((thumbnail, thumbnailIndex) => {
            const isActive = thumbnailIndex === activeIndex;
            thumbnail.classList.toggle('is-active', isActive);
            thumbnail.setAttribute('aria-pressed', String(isActive));
        });
    };

    thumbnails.forEach((thumbnail, index) => thumbnail.addEventListener('click', () => selectImage(index)));
    previousButton?.addEventListener('click', () => selectImage(activeIndex - 1));
    nextButton?.addEventListener('click', () => selectImage(activeIndex + 1));
    gallery.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') selectImage(activeIndex - 1);
        if (event.key === 'ArrowRight') selectImage(activeIndex + 1);
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
});

const reservationDate = document.querySelector('#event-date[data-forecast-endpoint]');
const reservationWeather = document.querySelector('#reservation-weather');
const dateFeedback = document.querySelector('#date-availability');

if (reservationDate && reservationWeather && dateFeedback) {
    let debounceTimer = null;
    let activeController = null;
    let requestSequence = 0;
    let unavailableDates = [];

    try {
        unavailableDates = JSON.parse(reservationDate.dataset.unavailableDates || '[]');
    } catch (_) {
        unavailableDates = [];
    }

    const hideWeather = () => {
        reservationWeather.classList.add('is-hidden');
        reservationWeather.replaceChildren();
    };

    const showWeather = (weather) => {
        reservationWeather.replaceChildren();
        const content = document.createElement('div');
        const eyebrow = document.createElement('p');
        const heading = document.createElement('h3');
        const details = document.createElement('p');
        eyebrow.className = 'eyebrow';
        eyebrow.textContent = `Prognoză Open-Meteo · ${weather.date.split('-').reverse().join('.')}`;
        heading.textContent = `${weather.description} în ${weather.city}`;
        details.textContent = `${Number(weather.temperature_min).toLocaleString('ro-RO', { maximumFractionDigits: 1 })}°C – ${Number(weather.temperature_max).toLocaleString('ro-RO', { maximumFractionDigits: 1 })}°C · ploaie ${weather.precipitation_probability}% · vânt ${Number(weather.wind_speed).toLocaleString('ro-RO', { maximumFractionDigits: 1 })} km/h`;
        content.append(eyebrow, heading, details);
        reservationWeather.append(content);
        reservationWeather.classList.remove('is-hidden');
    };

    const showWeatherUnavailable = () => {
        reservationWeather.replaceChildren();
        const message = document.createElement('p');
        message.textContent = 'Prognoza meteo nu este disponibilă momentan pentru data selectată.';
        reservationWeather.append(message);
        reservationWeather.classList.remove('is-hidden');
    };

    const verifyDate = () => {
        window.clearTimeout(debounceTimer);
        requestSequence += 1;
        const sequence = requestSequence;
        const selectedDate = reservationDate.value;
        activeController?.abort();
        activeController = null;
        reservationDate.setCustomValidity('');

        if (!selectedDate) {
            dateFeedback.textContent = 'Selectează o dată pentru verificarea disponibilității.';
            dateFeedback.className = 'date-feedback';
            hideWeather();
            return;
        }

        const earliestDate = reservationDate.min;
        if (earliestDate && selectedDate < earliestDate) {
            reservationDate.value = '';
            reservationDate.setCustomValidity('Data evenimentului trebuie să fie cel puțin ziua următoare.');
            dateFeedback.textContent = 'Alege o dată începând cu ziua următoare.';
            dateFeedback.className = 'date-feedback is-unavailable';
            hideWeather();
            return;
        }

        if (unavailableDates.includes(selectedDate)) {
            reservationDate.value = '';
            reservationDate.setCustomValidity('Data selectată este deja ocupată.');
            dateFeedback.textContent = 'Data selectată este deja ocupată. Alege o altă dată.';
            dateFeedback.className = 'date-feedback is-unavailable';
            hideWeather();
            return;
        }

        dateFeedback.textContent = 'Verificăm disponibilitatea...';
        dateFeedback.className = 'date-feedback';
        hideWeather();

        debounceTimer = window.setTimeout(async () => {
            activeController = new AbortController();
            const requestedDate = selectedDate;
            const url = new URL(reservationDate.dataset.forecastEndpoint, window.location.href);
            url.searchParams.set('venue_id', reservationDate.dataset.venueId);
            url.searchParams.set('date', requestedDate);

            try {
                const response = await fetch(url, {
                    signal: activeController.signal,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (sequence !== requestSequence || reservationDate.value !== requestedDate) {
                    return;
                }

                if (!response.ok) {
                    throw new Error(result.error || 'Verificarea nu a reușit.');
                }

                if (!result.available) {
                    unavailableDates.push(requestedDate);
                    reservationDate.value = '';
                    reservationDate.setCustomValidity('Data selectată este deja ocupată.');
                    dateFeedback.textContent = 'Data selectată este deja ocupată. Alege o altă dată.';
                    dateFeedback.className = 'date-feedback is-unavailable';
                    hideWeather();
                    return;
                }

                dateFeedback.textContent = 'Data este disponibilă pentru solicitare.';
                dateFeedback.className = 'date-feedback is-available';

                if (!result.weather_eligible) {
                    hideWeather();
                } else if (result.weather) {
                    showWeather(result.weather);
                } else {
                    showWeatherUnavailable();
                }
            } catch (error) {
                if (error.name === 'AbortError' || sequence !== requestSequence) {
                    return;
                }
                dateFeedback.textContent = 'Disponibilitatea va fi verificată din nou la trimiterea formularului.';
                dateFeedback.className = 'date-feedback';
                hideWeather();
            }
        }, 300);
    };

    reservationDate.addEventListener('input', verifyDate);
    reservationDate.addEventListener('change', verifyDate);
    if (reservationDate.value) {
        verifyDate();
    }
}
