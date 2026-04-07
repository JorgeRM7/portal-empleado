import { ref } from "vue";
import axios from "axios";

const unreadCount = ref(0);
const unread = ref([]);
const all = ref([]);
const loading = ref(false);
const error = ref(null);

async function fetchNotifications() {
    try {
        loading.value = true;
        const { data } = await axios.get("/notifications");
        unreadCount.value = data.unread_count;
        unread.value = data.unread;
        all.value = data.all;
    } catch (e) {
        error.value = e.message ?? "Error cargando notificaciones";
    } finally {
        loading.value = false;
    }
}

async function markAsRead(id) {
    await axios.post(`/notifications/${id}/read`);
    await fetchNotifications();
}

async function markAllAsRead() {
    await axios.post("/notifications/read-all");
    await fetchNotifications();
}

async function sendNotification() {
    unreadCount.value = unreadCount.value + 1;
    fetchNotifications();
}

export function useNotifications() {
    return {
        unreadCount,
        unread,
        all,
        loading,
        error,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        sendNotification,
    };
}
