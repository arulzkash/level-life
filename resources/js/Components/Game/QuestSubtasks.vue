<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { useAudio } from '@/Composables/useAudio';

const props = defineProps({
    quest: Object, 
});

const { playSfx } = useAudio();
const inputRef = ref(null); 
const focusedTaskId = ref(null); 
const originalTitleCache = ref('');

// --- STATE ---
const localSubtasks = ref(props.quest.subtasks ? JSON.parse(JSON.stringify(props.quest.subtasks)) : []);
const isExpanded = ref(false);
const newSubtaskTitle = ref('');

// --- COMPUTED ---
const totalTasks = computed(() => localSubtasks.value.length);
const completedTasks = computed(() => localSubtasks.value.filter(t => t.is_done).length);
const progressPercent = computed(() => {
    if (totalTasks.value === 0) return 0;
    return Math.round((completedTasks.value / totalTasks.value) * 100);
});

const generateId = () => 'temp_' + Math.random().toString(36).substr(2, 9);

// --- GLOBAL DEBOUNCE SAVER (HEMAT RU) ---
// Apapun aksinya (Add, Del, Edit-Enter, Toggle), semua lewat pintu ini.
// Request baru ditembak jika user "diam" selama 1000ms.
const queueSave = debounce(() => {
  router.patch(
    `/quests/${props.quest.id}`,
    { subtasks: localSubtasks.value },
    { preserveScroll: true, preserveState: true }
  );
}, 1000);

// --- ACTIONS ---

const openAndFocus = async () => {
    isExpanded.value = true;
    await nextTick();
    if (inputRef.value) inputRef.value.focus();
};

const add = () => {
    if (!newSubtaskTitle.value.trim()) return;
    
    localSubtasks.value.push({
        id: generateId(),
        title: newSubtaskTitle.value,
        is_done: false
    });
    
    newSubtaskTitle.value = '';
    isExpanded.value = true; 
    playSfx('click'); 
    
    // Masuk antrian debounce
    queueSave(); 
    
    nextTick(() => {
        if(inputRef.value) inputRef.value.focus();
    });
};

const toggle = (task) => {
    task.is_done = !task.is_done;
    if (task.is_done) playSfx('toggle-habit');
    
    // Masuk antrian debounce
    queueSave(); 
};

const remove = (index) => {
    localSubtasks.value.splice(index, 1);
    
    // Masuk antrian debounce
    queueSave(); 
    
    if (localSubtasks.value.length === 0) {
        isExpanded.value = false;
    }
};

// --- EDIT LOGIC ---

const startEditing = (task) => {
    focusedTaskId.value = task.id;
    originalTitleCache.value = task.title;
};

const finishEditing = (task) => {
    focusedTaskId.value = null;
    
    // Dirty Check: Hanya save kalau teks berubah
    if (task.title.trim() !== originalTitleCache.value.trim()) {
        queueSave(); // Masuk antrian debounce
    }
};

// --- SMART WATCHER (REVISI PRODUCTION) ---
watch(() => props.quest.subtasks, (newVal) => {
    // 1. Hitung jumlah 'Done' SEBELUM update data lokal
    const oldDoneCount = localSubtasks.value.filter(t => t.is_done).length;

    // 2. Update data lokal agar sinkron dengan server
    // Gunakan JSON parse/stringify untuk memutus referensi object (deep clone)
    localSubtasks.value = newVal ? JSON.parse(JSON.stringify(newVal)) : [];

    // 3. Hitung jumlah 'Done' SETELAH update (dari data baru server)
    const newDoneCount = localSubtasks.value.filter(t => t.is_done).length;

    // 4. Deteksi "Hard Reset" (Fitur Daily Grind Complete)
    // Logika: Tutup HANYA jika dulunya ada yang selesai (> 0), tapi sekarang jadi nol semua.
    // Skenario Add Subtask: 0 -> 0 (Aman, tidak akan nutup).
    // Skenario Reset Daily: 5 -> 0 (Kena kondisi ini, Auto Close).
    const isReset = oldDoneCount > 0 && newDoneCount === 0;

    if (isReset) {
        isExpanded.value = false;
    }
    
    // (Opsional) Defensive Coding: 
    // Jika user sedang mengetik (Add), pastikan tetap terbuka walau server refresh
    if (newSubtaskTitle.value) {
        isExpanded.value = true;
    }

}, { deep: true });

