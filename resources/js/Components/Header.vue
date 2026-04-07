<template>
    <div id="kt_header" class="header">
        <!--begin::Container-->
        <div
            class="container-fluid d-flex align-items-center flex-wrap justify-content-between"
            id="kt_header_container"
        >
            <!--begin::Page title-->
            <div
                class="page-title d-flex flex-column align-items-start justify-content-center flex-wrap me-2 pb-5 pb-lg-0 pt-7 pt-lg-0"
                data-kt-swapper="true"
                data-kt-swapper-mode="prepend"
                data-kt-swapper-parent="{default: '#kt_content_container', lg: '#kt_header_container'}"
            >
                <!--begin::Heading-->
                <h1 class="d-flex flex-column text-gray-900 fw-bold my-0 fs-1">
                    {{ props.title }}
                </h1>
                <!--end::Heading-->
            </div>
            <!--end::Page title=-->
            <!--begin::Wrapper-->
            <div class="d-flex d-lg-none align-items-center ms-n4 me-2">
                <!--begin::Aside mobile toggle-->
                <div
                    class="btn btn-icon btn-active-icon-primary"
                    id="kt_aside_toggle"
                >
                    <i class="ki-duotone ki-abstract-14 fs-1 mt-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--end::Aside mobile toggle-->
                <!--begin::Logo-->
                <!-- <a href="index.html" class="d-flex align-items-center">
                    <img
                        alt="Logo"
                        src="@assets/media/logos/default-small.svg"
                        class="h-30px"
                    />
                </a> -->
                <!--end::Logo-->
            </div>
            <!--end::Wrapper-->
            <!--begin::Topbar-->
            <div class="d-flex align-items-center flex-shrink-0">
                <!-- <div class="d-flex align-items-center ms-3 ms-lg-4"> -->
                    <!--begin::Drawer toggle-->
                    <!-- <div
                        class="btn btn-icon btn-color-gray-700 btn-active-color-primary btn-outline w-40px h-40px"
                        id="kt_activities_toggle"
                    >
                        <i class="ki-duotone ki-notification-bing fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </div> -->
                    <!--end::Drawer toggle-->
                <!-- </div> -->
                <!--end::Chat-->
                <!--begin::Theme mode-->
                <!-- <div class="d-flex align-items-center ms-3 ms-lg-4"> -->
                    <!--begin::Menu toggle-->
                    <!-- <a
                        href="#"
                        class="btn btn-icon btn-color-gray-700 btn-active-color-primary btn-outline w-40px h-40px"
                        data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                        data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end"
                    >
                        <i
                            class="ki-duotone ki-night-day theme-light-show fs-1"
                        >
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                            <span class="path6"></span>
                            <span class="path7"></span>
                            <span class="path8"></span>
                            <span class="path9"></span>
                            <span class="path10"></span>
                        </i>
                        <i class="ki-duotone ki-moon theme-dark-show fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </a> -->
                    <!--begin::Menu toggle-->
                    <!--begin::Menu-->
                    <!-- <div
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                        data-kt-menu="true"
                        data-kt-element="theme-mode-menu"
                    > -->
                        <!--begin::Menu item-->
                        <!-- <div class="menu-item px-3 my-0">
                            <a
                                href="#"
                                @click.prevent="selectTheme('light')"
                                class="menu-link px-3 py-2"
                                data-kt-element="mode"
                                data-kt-value="light"
                            >
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-night-day fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        <span class="path6"></span>
                                        <span class="path7"></span>
                                        <span class="path8"></span>
                                        <span class="path9"></span>
                                        <span class="path10"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Light</span>
                            </a>
                        </div> -->
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <!-- z -->
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <!-- <div class="menu-item px-3 my-0">
                            <a
                                href="#"
                                @click.prevent="selectTheme('system')"
                                class="menu-link px-3 py-2"
                                data-kt-element="mode"
                                data-kt-value="system"
                            >
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </span>
                                <span class="menu-title">System</span>
                            </a>
                        </div> -->
                        <!--end::Menu item-->
                    <!-- </div> -->
                    <!--end::Menu-->
                <!-- </div> -->
                <!--end::Theme mode-->
                <!--begin::Sidebar Toggler-->
                <!--end::Sidebar Toggler-->
            </div>
            <!--end::Topbar-->
        </div>
        <!--end::Container-->
    </div>
    <div
        id="kt_activities"
        class="bg-body"
        data-kt-drawer="true"
        data-kt-drawer-name="activities"
        data-kt-drawer-activate="true"
        data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'300px', 'lg': '900px'}"
        data-kt-drawer-direction="end"
        data-kt-drawer-toggle="#kt_activities_toggle"
        data-kt-drawer-close="#kt_activities_close"
    >
        <div class="card shadow-none border-0 rounded-0">
            <!--begin::Header-->
            <div class="card-header" id="kt_activities_header">
                <h3 class="card-title fw-bold text-gray-900">Activity Logs</h3>
                <div class="card-toolbar">
                    <button
                        type="button"
                        class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                        id="kt_activities_close"
                    >
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body position-relative" id="kt_activities_body">
                <!--begin::Content-->
                <div
                    id="kt_activities_scroll"
                    class="position-relative scroll-y me-n5 pe-5"
                    data-kt-scroll="true"
                    data-kt-scroll-height="auto"
                    data-kt-scroll-wrappers="#kt_activities_body"
                    data-kt-scroll-dependencies="#kt_activities_header, #kt_activities_footer"
                    data-kt-scroll-offset="5px"
                >
                    <!--begin::Timeline items-->
                    <div class="timeline timeline-border-dashed">
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-message-text-2 fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        There are 2 new tasks for you in
                                        “AirPlus Mobile App” project:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Added at 4:23 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Nina Nilson"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-14.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                                <!--begin::Timeline details-->
                                <div class="overflow-auto pb-5">
                                    <!--begin::Record-->
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-750px px-7 py-3 mb-5"
                                    >
                                        <!--begin::Title-->
                                        <a
                                            href="apps/projects/project.html"
                                            class="fs-5 text-gray-900 text-hover-primary fw-semibold w-375px min-w-200px"
                                            >Meeting with customer</a
                                        >
                                        <!--end::Title-->
                                        <!--begin::Label-->
                                        <div class="min-w-175px pe-2">
                                            <span
                                                class="badge badge-light text-muted"
                                                >Application Design</span
                                            >
                                        </div>
                                        <!--end::Label-->
                                        <!--begin::Users-->
                                        <div
                                            class="symbol-group symbol-hover flex-nowrap flex-grow-1 min-w-100px pe-2"
                                        >
                                            <!--begin::User-->
                                            <div
                                                class="symbol symbol-circle symbol-25px"
                                            >
                                                <img
                                                    src="@assets/media/avatars/300-2.jpg"
                                                    alt="img"
                                                />
                                            </div>
                                            <!--end::User-->
                                            <!--begin::User-->
                                            <div
                                                class="symbol symbol-circle symbol-25px"
                                            >
                                                <img
                                                    src="@assets/media/avatars/300-14.jpg"
                                                    alt="img"
                                                />
                                            </div>
                                            <!--end::User-->
                                            <!--begin::User-->
                                            <div
                                                class="symbol symbol-circle symbol-25px"
                                            >
                                                <div
                                                    class="symbol-label fs-8 fw-semibold bg-primary text-inverse-primary"
                                                >
                                                    A
                                                </div>
                                            </div>
                                            <!--end::User-->
                                        </div>
                                        <!--end::Users-->
                                        <!--begin::Progress-->
                                        <div class="min-w-125px pe-2">
                                            <span
                                                class="badge badge-light-primary"
                                                >In Progress</span
                                            >
                                        </div>
                                        <!--end::Progress-->
                                        <!--begin::Action-->
                                        <a
                                            href="apps/projects/project.html"
                                            class="btn btn-sm btn-light btn-active-light-primary"
                                            >View</a
                                        >
                                        <!--end::Action-->
                                    </div>
                                    <!--end::Record-->
                                    <!--begin::Record-->
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-750px px-7 py-3 mb-0"
                                    >
                                        <!--begin::Title-->
                                        <a
                                            href="apps/projects/project.html"
                                            class="fs-5 text-gray-900 text-hover-primary fw-semibold w-375px min-w-200px"
                                            >Project Delivery Preparation</a
                                        >
                                        <!--end::Title-->
                                        <!--begin::Label-->
                                        <div class="min-w-175px">
                                            <span
                                                class="badge badge-light text-muted"
                                                >CRM System Development</span
                                            >
                                        </div>
                                        <!--end::Label-->
                                        <!--begin::Users-->
                                        <div
                                            class="symbol-group symbol-hover flex-nowrap flex-grow-1 min-w-100px"
                                        >
                                            <!--begin::User-->
                                            <div
                                                class="symbol symbol-circle symbol-25px"
                                            >
                                                <img
                                                    src="@assets/media/avatars/300-20.jpg"
                                                    alt="img"
                                                />
                                            </div>
                                            <!--end::User-->
                                            <!--begin::User-->
                                            <div
                                                class="symbol symbol-circle symbol-25px"
                                            >
                                                <div
                                                    class="symbol-label fs-8 fw-semibold bg-success text-inverse-primary"
                                                >
                                                    B
                                                </div>
                                            </div>
                                            <!--end::User-->
                                        </div>
                                        <!--end::Users-->
                                        <!--begin::Progress-->
                                        <div class="min-w-125px">
                                            <span
                                                class="badge badge-light-success"
                                                >Completed</span
                                            >
                                        </div>
                                        <!--end::Progress-->
                                        <!--begin::Action-->
                                        <a
                                            href="apps/projects/project.html"
                                            class="btn btn-sm btn-light btn-active-light-primary"
                                            >View</a
                                        >
                                        <!--end::Action-->
                                    </div>
                                    <!--end::Record-->
                                </div>
                                <!--end::Timeline details-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon me-4">
                                <i
                                    class="ki-duotone ki-flag fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n2">
                                <!--begin::Timeline heading-->
                                <div class="overflow-auto pe-3">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        Invitation for crafting engaging designs
                                        that speak human workshop
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Sent at 4:23 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Alan Nilson"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-1.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-disconnect fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="mb-5 pe-3">
                                    <!--begin::Title-->
                                    <a
                                        href="#"
                                        class="fs-5 fw-semibold text-gray-800 text-hover-primary mb-2"
                                        >3 New Incoming Project Files:</a
                                    >
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Sent at 10:30 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Jan Hummer"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-23.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                                <!--begin::Timeline details-->
                                <div class="overflow-auto pb-5">
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-700px p-5"
                                    >
                                        <!--begin::Item-->
                                        <div
                                            class="d-flex flex-aligns-center pe-10 pe-lg-20"
                                        >
                                            <!--begin::Icon-->
                                            <img
                                                alt=""
                                                class="w-30px me-3"
                                                src="@assets/media/svg/files/pdf.svg"
                                            />
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a
                                                    href="apps/projects/project.html"
                                                    class="fs-6 text-hover-primary fw-bold"
                                                    >Finance KPI App
                                                    Guidelines</a
                                                >
                                                <!--end::Desc-->
                                                <!--begin::Number-->
                                                <div class="text-gray-500">
                                                    1.9mb
                                                </div>
                                                <!--end::Number-->
                                            </div>
                                            <!--begin::Info-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <div
                                            class="d-flex flex-aligns-center pe-10 pe-lg-20"
                                        >
                                            <!--begin::Icon-->
                                            <img
                                                alt="apps/projects/project.html"
                                                class="w-30px me-3"
                                                src="@assets/media/svg/files/doc.svg"
                                            />
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a
                                                    href="#"
                                                    class="fs-6 text-hover-primary fw-bold"
                                                    >Client UAT Testing
                                                    Results</a
                                                >
                                                <!--end::Desc-->
                                                <!--begin::Number-->
                                                <div class="text-gray-500">
                                                    18kb
                                                </div>
                                                <!--end::Number-->
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-aligns-center">
                                            <!--begin::Icon-->
                                            <img
                                                alt="apps/projects/project.html"
                                                class="w-30px me-3"
                                                src="@assets/media/svg/files/css.svg"
                                            />
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a
                                                    href="#"
                                                    class="fs-6 text-hover-primary fw-bold"
                                                    >Finance Reports</a
                                                >
                                                <!--end::Desc-->
                                                <!--begin::Number-->
                                                <div class="text-gray-500">
                                                    20mb
                                                </div>
                                                <!--end::Number-->
                                            </div>
                                            <!--end::Icon-->
                                        </div>
                                        <!--end::Item-->
                                    </div>
                                </div>
                                <!--end::Timeline details-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-abstract-26 fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        Task
                                        <a
                                            href="#"
                                            class="text-primary fw-bold me-1"
                                            >#45890</a
                                        >merged with
                                        <a
                                            href="#"
                                            class="text-primary fw-bold me-1"
                                            >#45890</a
                                        >in “Ads Pro Admin Dashboard project:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Initiated at 4:23 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Nina Nilson"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-14.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-pencil fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        3 new application design concepts added:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Created at 4:23 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Marcus Dotson"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-2.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                                <!--begin::Timeline details-->
                                <div class="overflow-auto pb-5">
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-700px p-7"
                                    >
                                        <!--begin::Item-->
                                        <div class="overlay me-10">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img
                                                    alt="img"
                                                    class="rounded w-150px"
                                                    src="@assets/media/stock/600x400/img-29.jpg"
                                                />
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div
                                                class="overlay-layer bg-dark bg-opacity-10 rounded"
                                            >
                                                <a
                                                    href="#"
                                                    class="btn btn-sm btn-primary btn-shadow"
                                                    >Explore</a
                                                >
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <div class="overlay me-10">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img
                                                    alt="img"
                                                    class="rounded w-150px"
                                                    src="@assets/media/stock/600x400/img-31.jpg"
                                                />
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div
                                                class="overlay-layer bg-dark bg-opacity-10 rounded"
                                            >
                                                <a
                                                    href="#"
                                                    class="btn btn-sm btn-primary btn-shadow"
                                                    >Explore</a
                                                >
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <div class="overlay">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img
                                                    alt="img"
                                                    class="rounded w-150px"
                                                    src="@assets/media/stock/600x400/img-40.jpg"
                                                />
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div
                                                class="overlay-layer bg-dark bg-opacity-10 rounded"
                                            >
                                                <a
                                                    href="#"
                                                    class="btn btn-sm btn-primary btn-shadow"
                                                    >Explore</a
                                                >
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::Item-->
                                    </div>
                                </div>
                                <!--end::Timeline details-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i class="ki-duotone ki-sms fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        New case
                                        <a
                                            href="#"
                                            class="text-primary fw-bold me-1"
                                            >#67890</a
                                        >is assigned to you in Multi-platform
                                        Database Design project
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div class="overflow-auto pb-5">
                                        <!--begin::Wrapper-->
                                        <div
                                            class="d-flex align-items-center mt-1 fs-6"
                                        >
                                            <!--begin::Info-->
                                            <div class="text-muted me-2 fs-7">
                                                Added at 4:23 PM by
                                            </div>
                                            <!--end::Info-->
                                            <!--begin::User-->
                                            <a
                                                href="#"
                                                class="text-primary fw-bold me-1"
                                                >Alice Tan</a
                                            >
                                            <!--end::User-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-pencil fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mb-10 mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        You have received a new order:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Placed at 5:05 AM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <div
                                            class="symbol symbol-circle symbol-25px"
                                            data-bs-toggle="tooltip"
                                            data-bs-boundary="window"
                                            data-bs-placement="top"
                                            title="Robert Rich"
                                        >
                                            <img
                                                src="@assets/media/avatars/300-4.jpg"
                                                alt="img"
                                            />
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                                <!--begin::Timeline details-->
                                <div class="overflow-auto pb-5">
                                    <!--begin::Notice-->
                                    <div
                                        class="notice d-flex bg-light-primary rounded border-primary border border-dashed min-w-lg-600px flex-shrink-0 p-6"
                                    >
                                        <!--begin::Icon-->
                                        <i
                                            class="ki-duotone ki-devices-2 fs-2tx text-primary me-4"
                                        >
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <!--end::Icon-->
                                        <!--begin::Wrapper-->
                                        <div
                                            class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap"
                                        >
                                            <!--begin::Content-->
                                            <div
                                                class="mb-3 mb-md-0 fw-semibold"
                                            >
                                                <h4
                                                    class="text-gray-900 fw-bold"
                                                >
                                                    Database Backup Process
                                                    Completed!
                                                </h4>
                                                <div
                                                    class="fs-6 text-gray-700 pe-7"
                                                >
                                                    Login into Admin Dashboard
                                                    to make sure the data
                                                    integrity is OK
                                                </div>
                                            </div>
                                            <!--end::Content-->
                                            <!--begin::Action-->
                                            <a
                                                href="#"
                                                class="btn btn-primary px-6 align-self-center text-nowrap"
                                                >Proceed</a
                                            >
                                            <!--end::Action-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Notice-->
                                </div>
                                <!--end::Timeline details-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                        <!--begin::Timeline item-->
                        <div class="timeline-item">
                            <!--begin::Timeline line-->
                            <div class="timeline-line"></div>
                            <!--end::Timeline line-->
                            <!--begin::Timeline icon-->
                            <div class="timeline-icon">
                                <i
                                    class="ki-duotone ki-basket fs-2 text-gray-500"
                                >
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </div>
                            <!--end::Timeline icon-->
                            <!--begin::Timeline content-->
                            <div class="timeline-content mt-n1">
                                <!--begin::Timeline heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">
                                        New order
                                        <a
                                            href="#"
                                            class="text-primary fw-bold me-1"
                                            >#67890</a
                                        >is placed for Workshow Planning &
                                        Budget Estimation
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Description-->
                                    <div
                                        class="d-flex align-items-center mt-1 fs-6"
                                    >
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">
                                            Placed at 4:23 PM by
                                        </div>
                                        <!--end::Info-->
                                        <!--begin::User-->
                                        <a
                                            href="#"
                                            class="text-primary fw-bold me-1"
                                            >Jimmy Bold</a
                                        >
                                        <!--end::User-->
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Timeline heading-->
                            </div>
                            <!--end::Timeline content-->
                        </div>
                        <!--end::Timeline item-->
                    </div>
                    <!--end::Timeline items-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer py-5 text-center" id="kt_activities_footer">
                <a
                    href="pages/user-profile/activity.html"
                    class="btn btn-bg-body text-primary"
                    >View All Activities
                    <i class="ki-duotone ki-arrow-right fs-3 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span> </i
                ></a>
            </div>
            <!--end::Footer-->
        </div>
    </div>
