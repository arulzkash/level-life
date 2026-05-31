/**
 * Built-in journal templates with predefined section structures.
 */
export const BUILT_IN_TEMPLATES = [
    { name: 'Daily Review', sections: [{ title: 'Gratitude (3)' }, { title: 'Top 3 Wins' }, { title: 'Challenges / Friction' }, { title: 'Lessons / Insight' }, { title: 'Tomorrow (Top 3)' }, { title: 'One-line summary' }] },
    { name: 'Morning Plan', sections: [{ title: 'Intention' }, { title: 'Must-Do (1–3)' }, { title: 'Nice-to-Have' }, { title: 'If-Then Plan' }, { title: 'Distractions to avoid' }] },
    { name: '2-Min Check-in', sections: [{ title: 'Right now I feel…' }, { title: 'One win' }, { title: 'One next step' }] },
    { name: 'Gratitude', sections: [{ title: '3 Gratitudes' }, { title: 'One person I appreciate' }, { title: 'One small joy' }] },
    { name: 'Brain Dump', sections: [{ title: 'Stream of thoughts (no filter)' }] },
    { name: 'CBT Thought Record', sections: [{ title: 'Situation' }, { title: 'Automatic thoughts' }, { title: 'Feelings (0–100)' }, { title: 'Evidence for' }, { title: 'Evidence against' }, { title: 'Balanced thought' }, { title: 'Next action' }] },
    { name: 'Stoic Reflection', sections: [{ title: 'What was in my control' }, { title: "What wasn't" }, { title: 'What I did well' }, { title: 'What to improve tomorrow' }] },
    { name: 'Health & Energy', sections: [{ title: 'Sleep / Energy' }, { title: 'Food' }, { title: 'Movement' }, { title: 'Stress + source' }, { title: 'One small health win tomorrow' }] },
    { name: 'Idea to Ship', sections: [{ title: 'Idea dump' }, { title: 'One idea to ship' }, { title: 'Next step' }, { title: 'Things to research' }] },
    { name: 'Weekly Review', sections: [{ title: 'Top wins' }, { title: 'Top lessons' }, { title: 'Energized me' }, { title: 'Drained me' }, { title: 'Stop / Start / Continue' }, { title: 'Next week focus' }] },
];

/**
 * Map built-in templates to the insert options format with IDs.
 */
export const builtInTemplateOptions = BUILT_IN_TEMPLATES.map((t) => ({
    id: `builtin:${t.name}`,
    name: t.name,
    sections: t.sections,
}));
