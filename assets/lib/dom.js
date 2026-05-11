import { Chart } from 'chart.js/auto'

/**
 * Permet de masquer un élément avec un effet de repli
 * 
 * @param {HTMLElement} element
 * @param {Number} duration
 * @returns {Promise<boolean>}
 */
export function slideUp (element, duration = 500) {
    return new Promise(resolve => {
        element.style.height = `${element.offsetHeight}px`
        element.style.transitionProperty = 'height, margin, padding'
        element.style.transitionDuration = `${duration}ms`
        element.offsetHeight // eslint-disable-line no-unused-expressions
        element.style.overflow = 'hidden'
        element.style.height = 0
        element.style.paddingTop = 0
        element.style.paddingBottom = 0
        element.style.marginTop = 0
        element.style.marginBottom = 0
        window.setTimeout(() => {
            element.style.display = 'none'
            element.style.removeProperty('height')
            element.style.removeProperty('padding-top')
            element.style.removeProperty('padding-bottom')
            element.style.removeProperty('margin-top')
            element.style.removeProperty('margin-bottom')
            element.style.removeProperty('overflow')
            element.style.removeProperty('transition-duration')
            element.style.removeProperty('transition-property')
            resolve(element)
        }, duration)
    })
}

/**
 * Permet de vérifier si un élément existe et ensuite fais un traitement
 * @param {HTMLElement} el 
 * @param {callback} callback 
 * @returns 
 */
export function el(el, callback) {
    if(!el) {
        return
    }
    callback(el)
}

/**
 * Graphique
 */
export const createChart = ({
    element,
    type = 'bar',
    labels,
    datasets = [],
    options = {}
}) => {
    return new Chart(element, {
        type: type,
        data: {
            labels: labels,
            datasets: datasets.map(({ label, data, color, ...rest }) => ({
                label,
                data,
                backgroundColor: color,
                borderColor: color,
                ...rest
            }))
        },
        options: {
            responsive: true,
            ...options
        }
    })
}

/**
 * Permet de génèrer un tableau de couleurs aléatoires en hexadécimal
 * @param {number} count - Nombre de couleurs à générer
 * @returns {string[]}
 */
export function randomColor(count) {
    const colors = []
    for (let i = 0; i < count; i++) {
        const color = "#" + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0') // Génère une couleur hexadécimale aléatoire
        colors.push(color)
    }
    return colors
}
// 16777215 correspond à 0xFFFFFF - Toutes les couleurs possibles en hex sur 24 bits
// .toString(16) - Convertit le nombre en hexadécimal
// .padStart(6, '0') - Garantit que le code a toujours 6 caractères

/*
    function randomColor() {
        const r = Math.floor(Math.random() * 256);
        const g = Math.floor(Math.random() * 256);
        const b = Math.floor(Math.random() * 256);
        return rgb(`${r}`, `${g}`, `${b}`);
    }
*/