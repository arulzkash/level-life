const BADGE_ICONS = {
    streak_3: '👞',
    streak_7: '🔥',
    streak_14: '⚔️',
    streak_30: '🛡️',
    streak_60: '💎',
    streak_100: '👑',
    streak_150: '🗿',
    streak_200: '🐉',
    streak_365: '🌟',
    streak_500: '🌠',
    second_wind: '🍃',
    comeback_kid: '❤️‍🔥',
};

export function getBadgeIcon(key) {
    return BADGE_ICONS[key] || '🎖️';
}
