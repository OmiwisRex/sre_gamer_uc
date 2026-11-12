/**
 * Formatea una fecha en formato 'YYYY-MM-DD HH:MM:SS' o Date object
 * al formato 'DD/MM/YYYY - HH:MM Am/Pm'
 * @param {string|Date} fecha - La fecha a formatear
 * @returns {string} Fecha formateada
 */
export const formatDateTimeWithPeriod = (fecha) => {
    if (!fecha) return 'Sin fecha';

    let dateObj;

    if (typeof fecha === 'string') {
        dateObj = new Date(fecha);
    } else if (fecha instanceof Date) {
        dateObj = fecha;
    } else {
        return 'Sin fecha';
    }

    if (isNaN(dateObj.getTime())) {
        return 'Sin fecha';
    }

    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();

    let hours = dateObj.getHours();
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');

    const period = hours >= 12 ? 'Pm' : 'Am';
    hours = hours % 12 || 12;
    const formattedHours = String(hours).padStart(2, '0');

    return `${day}/${month}/${year} - ${formattedHours}:${minutes} ${period}`;
};

/**
 * Formatea solo la hora de una fecha
 * @param {string|Date} fecha - La fecha a formatear
 * @returns {string} Hora formateada (HH:MM Am/Pm)
 */
export const formatTime = (fecha) => {
    if (!fecha) return 'Sin hora';

    let dateObj;

    if (typeof fecha === 'string') {
        dateObj = new Date(fecha);
    } else if (fecha instanceof Date) {
        dateObj = fecha;
    } else {
        return 'Sin hora';
    }

    if (isNaN(dateObj.getTime())) {
        return 'Sin hora';
    }

    let hours = dateObj.getHours();
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');

    const period = hours >= 12 ? 'Pm' : 'Am';
    hours = hours % 12 || 12;
    const formattedHours = String(hours).padStart(2, '0');

    return `${formattedHours}:${minutes} ${period}`;
};

/**
 * Formatea solo la fecha
 * @param {string|Date} fecha - La fecha a formatear
 * @returns {string} Fecha formateada (DD/MM/YYYY)
 */
export const formatDate = (fecha) => {
    if (!fecha) return 'Sin fecha';

    let dateObj;

    if (typeof fecha === 'string') {
        dateObj = new Date(fecha);
    } else if (fecha instanceof Date) {
        dateObj = fecha;
    } else {
        return 'Sin fecha';
    }

    if (isNaN(dateObj.getTime())) {
        return 'Sin fecha';
    }

    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();

    return `${day}/${month}/${year}`;
};
