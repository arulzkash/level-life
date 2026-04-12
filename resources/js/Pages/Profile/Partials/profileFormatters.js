export const parseDate = (dateString) => {
    if (!dateString) {
        return null;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        const [year, month, day] = dateString.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    return new Date(dateString);
};

export const formatDate = (dateString, fallback = 'Unknown') => {
    const date = parseDate(dateString);

    if (!date || Number.isNaN(date.getTime())) {
        return fallback;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

export const dayTooltip = (day) => {
    const label = formatDate(day.date, day.date);

    if (day.is_future) {
        return `${label} - future`;
    }

    const questWord = day.count === 1 ? 'quest' : 'quests';

    return `${label} - ${day.count} ${questWord} completed`;
};

export const heatLevelClass = (level, isFuture = false) => {
    if (isFuture) {
        return 'border border-dashed border-slate-700/80 bg-slate-950/30';
    }

    const map = {
        0: 'bg-slate-800 ring-1 ring-inset ring-slate-700/60',
        1: 'bg-sky-950 ring-1 ring-inset ring-sky-900/50',
        2: 'bg-sky-800 ring-1 ring-inset ring-sky-700/50',
        3: 'bg-sky-500/80 ring-1 ring-inset ring-sky-300/60 shadow-[0_0_10px_rgba(56,189,248,0.10)]',
        4: 'bg-cyan-300 ring-1 ring-inset ring-cyan-100/80 shadow-[0_0_12px_rgba(103,232,249,0.16)]',
    };

    return map[level] || map[0];
};

export const buildStatusTone = (status) => {
    if (status === 'On Fire') {
        return {
            pill: 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100',
            glow: 'shadow-[0_0_30px_rgba(34,211,238,0.10)]',
            accent: 'text-cyan-200',
            icon: '🔥',
            helper: 'You are active today.',
        };
    }

    if (status === 'Pending') {
        return {
            pill: 'border-sky-300/30 bg-sky-400/10 text-sky-100',
            glow: 'shadow-[0_0_30px_rgba(56,189,248,0.10)]',
            accent: 'text-sky-200',
            icon: '⏳',
            helper: 'Keep it alive before rollover.',
        };
    }

    return {
        pill: 'border-slate-700 bg-slate-900 text-slate-300',
        glow: '',
        accent: 'text-slate-200',
        icon: '❄️',
        helper: 'No activity recorded today.',
    };
};