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
    showTestNotification: {
        type: Boolean,
        default: false,
    },
});

const isSupported = ref(false);
const permission = ref(typeof Notification === 'undefined' ? 'unsupported' : Notification.permission);
const browserSubscription = ref(null);
const browserSubscribed = ref(false);
const accountSubscribed = ref(false);
const statusMessage = ref('');
const isBusy = ref(false);
const isSavingSettings = ref(false);
const subscriptionCount = ref(props.initialSubscriptionCount);
const reminderSettings = ref({
    morning_enabled: Boolean(props.initialSettings?.morning_enabled),
    evening_enabled: Boolean(props.initialSettings?.evening_enabled),
});

const canUseNotifications = computed(() => isSupported.value && Boolean(props.vapidPublicKey));
const notificationState = computed(() => {
    if (!isSupported.value) {
        return 'unsupported';
    }

    if (!props.vapidPublicKey) {
        return 'missing_vapid_key';
    }

    if (permission.value === 'denied') {
        return 'permission_denied';
    }

    if (browserSubscribed.value && accountSubscribed.value) {
        return 'browser_and_account_subscribed';
    }

    if (browserSubscribed.value && !accountSubscribed.value) {
        return 'browser_subscribed_account_not_subscribed';
    }

    if (permission.value === 'default') {
        return 'permission_default';
    }

    return 'not_subscribed';
});
const stateLabel = computed(() => {
    const labels = {
        unsupported: 'Push notifications are not supported in this browser.',
        missing_vapid_key: 'VAPID public key is not configured yet.',
        permission_denied: 'Notifications are blocked in this browser. Allow them in site settings to enable push notifications.',
        permission_default: 'Notifications are not enabled yet. The browser will ask for permission when you enable them.',
        browser_and_account_subscribed: 'Notifications are enabled for this account on this device.',
        browser_subscribed_account_not_subscribed: 'Notifications are available on this device, but not enabled for this account.',
        not_subscribed: 'Notifications are not enabled on this device.',
    };

    return labels[notificationState.value];
});
const buttonLabel = computed(() => {
    if (isBusy.value) {
        return 'Working...';
    }

    if (accountSubscribed.value) {
        return 'Disable Notifications';
    }

    if (browserSubscribed.value) {
        return 'Enable for this account';
    }

    return 'Enable Notifications';
});
const primaryButtonDisabled = computed(() => (
    isBusy.value
    || !canUseNotifications.value
    || permission.value === 'denied'
));

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

    browserSubscription.value = subscription || null;
    browserSubscribed.value = Boolean(subscription);
    accountSubscribed.value = false;
    permission.value = Notification.permission;

    if (!subscription) {
        return;
    }

    const response = await window.axios.post('/push-subscriptions/status', {
        endpoint: subscription.endpoint,
    });

    accountSubscribed.value = Boolean(response.data.endpointSubscribed);
    subscriptionCount.value = response.data.subscriptionCount ?? subscriptionCount.value;
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
        let subscription = browserSubscription.value || await registration.pushManager.getSubscription();

        if (!subscription) {
            const grantedPermission = await Notification.requestPermission();
            permission.value = grantedPermission;

            if (grantedPermission !== 'granted') {
                statusMessage.value = 'Notification permission was not granted.';
                return;
            }

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(props.vapidPublicKey),
            });
        }

        const response = await window.axios.post('/push-subscriptions', {
            ...subscription.toJSON(),
            contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
        });

        browserSubscription.value = subscription;
        browserSubscribed.value = true;
        accountSubscribed.value = true;
        subscriptionCount.value = response.data.subscriptionCount ?? 1;
        statusMessage.value = 'Notifications are enabled for this account on this device.';
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
        const subscription = browserSubscription.value || await registration?.pushManager.getSubscription();
        let response = null;

        if (subscription) {
            response = await window.axios.delete('/push-subscriptions', {
                data: { endpoint: subscription.endpoint },
            });

            if (accountSubscribed.value) {
                await subscription.unsubscribe();
            }
        }

        browserSubscription.value = null;
        browserSubscribed.value = false;
        accountSubscribed.value = false;
        subscriptionCount.value = response.data.subscriptionCount ?? Math.max(0, subscriptionCount.value - 1);
        statusMessage.value = 'Notifications are disabled for this account on this device.';
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to disable notifications.';
    } finally {
        isBusy.value = false;
    }
};

const toggleNotifications = () => {
    if (accountSubscribed.value) {
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

    try {
        await refreshSubscriptionState();
    } catch (error) {
        statusMessage.value = error.response?.data?.message || 'Failed to check notification status.';
    }
});
</script>

<template>
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-black tracking-tight text-white">Notifications</h2>
            <p class="mt-1 text-sm leading-6 text-slate-400">
                Enable push notifications on this device and manage your daily reminders.
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
                :disabled="primaryButtonDisabled"
                class="inline-flex items-center rounded-full border border-sky-300/25 bg-sky-400/10 px-4 py-2 text-sm font-semibold text-sky-100 transition hover:border-sky-200/40 hover:bg-sky-400/15 disabled:cursor-not-allowed disabled:opacity-50"
                @click="toggleNotifications"
            >
                {{ buttonLabel }}
            </button>

            <button
                v-if="showTestNotification"
                type="button"
                :disabled="isBusy || !accountSubscribed"
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
                    :disabled="!accountSubscribed || isSavingSettings"
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
                    :disabled="!accountSubscribed || isSavingSettings"
                    class="mt-1 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500 disabled:opacity-50"
                    @change="saveReminderSettings"
                >
                <span>
                    <span class="block text-sm font-semibold text-slate-100">Evening streak reminder</span>
                    <span class="block text-sm leading-6 text-slate-400">Send a 19:00 WIB reminder only if no quest was completed today.</span>
                </span>
            </label>
        </div>

        <p class="text-sm leading-6 text-slate-300">
            {{ statusMessage || stateLabel }}
        </p>
    </div>
</template>
