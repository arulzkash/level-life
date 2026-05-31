<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    vapidPublicKey: {
        type: String,
        default: null,
    },
    initialSubscriptionCount: {
        type: Number,
        default: 0,
    },
    initialSettings: {
        type: Object,
        default: () => ({
            morning_enabled: false,
            evening_enabled: false,
        }),
    },
});

const isSupported = ref(false);
const permission = ref(typeof Notification === 'undefined' ? 'unsupported' : Notification.permission);
const isSubscribed = ref(props.initialSubscriptionCount > 0);
const statusMessage = ref('');
const isBusy = ref(false);
const isSavingSettings = ref(false);
const subscriptionCount = ref(props.initialSubscriptionCount);
const reminderSettings = ref({
    morning_enabled: Boolean(props.initialSettings?.morning_enabled),
    evening_enabled: Boolean(props.initialSettings?.evening_enabled),
});

const canUseNotifications = computed(() => isSupported.value && Boolean(props.vapidPublicKey));
const buttonLabel = computed(() => {
    if (isBusy.value) {
        return 'Working...';
    }

    return isSubscribed.value ? 'Disable Notifications' : 'Enable Notifications';
});

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
};

const getRegistration = async () => {
    const existing = await navigator.serviceWorker.getRegistration('/sw.js');

    if (existing) {
        return existing;
    }

    return navigator.serviceWorker.register('/sw.js', { scope: '/' });
};

const refreshSubscriptionState = async () => {
    if (!canUseNotifications.value) {
        return;
    }

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');
    const subscription = await registration?.pushManager.getSubscription();

    isSubscribed.value = Boolean(subscription);
    permission.value = Notification.permission;
};

const subscribe = async () => {
    if (!canUseNotifications.value) {
        statusMessage.value = props.vapidPublicKey
            ? 'Push notifications are not supported in this browser.'
            : 'VAPID public key is not configured yet.';
        return;
    }

    isBusy.value = true;
    statusMessage.value = '';

    try {
        const registration = await getRegistration();
        const grantedPermission = await Notification.requestPermission();
        permission.value = grantedPermission;

        if (grantedPermission !== 'granted') {
            statusMessage.value = 'Notification permission was not granted.';
            return;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(props.vapidPublicKey),
        });

        const response = await window.axios.post('/push-subscriptions', {
            ...subscription.toJSON(),
            contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
        });

        isSubscribed.value = true;
        subscriptionCount.value = response.data.subscriptionCount ?? 1;
        statusMessage.value = 'Notifications are enabled on this device.';
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to enable notifications.';
    } finally {
        isBusy.value = false;
    }
};

const unsubscribe = async () => {
    isBusy.value = true;
    statusMessage.value = '';

    try {
        const registration = await navigator.serviceWorker.getRegistration('/sw.js');
        const subscription = await registration?.pushManager.getSubscription();

        if (subscription) {
            await window.axios.delete('/push-subscriptions', {
                data: { endpoint: subscription.endpoint },
            });

            await subscription.unsubscribe();
        }

        isSubscribed.value = false;
        subscriptionCount.value = Math.max(0, subscriptionCount.value - 1);
        statusMessage.value = 'Notifications are disabled on this device.';
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to disable notifications.';
    } finally {
        isBusy.value = false;
    }
};

const toggleNotifications = () => {
    if (isSubscribed.value) {
        return unsubscribe();
    }

    return subscribe();
};

const sendTestNotification = async () => {
    isBusy.value = true;
    statusMessage.value = '';

    try {
        const response = await window.axios.post('/push-subscriptions/test');
        subscriptionCount.value = response.data.subscriptionCount ?? subscriptionCount.value;
        statusMessage.value = response.data.message || 'Test notification sent.';
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to send test notification.';
    } finally {
        isBusy.value = false;
    }
};

const saveReminderSettings = async () => {
    isSavingSettings.value = true;
    statusMessage.value = '';

    try {
        const response = await window.axios.patch('/push-subscriptions/settings', reminderSettings.value);
        reminderSettings.value = {
            morning_enabled: Boolean(response.data.settings?.morning_enabled),
            evening_enabled: Boolean(response.data.settings?.evening_enabled),
        };
        statusMessage.value = 'Reminder settings saved.';
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to save reminder settings.';
    } finally {
        isSavingSettings.value = false;
    }
};

onMounted(async () => {
    isSupported.value = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    if (!isSupported.value) {
        statusMessage.value = 'Push notifications are not supported in this browser.';
        return;
    }

    await refreshSubscriptionState();
});
</script>

<template>
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-black tracking-tight text-white">Notifications</h2>
            <p class="mt-1 text-sm leading-6 text-slate-400">
                Enable push notifications on this device and send a manual test notification.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
            <dl class="grid gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Browser</dt>
                    <dd class="mt-1 font-semibold text-slate-200">{{ isSupported ? 'Supported' : 'Unsupported' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Permission</dt>
                    <dd class="mt-1 font-semibold capitalize text-slate-200">{{ permission }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Saved devices</dt>
                    <dd class="mt-1 font-semibold text-slate-200">{{ subscriptionCount }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                :disabled="isBusy || !canUseNotifications"
                class="inline-flex items-center rounded-full border border-sky-300/25 bg-sky-400/10 px-4 py-2 text-sm font-semibold text-sky-100 transition hover:border-sky-200/40 hover:bg-sky-400/15 disabled:cursor-not-allowed disabled:opacity-50"
                @click="toggleNotifications"
            >
                {{ buttonLabel }}
            </button>

            <button
                type="button"
                :disabled="isBusy || !isSubscribed"
                class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                @click="sendTestNotification"
            >
                Send Test Notification
            </button>
        </div>

        <div class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
            <label class="flex items-start gap-3">
                <input
                    v-model="reminderSettings.morning_enabled"
                    type="checkbox"
                    :disabled="!isSubscribed || isSavingSettings"
                    class="mt-1 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500 disabled:opacity-50"
                    @change="saveReminderSettings"
                >
                <span>
                    <span class="block text-sm font-semibold text-slate-100">Morning reminder</span>
                    <span class="block text-sm leading-6 text-slate-400">Send a 07:00 WIB reminder when the internal trigger runs.</span>
                </span>
            </label>

            <label class="flex items-start gap-3">
                <input
                    v-model="reminderSettings.evening_enabled"
                    type="checkbox"
                    :disabled="!isSubscribed || isSavingSettings"
                    class="mt-1 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500 disabled:opacity-50"
                    @change="saveReminderSettings"
                >
                <span>
                    <span class="block text-sm font-semibold text-slate-100">Evening streak reminder</span>
                    <span class="block text-sm leading-6 text-slate-400">Send an 18:00 WIB reminder only if no quest was completed today.</span>
                </span>
            </label>
        </div>

        <p v-if="statusMessage" class="text-sm leading-6 text-slate-300">
            {{ statusMessage }}
        </p>
    </div>
</template>