</script>

<template>
    <div class="mt-4 w-full select-none">
        
        <div 
            v-if="totalTasks > 0"
            @click="isExpanded = !isExpanded"
            class="group/bar flex cursor-pointer items-center gap-3 rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 transition-all hover:bg-slate-800"
        >
            <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-slate-800 shadow-inner">
                <div 
                    class="absolute left-0 top-0 h-full transition-all duration-500 ease-out"
                    :class="progressPercent === 100 ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-gradient-to-r from-indigo-600 to-purple-500'"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>
            
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                <span :class="progressPercent === 100 ? 'text-emerald-400' : ''">
                    {{ completedTasks }}/{{ totalTasks }}
                </span>
                <span 
                    class="text-slate-600 transition-transform duration-300 group-hover/bar:text-slate-200"
                    :class="{ 'rotate-180': isExpanded }"
                >
                    ▼
                </span>
            </div>
        </div>

        <div v-else>
            <button 
                v-if="!isExpanded"
                @click="openAndFocus"
                class="group/add flex items-center gap-1.5 rounded px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600 transition-colors hover:bg-slate-800 hover:text-indigo-400"
            >
                <span class="text-lg leading-none opacity-50 group-hover/add:opacity-100">+</span>
                <span>Subtasks</span>
            </button>
        </div>

        <div v-if="isExpanded" class="animate-slide-down mt-2 space-y-1 pl-1">
            
            <div 
                v-for="(task, index) in localSubtasks" 
                :key="task.id || index"
                class="group flex items-start gap-3 py-1"
            >
                <button 
                    @click="toggle(task)"
                    class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded border transition-all"
                    :class="task.is_done ? 'border-emerald-500 bg-emerald-500/20 shadow-[0_0_5px_rgba(16,185,129,0.3)]' : 'border-slate-600 bg-slate-800 hover:border-slate-400'"
                >
                    <span v-if="task.is_done" class="text-[10px] text-emerald-400">✓</span>
                </button>

                <div class="relative w-full">
                    <input 
                        v-model="task.title"
                        @focus="startEditing(task)"
                        @blur="finishEditing(task)"
                        @keydown.enter="$event.target.blur()"
                        class="w-full bg-transparent p-0 text-xs text-slate-300 outline-none transition-all placeholder-slate-600 border-none focus:ring-0"
                        :class="[
                            task.is_done ? 'line-through text-slate-500 decoration-slate-600' : 'font-medium',
                        ]"
                    />
                    
                    <span 
                        v-if="focusedTaskId === (task.id || index)"
                        class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-[10px] text-indigo-400 opacity-80 animate-pulse bg-slate-900 px-1 rounded"
                    >
                        ⏎
                    </span>
                </div>

                <button 
                    @click="remove(index)"
                    class="hidden text-slate-600 hover:text-red-400 group-hover:block"
                >
                    ×
                </button>
            </div>

            <div class="flex items-center gap-3 py-1 opacity-60 transition-opacity hover:opacity-100">
                <div class="h-4 w-4 flex-shrink-0 rounded border border-dashed border-slate-600 bg-transparent"></div>
                <input 
                    ref="inputRef"
                    v-model="newSubtaskTitle"
                    @keydown.enter.prevent="add"
                    @blur="() => { if(totalTasks === 0 && !newSubtaskTitle) isExpanded = false; }"
                    placeholder="Add step..."
                    class="w-full bg-transparent p-0 text-xs italic text-slate-500 placeholder-slate-600 focus:ring-0 border-none outline-none"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-down {
    animation: slideDown 0.2s ease-out forwards;
    transform-origin: top;
}
@keyframes slideDown {
    from { opacity: 0; transform: scaleY(0.95); }
    to { opacity: 1; transform: scaleY(1); }
}
</style>