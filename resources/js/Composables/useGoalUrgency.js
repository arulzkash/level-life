import { computed } from 'vue';
import { GOAL_COPY } from '@/Utils/featureCopy';

export function useGoalUrgency() {
    const getLocalYMD = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return dateString.split('T')[0];
        const offset = d.getTimezoneOffset() * 60000;
        return new Date(d.getTime() - offset).toISOString().slice(0, 10);
    };

    const getTodayStr = () => {
        return getLocalYMD(new Date().toISOString());
    };

    const toStartOfDay = (dateStr) => {
        const d = new Date(dateStr);
        d.setHours(0, 0, 0, 0);
        return d;
    };

    const diffDaysFromToday = (targetDateStr, todayStr) => {
        const today = toStartOfDay(todayStr);
        const target = toStartOfDay(targetDateStr);
        const diffTime = target - today;
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    };

    // Helper untuk format tanggal (contoh: "25 Feb")
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    };

    const analyzeGoal = (goal) => {
        const todayStr = getTodayStr();
        
        // Normalize all milestones to have a locally-accurate YYYY-MM-DD string for comparison
        const milestones = Array.isArray(goal.milestones) ? goal.milestones.map(m => ({
            ...m,
            _localYMD: m.due_date ? getLocalYMD(m.due_date) : ''
        })) : [];

        const completedMilestones = milestones.filter((m) => m.is_completed);
        const incompleteMilestones = milestones.filter((m) => !m.is_completed);

        const progress = milestones.length > 0 ? Math.round((completedMilestones.length / milestones.length) * 100) : 0;

        const sortedIncomplete = [...incompleteMilestones]
            .filter((m) => m._localYMD)
            .sort((a, b) => a._localYMD.localeCompare(b._localYMD));

        const nearestMilestone = sortedIncomplete[0] || null;
        const nearestDate = nearestMilestone?.due_date || goal.deadline || null;
        const nearestLocalYMD = nearestMilestone?._localYMD || (goal.deadline ? getLocalYMD(goal.deadline) : null);

        const overdueMilestones = incompleteMilestones.filter((m) => m._localYMD && m._localYMD < todayStr);

        const overdueCount = overdueMilestones.length;
        const incompleteCount = incompleteMilestones.length;

        // Use local YMD to calculate days left safely, instead of potentially off-by-one raw timestamps
        const daysLeft = nearestLocalYMD ? diffDaysFromToday(nearestLocalYMD, todayStr) : 9999;
        const goalLocalYMD = goal.deadline ? getLocalYMD(goal.deadline) : null;
        const goalDaysLeft = goalLocalYMD ? diffDaysFromToday(goalLocalYMD, todayStr) : 9999;
        const isGoalOverdue = !!goalLocalYMD && goalLocalYMD < todayStr;

        let level = 1;
        let stateName = 'SAFE';
        let stateMessage = 'On Track';

        if ((isGoalOverdue && progress < 100) || overdueCount >= 2 || daysLeft <= 0) {
            level = 4;
            stateName = 'CRITICAL';
            if (isGoalOverdue && progress < 100) {
                stateMessage = 'Goal deadline missed';
            } else if (overdueCount >= 2) {
                stateMessage = `${overdueCount} overdue milestones`;
            } else {
                stateMessage = daysLeft === 0 
                    ? (nearestMilestone ? 'Milestone due today' : 'Goal due today') 
                    : (nearestMilestone ? 'Milestone overdue' : 'Goal overdue');
            }
        } else if (overdueCount === 1 || daysLeft <= 2) {
            level = 3;
            stateName = 'DANGER';
            if (overdueCount === 1) {
                stateMessage = '1 overdue milestone';
            } else {
                stateMessage = daysLeft === 1 ? '1 day left' : `${daysLeft} days left`;
            }
        } else if (daysLeft <= 5) {
            level = 2;
            stateName = 'WARNING';
            stateMessage = `${daysLeft} days buffer`;
        } else {
            level = 1;
            stateName = 'SAFE';
            stateMessage = `${daysLeft} days buffer`;
        }

        return {
            id: goal.id,
            rawGoal: goal,
            progress,
            level,
            stateName,
            stateMessage,
            stateHelper: GOAL_COPY.urgencyHelpers[stateName] || '',
            daysLeft,
            goalDaysLeft,
            nearestDate,
            nearestMilestone,
            overdueCount,
            incompleteCount,
            isGoalOverdue
        };
    };

    const sortGoalsByUrgency = (goals = []) => {
        return [...goals].map(analyzeGoal).sort((a, b) => {
            if (b.level !== a.level) return b.level - a.level;
            if (a.daysLeft !== b.daysLeft) return a.daysLeft - b.daysLeft;
            return a.progress - b.progress;
        });
    };

    return { analyzeGoal, sortGoalsByUrgency, formatDate, getLocalYMD };
}
