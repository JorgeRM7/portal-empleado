<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { usePage, router, useForm } from "@inertiajs/vue3";
import Avatar from "primevue/avatar";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Password from "primevue/password";
import Divider from "primevue/divider";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";

const page = usePage();
const toast = useToast();

// ── Datos de sesión ──────────────────────────────────────────────────────────
// Inertia expone el usuario autenticado en page.props.auth.user
// Ajusta los campos según tu modelo User de Laravel
const user = computed(() => page.props.auth?.user ?? {});

const initials = computed(() => {
    const name = user.value.employee.full_name ?? "";
    return name
        .split(" ")
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join("");
});

// ── Cambiar contraseña ───────────────────────────────────────────────────────
const passwordDialog = ref(false);
const passwordLoading = ref(false);
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const submitPassword = () => {
    passwordLoading.value = true;
    passwordForm.put(route("user-password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            passwordDialog.value = false;
            passwordForm.reset();
            toast.add({
                severity: "success",
                summary: "Contraseña actualizada",
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error al actualizar",
                detail: errors.password,
                life: 3500,
            });
        },
        onFinish: () => {
            passwordLoading.value = false;
        },
    });
};

const employeePhoto = computed(() => {
    const employeeId = user.value?.employee?.id ?? user.value?.id;

    return employeeId
        ? `https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${employeeId}.jpg`
        : page.props.auth?.user?.profile_photo_url || "";
});

console.log(page.props.auth);

// ── Cerrar sesión ────────────────────────────────────────────────────────────
const logoutConfirm = ref(false);
const logout = () => router.post(route("logout"));
</script>

<template>
    <AppLayout title="Mi Perfil">
        <Toast />

        <div class="profile-wrap">
            <!-- ══ HERO ══════════════════════════════════════════════════════ -->
            <div class="hero">
                <div class="hero-bg">
                    <div class="orb orb-1"></div>
                    <div class="orb orb-2"></div>
                </div>
                <div class="hero-body">
                    <div class="avatar-ring">
                        <!-- <Avatar
                            :label="initials"
                            shape="circle"
                            class="hero-avatar"
                        /> -->
                        <img
                            :src="employeePhoto"
                            alt="Foto de perfil"
                            class="hero-avatar"
                        />
                    </div>
                    <div class="hero-text">
                        <h1 class="user-name">
                            {{ user.employee?.full_name ?? "—" }}
                        </h1>
                        <p class="user-email">{{ user.email ?? "—" }}</p>
                        <!-- <Tag value="Activo" class="status-tag" /> -->
                    </div>
                </div>
            </div>

            <!-- ══ DATOS DE CUENTA ═══════════════════════════════════════════ -->
            <div class="info-card">
                <p class="section-label">
                    <i class="pi pi-user"></i> Información de cuenta
                </p>
                <div class="info-rows">
                    <div class="info-row">
                        <span class="lbl">Nombre completo</span>
                        <span class="val">{{
                            user.employee?.full_name ?? "—"
                        }}</span>
                    </div>
                    <Divider />
                    <div class="info-row">
                        <span class="lbl">Correo electrónico</span>
                        <span class="val">{{ user.email ?? "—" }}</span>
                    </div>
                    <Divider />
                    <div class="info-row">
                        <span class="lbl">Fecha de Entrada</span>
                        <span class="val">
                            {{
                                user.employee?.entry_date
                                    ? new Date(
                                          user.employee.entry_date.slice(
                                              0,
                                              10,
                                          ) + "T00:00:00",
                                      ).toLocaleDateString("es-MX", {
                                          year: "numeric",
                                          month: "long",
                                          day: "numeric",
                                      })
                                    : "—"
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ══ ACCIONES ══════════════════════════════════════════════════ -->
            <div class="info-card">
                <p class="section-label"><i class="pi pi-cog"></i> Cuenta</p>
                <div class="action-list">
                    <button class="action-row" @click="passwordDialog = true">
                        <span class="action-icon blue"
                            ><i class="pi pi-lock"></i
                        ></span>
                        <span class="action-text">
                            <span class="action-main">Cambiar contraseña</span>
                            <span class="action-hint"
                                >Actualiza tu contraseña de acceso</span
                            >
                        </span>
                        <i class="pi pi-chevron-right chevron"></i>
                    </button>
                    <Divider />
                    <button class="action-row" @click="logoutConfirm = true">
                        <span class="action-icon red"
                            ><i class="pi pi-sign-out"></i
                        ></span>
                        <span class="action-text">
                            <span class="action-main danger-text"
                                >Cerrar sesión</span
                            >
                            <span class="action-hint">Salir de tu cuenta</span>
                        </span>
                        <i class="pi pi-chevron-right chevron"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ DIALOG: Cambiar contraseña ═══════════════════════════════════ -->
        <Dialog
            v-model:visible="passwordDialog"
            header="Cambiar contraseña"
            modal
            :style="{ width: '380px' }"
            :draggable="false"
        >
            <div class="pwd-form">
                <label>Contraseña actual</label>
                <Password
                    v-model="passwordForm.current_password"
                    :feedback="false"
                    toggleMask
                    placeholder="••••••••"
                    class="w-full"
                    inputClass="w-full"
                />
                <span class="text-red-500 text-sm">{{
                    passwordForm.errors.updatePassword.current_password
                }}</span>
                <label>Nueva contraseña</label>
                <Password
                    v-model="passwordForm.password"
                    toggleMask
                    placeholder="••••••••"
                    class="w-full"
                    inputClass="w-full"
                />
                <span class="text-red-500 text-sm">{{
                    passwordForm.errors.updatePassword.password
                }}</span>
                <label>Confirmar contraseña</label>
                <Password
                    v-model="passwordForm.password_confirmation"
                    :feedback="false"
                    toggleMask
                    placeholder="••••••••"
                    class="w-full"
                    inputClass="w-full"
                />
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    text
                    severity="secondary"
                    @click="passwordDialog = false"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    :loading="passwordLoading"
                    @click="submitPassword"
                />
            </template>
        </Dialog>

        <!-- ══ DIALOG: Confirmar logout ══════════════════════════════════════ -->
        <Dialog
            v-model:visible="logoutConfirm"
            header="¿Cerrar sesión?"
            modal
            :style="{ width: '340px' }"
            :draggable="false"
        >
            <p class="logout-msg">
                Tu sesión será terminada. Podrás volver a iniciar sesión cuando
                quieras.
            </p>
            <template #footer>
                <Button
                    label="Cancelar"
                    text
                    severity="secondary"
                    @click="logoutConfirm = false"
                />
                <Button
                    label="Cerrar sesión"
                    severity="danger"
                    icon="pi pi-sign-out"
                    @click="logout"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* ── Contenedor ─────────────────────────────────────────────────────────── */
.profile-wrap {
    margin: 0 auto;
    padding: 0 1rem 3rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ── Hero ───────────────────────────────────────────────────────────────── */
.hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    padding: 2.75rem 2rem 2.5rem;
}
.hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%);
}
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(55px);
    opacity: 0.4;
}
.orb-1 {
    width: 240px;
    height: 240px;
    background: #3b82f6;
    top: -80px;
    right: -40px;
}
.orb-2 {
    width: 160px;
    height: 160px;
    background: #6366f1;
    bottom: -50px;
    left: 30px;
}

