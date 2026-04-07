import { usePage } from "@inertiajs/vue3";

export function useAuthz() {
    const page = usePage();

    const can = (permission, view = null) => {
        const auth = page.props.auth;

        if (auth?.permission_overrides) {
            const key = view ? `${view}.${permission}` : permission;
            const keyAlt = permission;

            if (auth.permission_overrides.hasOwnProperty(key)) {
                return auth.permission_overrides[key] === true;
            }
            if (auth.permission_overrides.hasOwnProperty(keyAlt)) {
                return auth.permission_overrides[keyAlt] === true;
            }
        }

        const perms = auth?.permissions ?? [];
        return perms.includes(permission);
    };

    const isOverridden = (permission, view = null) => {
        const auth = page.props.auth;
        const key = view ? `${view}.${permission}` : permission;
        const keyAlt = permission;
        return (
            auth?.permission_overrides?.hasOwnProperty(key) ||
            auth?.permission_overrides?.hasOwnProperty(keyAlt)
        );
    };

    const getOverrideValue = (permission, view = null) => {
        const auth = page.props.auth;
        const key = view ? `${view}.${permission}` : permission;
        const keyAlt = permission;
        return (
            auth?.permission_overrides?.[key] ??
            auth?.permission_overrides?.[keyAlt] ??
            null
        );
    };

    const getRole = () => {
        const auth = page.props.auth;
        return auth?.user?.roles?.[0] ?? null;
    };

    return { can, isOverridden, getOverrideValue, getRole };
}
