<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";
import { useForm } from '@inertiajs/vue3';
import { nextTick } from 'vue';

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/locations', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const props = defineProps({
    Location: Object,
    States: Array,
    Cities: Array,
});
// console.log(props);

const form = useForm({
    name: '',
    city_id: null,
    state_id: null
});

const frontErrors = reactive({});

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const filteredCities = computed(() => {
    if (!form.state_id) return [];

    return props.Cities.filter(
        city => city.state_id === form.state_id
    );
});

watch(() => form.state_id, () => {
    form.city_id = null;
});

const hydrateForm = async (data) => {
    form.name = data.name ?? '';

    form.state_id = data.state_id ? Number(data.state_id) : null;

    await nextTick();

    form.city_id = data.city_id ? Number(data.city_id) : null;
};

onMounted(() => {
    hydrateForm(props.Location);
});

</script>

<template>
    <AppLayout title="Crear Rol">
        <div class="card space-y-6">

            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle de la Dirección
            </h2>

            <div class="p-4">

                <div class="p-formgrid grid p-nogutter p-fluid">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Estado -->
                        <div>
                            <label for="state_id" class="block font-bold mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <Select
                                disabled
                                id="state_id"
                                v-model="form.state_id"
                                :options="props.States"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.state_id"
                                @change="clearError('state_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.state_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.state_id }}
                            </Message>
                        </div>

                        <!-- Ciudad -->
                        <div>
                            <label for="city_id" class="block font-bold mb-2">
                                Ciudad <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="city_id"
                                v-model="form.city_id"
                                :options="filteredCities"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una ciudad"
                                class="w-full"
                                filter
                                disabled
                                :invalid="!!frontErrors.city_id"
                                @change="clearError('city_id')"
                            />
                            <Message
                                v-if="frontErrors.city_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.city_id }}
                            </Message>
                        </div>

                        <!-- Colonia -->
                        <div class="field mb-4">
                            <label for="name" class="block font-bold mb-2">
                                Colonia <span class="text-red-500">*</span>
                            </label>
                            <InputText 
                                readonly
                                id="name" 
                                v-model="form.name" 
                                required 
                                class="w-full"
                                :class="{ 'p-invalid': frontErrors.name }" 
                                @input="clearError('name')" 
                            />
                            <Message
                                v-if="frontErrors.name"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.name }}
                            </Message>
                        </div>
                    </div>
                    
                    <!-- BOTONES ABAJO -->
                    <div class="flex justify-end gap-3 pt-2">
                        <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                    </div>
                </div>
            </div>
        
        </div>
    </AppLayout>
</template>