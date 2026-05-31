/**
 * Date formatting utility functions.
 * Pure functions for formatting dates and calculating urgency.
 */

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/**
 * Parse a date string into a local-timezone Date at midnight.
 * Handles ISO 8601 strings and YYYY-MM-DD format.
 * @param {string} dateString
 * @returns {Date|null}
 */
function parseDate(dateString) {
    if (!dateString) return null;
    // For YYYY-MM-DD format, parse as local date (not UTC)
    const ymdMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (ymdMatch) {
        const d = new Date(Number(ymdMatch[1]), Number(ymdMatch[2]) - 1, Number(ymdMatch[3]));
        d.setHours(0, 0, 0, 0);
        return isNaN(d.getTime()) ? null : d;
    }
    // For ISO or other formats, convert to local YMD first
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return null;
    const local = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    local.setHours(0, 0, 0, 0);
    return local;
}

/**
 * Get today's date at midnight (local timezone).
 * @returns {Date}
 */
function getToday() {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    return now;
}

/**
 * Calculate the difference in days between a target date and today.
 * Positive = future, negative = past, 0 = today.
 * @param {Date} targetDate
 * @returns {number}
 */
function diffDaysFromToday(targetDate) {
    const today = getToday();
    const diffTime = targetDate.getTime() - today.getTime();
    return Math.round(diffTime / (1000 * 60 * 60 * 24));
}

/**
 * Format a date string as "DD Mon YYYY" (e.g., "15 Jan 2025").
 * @param {string} dateString - ISO 8601 or YYYY-MM-DD date string
 * @returns {string} Formatted date or '-' if invalid
 */
export function formatDate(dateString) {
    const date = parseDate(dateString);
    if (!date) return '-';
    const day = date.getDate();
    const month = MONTHS[date.getMonth()];
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
}

/**
 * Get a descriptive string for how many days are left until a due date.
 * @param {string} dateString - ISO 8601 or YYYY-MM-DD date string
 * @returns {string} Descriptive string like "Due today", "Due tomorrow", "X days left", "X days overdue"
 */
export function getDaysLeft(dateString) {
    const date = parseDate(dateString);
    if (!date) return '';
    const diffDays = diffDaysFromToday(date);

    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    return `${diffDays} days left`;
}

/**
 * Get a Tailwind CSS class string based on date urgency.
 * @param {string} dateString - ISO 8601 or YYYY-MM-DD date string
 * @returns {string} Tailwind urgency class
 */
export function getDaysLeftClass(dateString) {
    const date = parseDate(dateString);
    if (!date) return 'text-slate-500';
    const diffDays = diffDaysFromToday(date);

    if (diffDays < 0) return 'text-red-700 font-bold';
    if (diffDays === 0) return 'text-red-500 font-bold';
    if (diffDays <= 2) return 'text-orange-400 font-bold';
    if (diffDays <= 5) return 'text-amber-400 font-bold';
    return 'text-emerald-400 font-medium';
}

/**
 * Get a combined display string with formatted date and days-left indicator.
 * @param {string} dateString - ISO 8601 or YYYY-MM-DD date string
 * @returns {string} Combined string like "15 Jan 2025 (Due tomorrow)"
 */
export function formatDaysLeft(dateString) {
    const formatted = formatDate(dateString);
    const daysLeft = getDaysLeft(dateString);
    if (!daysLeft) return formatted;
    return `${formatted} (${daysLeft})`;
}
