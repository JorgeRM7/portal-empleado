<script>
import { onMounted, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";

export default {
    name: "Aside",
    methods: {
        logout() {
            router.post(route("logout"));
        },
    },
    components: {
        Link,
    },
    props: {
        user: {
            type: Object,
            default: () => ({
                avatar: "assets/media/avatars/300-1.jpg",
                name: "Nombre de Usuario",
                role: "Rol de Usuario",
                profileLink: "/user-profile",
            }),
        },
    },
};
</script>

<template>
    <div
        id="kt_aside"
        class="aside pt-7 pb-4 pb-lg-7 pt-lg-17 aside-dark shadow"
        data-kt-drawer="true"
        data-kt-drawer-name="aside"
        data-kt-drawer-activate="{default: true, lg: false}"
        data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'200px', '300px': '250px'}"
        data-kt-drawer-direction="start"
        data-kt-drawer-toggle="#kt_aside_toggle"
    >
        <!--begin::Brand-->

        <!--end::Brand-->
        <!--begin::Aside user-->
        <div class="aside-user mb-5 mb-lg-10" id="kt_aside_user">
            <!--begin::User-->
            <div class="d-flex align-items-center flex-column">
                <!--begin::Symbolll-->
                <div class="symbol symbol-75px mb-4">
                    <img
                        :src="$page.props.auth.user?.profile_photo_url"
                        alt=""
                        class="object-cover"
                    />
                </div>
                <!--end::Symbol-->
                <!--begin::Info-->
                <div class="text-center mb-10">
                    <!--begin::Username-->
                    <Link
                        href="/user/profile"
                        class="hover:text-[#ed6d24] fs-4 fw-bolder"
                        >{{ $page.props.auth.user?.name }}</Link
                    >

                    <!--end::Username-->

                    <div class="text-muted fw-bold">
                        {{ $page.props.auth.user?.email }}
                    </div>
                </div>
                <!--end::Info-->
            </div>
            <!--end::User-->
        </div>
        <!--end::Aside user-->
        <!--begin::Aside menu-->
        <div
            class="aside-menu flex-column-fluid ps-3 ps-lg-5 pe-1 mb-9"
            id="kt_aside_menu"
        >
            <!--begin::Aside Menu-->
            <div
                class="w-100 hover-scroll-y pe-2 me-2"
                id="kt_aside_menu_wrapper"
                data-kt-scroll="true"
                data-kt-scroll-activate="{default: false, lg: true}"
                data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_user, #kt_aside_footer"
                data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu, #kt_aside_menu_wrapper"
                data-kt-scroll-offset="0"
            >
                <div
                    class="menu menu-column menu-rounded menu-sub-indention fw-semibold"
                    id="#kt_aside_menu"
                    data-kt-menu="true"
                >
                    <div
                        data-kt-menu-trigger="click"
                        class="menu-item here show menu-accordion"
                    >
                        <Link :href="route('dashboard')" class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-solid ki-home-2 fs-2"></i>
                            </span>
                            <span class="menu-title">Inicio</span>
                        </Link>
                    </div>
                    <!-- <div data-kt-menu-trigger="click" class="menu-item here show menu-accordion"></div> -->
                    <div
                        data-kt-menu-trigger="click"
                        class="menu-item menu-accordion"
                    >
                        <Link
                            :href="route('suppliers.index')"
                            class="menu-link"
                        >
                            <span class="menu-icon">
                                <i class="ki-solid ki-note-2 fs-2"></i>
                            </span>
                            <span class="menu-title"
                                >Catálogo de Proveedores</span
                            >
                        </Link>
                    </div>
                    <div
                        data-kt-menu-trigger="click"
                        class="menu-item menu-accordion"
                    >
                        <Link
                            :href="route('purchase-orders.index')"
                            class="menu-link"
                        >
                            <span class="menu-icon">
                                <i class="ki-solid ki-delivery-time fs-2"></i>
                            </span>
                            <span class="menu-title"
                                >Ordenes de Compra Pendientes</span
                            >
                        </Link>
                    </div>
                    <div
                        data-kt-menu-trigger="click"
                        class="menu-item menu-accordion"
                    >
                        <Link :href="route('invoices.index')" class="menu-link">
                            <span class="menu-icon">
                                <i
                                    class="ki-solid ki-questionnaire-tablet fs-2"
                                ></i>
                            </span>
                            <span class="menu-title"
                                >Ordenes de Compra Completas</span
                            >
                        </Link>
                    </div>
                    <div
                        data-kt-menu-trigger="click"
                        class="menu-item menu-accordion"
                    >
                        <Link :href="route('users.index')" class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-solid ki-profile-user fs-2"></i>
                            </span>
                            <span class="menu-title">Usuarios</span>
                        </Link>
                    </div>
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Aside Menu-->
        </div>
        <!--end::Aside menu-->
        <!--begin::Footer-->
        <div
            class="aside-footer flex-column-auto px-6 px-lg-9"
            id="kt_aside_footer"
        >
            <!--begin::User panel-->
            <div class="d-flex flex-stack ms-7">
                <!--begin::Link-->
                <a
                    @click.prevent="logout"
                    href="#"
                    class="btn btn-sm btn-icon hover:text-[#ed6d24] btn-icon-gray-600 btn-active-color-primary"
                >
                    <i class="ki-duotone ki-entrance-left fs-1 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--begin::Major-->
                    <span class="d-flex flex-shrink-0 fw-bold fs-7"
                        >Cerrar Sesión</span
                    >
                    <!--end::Major-->
                </a>
                <!--end::Link-->
                <!--begin::User menu-->
                <div class="ms-1">
                    <div
                        class="btn btn-sm btn-icon btn-icon-gray-600 btn-active-color-primary position-relative me-n1"
                        data-kt-menu-trigger="click"
                        data-kt-menu-overflow="true"
                        data-kt-menu-placement="top-start"
                    >
                        <i class="ki-solid ki-setting-2 fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--begin::User account menu-->
                    <div
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                        data-kt-menu="true"
                    >
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <div
                                class="menu-content d-flex align-items-center px-3"
                            >
                                <!--begin::Avatar-->
                                <div class="symbol symbol-50px me-5">
                                    <img
                                        alt="Logo"
                                        :src="
                                            $page.props.auth.user
                                                ?.profile_photo_url
                                        "
                                    />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Username-->
                                <div class="d-flex flex-column">
                                    <div
                                        class="fw-bold d-flex align-items-center fs-5"
                                    >
                                        {{ $page.props.auth.user?.name }}
                                    </div>
                                    <a
                                        href="#"
                                        class="fw-semibold text-muted text-hover-primary fs-7"
                                        >{{ $page.props.auth.user?.email }}</a
                                    >
                                </div>
                                <!--end::Username-->
                            </div>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu separator-->
                        <div class="separator my-2"></div>
                        <!--end::Menu separator-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <Link class="menu-link px-5" href="/user/profile"
                                >Perfil</Link
                            >
                        </div>
                        <!--end::Menu item-->

                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <a
                                href="#"
                                @click.prevent="logout"
                                class="menu-link px-5"
                                >Cerrar Sesión</a
                            >
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::User account menu-->
                </div>
                <!--end::User menu-->
            </div>
            <!--end::User panel-->
        </div>
        <!--end::Footer-->
    </div>
