// resources/js/Composables/useAudio.js

const sounds = {
    // Mapping nama suara ke file di public folder
    slash: new Audio('/audio/slash.mp3'),
    coin: new Audio('/audio/coin.mp3'),
    levelup: new Audio('/audio/levelup.mp3'),
    complete: new Audio('/audio/complete.mp3'),
    purchase: new Audio('/audio/purchase.mp3'),
    'toggle-habit': new Audio('/audio/toggle-habit.mp3'),
    typing: new Audio('/audio/typing.mp3'),
};

// Preload volume (biar gak bikin kaget)
Object.values(sounds).forEach((s) => (s.volume = 0.5));

export function useAudio() {
    const playSfx = (name) => {
        const audio = sounds[name];
        if (audio) {
            // Reset waktu biar bisa dispam (kalau diklik cepet)
            audio.currentTime = 0;
            audio.play().catch((e) => console.warn('Audio blocked:', e));
        }
    };

    return { playSfx };
}