.hero-body {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.4rem;
}

.avatar-ring {
    flex-shrink: 0;
    padding: 3px;
    border-radius: 10%;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.55),
        rgba(255, 255, 255, 0.08)
    );
    box-shadow: 0 0 22px rgba(99, 102, 241, 0.5);
}
:deep(.hero-avatar) {
    width: 64px !important;
    height: 78px !important;
    font-size: 1.6rem !important;
    font-weight: 700 !important;
    background: rgba(255, 255, 255, 0.15) !important;
    border-radius: 10%;
}

.hero-text {
    color: white;
}
.user-name {
    margin: 0 0 0.2rem;
    font-size: 1.45rem;
    font-weight: 700;
    letter-spacing: -0.025em;
    line-height: 1.1;
}
.user-email {
    margin: 0 0 0.65rem;
    font-size: 0.85rem;
    opacity: 0.72;
}
:deep(.status-tag .p-tag) {
    font-size: 0.7rem !important;
    background: rgba(34, 197, 94, 0.18) !important;
    color: #86efac !important;
    border: 1px solid rgba(34, 197, 94, 0.28) !important;
}

/* ── Cards ──────────────────────────────────────────────────────────────── */
.info-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 16px;
    padding: 1.25rem 1.4rem;
}

.section-label {
    margin: 0 0 0.85rem;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-color-secondary);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.section-label i {
    font-size: 0.75rem;
}

/* Filas de info */
.info-rows {
    display: flex;
    flex-direction: column;
}
:deep(.info-rows .p-divider) {
    margin: 0.1rem 0;
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.3rem 0;
}
.lbl {
    font-size: 0.83rem;
    color: var(--text-color-secondary);
    flex-shrink: 0;
}
.val {
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--text-color);
    text-align: right;
}

/* Filas de acción */
.action-list {
    display: flex;
    flex-direction: column;
}
:deep(.action-list .p-divider) {
    margin: 0.1rem 0;
}

.action-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.55rem 0.3rem;
    border-radius: 8px;
    text-align: left;
    transition:
        background 0.12s,
        padding 0.12s;
}
.action-row:hover {
    background: var(--surface-hover);
    padding-left: 0.6rem;
}

.action-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.88rem;
}
.action-icon.blue {
    background: #eff6ff;
    color: #2563eb;
}
.action-icon.red {
    background: #fef2f2;
    color: #dc2626;
}

.action-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.action-main {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-color);
    line-height: 1.2;
}
.danger-text {
    color: #dc2626;
}
.action-hint {
    font-size: 0.76rem;
    color: var(--text-color-secondary);
}
.chevron {
    font-size: 0.68rem;
    color: var(--text-color-secondary);
    opacity: 0.45;
}

/* ── Dialog: password form ──────────────────────────────────────────────── */
.pwd-form {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    padding: 0.25rem 0 0.5rem;
}
.pwd-form label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-color-secondary);
    margin-bottom: -0.25rem;
}

/* ── Dialog: logout msg ─────────────────────────────────────────────────── */
.logout-msg {
    margin: 0;
    color: var(--text-color-secondary);
    font-size: 0.88rem;
    line-height: 1.5;
}

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 480px) {
    .hero {
        padding: 2rem 1.25rem;
    }
    .user-name {
        font-size: 1.2rem;
    }
    .profile-wrap {
        padding: 0 0.75rem 2rem;
    }
}
</style>