</template>

<!-- menu con mas listas -->
<!-- <div
    data-kt-menu-trigger="click"
    class="menu-item menu-accordion"
>
    <span class="menu-link">
        <span class="menu-icon">
            <i class="ki-solid ki-abstract-35 fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </span>
        <span class="menu-title">Utilities</span>
        <span class="menu-arrow"></span>
    </span>
    <div class="menu-sub menu-sub-accordion">
        <div
            data-kt-menu-trigger="click"
            class="menu-item menu-accordion"
        >
            <span class="menu-link">
                <span class="menu-bullet">
                    <span class="bullet bullet-dot"></span>
                </span>
                <span class="menu-title">Modals</span>
                <span class="menu-arrow"></span>
            </span>
            <div
                class="menu-sub menu-sub-accordion menu-active-bg"
            >
                <div
                    data-kt-menu-trigger="click"
                    class="menu-item menu-accordion"
                >
                    <span class="menu-link">
                        <span class="menu-bullet">
                            <span
                                class="bullet bullet-dot"
                            ></span>
                        </span>
                        <span class="menu-title"
                            >General</span
                        >
                        <span class="menu-arrow"></span>
                    </span>
                    <div
                        class="menu-sub menu-sub-accordion menu-active-bg"
                    >
                        
                        <div class="menu-item">
                            <a
                                class="menu-link"
                                href="utilities/modals/general/invite-friends.html"
                            >
                                <span class="menu-bullet">
                                    <span
                                        class="bullet bullet-dot"
                                    ></span>
                                </span>
                                <span class="menu-title"
                                    >Invite Friends</span
                                >
                            </a>
                        </div>
                        <div class="menu-item">
                            <a
                                class="menu-link"
                                href="utilities/modals/general/view-users.html"
                            >
                                <span class="menu-bullet">
                                    <span
                                        class="bullet bullet-dot"
                                    ></span>
                                </span>
                                <span class="menu-title"
                                    >View Users</span
                                >
                            </a>
                        </div>
                        <div class="menu-item">
                            <a
                                class="menu-link"
                                href="utilities/modals/general/select-users.html"
                            >
                                <span class="menu-bullet">
                                    <span
                                        class="bullet bullet-dot"
                                    ></span>
                                </span>
                                <span class="menu-title"
                                    >Select Users</span
                                >
                            </a>
                        </div>
                        <div class="menu-item">
                            <a
                                class="menu-link"
                                href="utilities/modals/general/upgrade-plan.html"
                            >
                                <span class="menu-bullet">
                                    <span
                                        class="bullet bullet-dot"
                                    ></span>
                                </span>
                                <span class="menu-title"
                                    >Upgrade Plan</span
                                >
                            </a>
                        </div>
                        <div class="menu-item">
                            <a
                                class="menu-link"
                                href="utilities/modals/general/share-earn.html"
                            >
                                <span class="menu-bullet">
                                    <span
                                        class="bullet bullet-dot"
                                    ></span>
                                </span>
                                <span class="menu-title"
                                    >Share & Earn</span
                                >
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->
<!-- termina opcion menu con mas listas -->