</template>
<script setup>
import { onMounted } from "vue";

const props = defineProps({
    title: String,
});

function handleSystemThemeChange(e) {
    const htmlElement = document.documentElement;
    const newTheme = e.matches ? "dark" : "light";

    if (newTheme === "dark") {
        htmlElement.classList.add("mode-dark");
    } else {
        htmlElement.classList.remove("mode-dark");
    }
    document.documentElement.setAttribute("data-bs-theme", newTheme);
}

function selectTheme(themeToApply) {
    console.log("selectTheme called with themeToApply:", themeToApply);
    const htmlElement = document.documentElement;

    htmlElement.classList.remove("mode-dark");

    window
        .matchMedia("(prefers-color-scheme: dark)")
        .removeEventListener("change", handleSystemThemeChange);

    if (themeToApply === "system") {
        const systemThemeIsDark = window.matchMedia(
            "(prefers-color-scheme: dark)"
        ).matches;
        const actualTheme = systemThemeIsDark ? "dark" : "light";
        document.documentElement.setAttribute("data-bs-theme", actualTheme);

        if (systemThemeIsDark) {
            htmlElement.classList.add("mode-dark");
            console.log("Applying system dark theme.");
        } else {
            console.log("Applying system light theme.");
        }
        window
            .matchMedia("(prefers-color-scheme: dark)")
            .addEventListener("change", handleSystemThemeChange);
    } else if (themeToApply === "dark") {
        document.documentElement.setAttribute("data-bs-theme", "dark");
        htmlElement.classList.add("mode-dark");
        console.log("Applying explicit dark theme.");
    } else if (themeToApply === "light") {
        document.documentElement.setAttribute("data-bs-theme", "light");
        htmlElement.classList.remove("mode-dark"); // Asegurarse de quitarla
        console.log("Applying explicit light theme.");
    }

    localStorage.setItem("themePreference", themeToApply);
}

onMounted(() => {
    const savedTheme = localStorage.getItem("themePreference") || "system";
    selectTheme(savedTheme);
});
</script>

<style>
@container (max-width:120px) {
    .table-e160dd4c-4d48-4e6e-95e9-0d110e92730f-column-120 {
        display: none;
    }
}

@container (max-width:240px) {
    .table-e160dd4c-4d48-4e6e-95e9-0d110e92730f-column-240 {
        display: none;
    }
}

@container (max-width:360px) {
    .table-e160dd4c-4d48-4e6e-95e9-0d110e92730f-column-360 {
        display: none;
    }
}
</style>
