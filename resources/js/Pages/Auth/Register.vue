<script setup>
import { ref, onMounted, watch } from 'vue';
import { useForm, Link as InertiaLink, Head } from '@inertiajs/vue3';


const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
});

const submitForm = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};


const showPassword = ref({
  password: false,
  confirm_password: false, 
});

const togglePasswordVisibility = (field) => {
  showPassword.value[field] = !showPassword.value[field];
  const input = document.querySelector(`input[name="${field}"]`);
  if (input) {
    input.type = showPassword.value[field] ? 'text' : 'password';
  }
};


const passwordStrength = ref(0);

// Función para evaluar la fuerza de la contraseña
const evaluatePasswordStrength = (password) => {
  let score = 0;

  if (!password) {
    return 0;
  }

  const hasLower = /[a-z]/.test(password);
  const hasUpper = /[A-Z]/.test(password);
  const hasDigit = /\d/.test(password);
  const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

  if (hasLower) score++;
  if (hasUpper) score++;
  if (hasDigit) score++;
  if (hasSpecial) score++;

  if (password.length >= 6) score++; // Al menos 6 caracteres
  if (password.length >= 8) score++; // Al menos 8 caracteres
  if (password.length >= 12) score++; // Al menos 12 caracteres (para muy fuerte)

  if (score <= 1) return 1; // Muy débil (1 barra)
  if (score === 2) return 2; // Débil (2 barras)
  if (score === 3) return 3; // Media (3 barras)
  if (score >= 4) return 4; // Fuerte (4 barras)

  return 0; 
};

watch(() => form.password, (newPassword) => {
  passwordStrength.value = evaluatePasswordStrength(newPassword);
}, { immediate: true });

const passwordStrengthClass = (password, barIndex) => {
  const currentStrength = evaluatePasswordStrength(password);

  if (barIndex <= currentStrength) {
    if (currentStrength === 1) return 'bg-danger';   // Muy Débil (rojo)
    if (currentStrength === 2) return 'bg-warning';  // Débil (naranja/amarillo)
    if (currentStrength === 3) return 'bg-warning';  // Media (azul, o un tono intermedio)
    if (currentStrength >= 4) return 'bg-success';   // Fuerte (verde)
  }
  return 'bg-secondary'; 
};

const languageMenuOpen = ref(false);
const currentLanguageFlag = ref('/assets/media/flags/united-states.svg');
const currentLanguageName = ref('English');
const languages = [
  { code: 'en', name: 'English', flag: '/assets/media/flags/united-states.svg' },
  { code: 'es', name: 'Spanish', flag: '/assets/media/flags/spain.svg' },
  { code: 'de', name: 'German', flag: '/assets/media/flags/germany.svg' },
  { code: 'jp', name: 'Japanese', flag: '/assets/media/flags/japan.svg' },
  { code: 'fr', name: 'French', flag: '/assets/media/flags/france.svg' },
];

const toggleLanguageMenu = () => {
  languageMenuOpen.value = !languageMenuOpen.value;
};

const setLanguage = (lang) => {
  currentLanguageFlag.value = lang.flag;
  currentLanguageName.value = lang.name;
  languageMenuOpen.value = false;
  
};

onMounted(() => {
  
  document.addEventListener('click', (e) => {
    const languageButton = document.querySelector('.btn-color-gray-700'); 
    const languageMenu = document.getElementById('kt_auth_lang_menu'); 

    if (languageMenuOpen.value && languageButton && languageMenu &&
        !languageButton.contains(e.target) && !languageMenu.contains(e.target)) {
      languageMenuOpen.value = false;
    }
  });

});
</script>

<style scoped>

.menu.show {
  display: block;
}
</style>

<template>
  <Head title="Register" />

  <div class="d-flex flex-column flex-fluid">
    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
      <div class="d-flex flex-lg-row-fluid">
        <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
          <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="/assets/media/auth/agency.png" alt="" />
          <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="/assets/media/auth/agency-dark.png" alt="" />
          <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
          <div class="text-gray-600 fs-base text-center fw-semibold">
            In this kind of post,
            <a href="#" class="opacity-75-hover text-primary me-1">the blogger</a>introduces a person they’ve interviewed
            <br />and provides some background information about
            <a href="#" class="opacity-75-hover text-primary me-1">the interviewee</a>and their
            <br />work following this is a transcript of the interview.
          </div>
          </div>
        </div>
      <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
          <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
            <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">
              <form class="form w-100" @submit.prevent="submitForm">
                <div class="text-center mb-11">
                  <h1 class="text-gray-900 fw-bolder mb-3 text-4xl">Sign Up</h1>
                  </div>
                <div class="row g-3 mb-9">
                  <div class="col-md-6">
                    <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                      <img alt="Logo" src="/assets/media/svg/brand-logos/google-icon.svg" class="h-15px me-3" />Sign in with Google
                    </a>
                    </div>
                  <div class="col-md-6">
                    <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                      <img alt="Logo" src="/assets/media/svg/brand-logos/apple-black.svg" class="theme-light-show h-15px me-3" />
                      <img alt="Logo" src="/assets/media/svg/brand-logos/apple-black-dark.svg" class="theme-dark-show h-15px me-3" />Sign in with Apple
                    </a>
                    </div>
                  </div>
                <div class="separator separator-content my-14">
                  <span class="w-125px text-gray-500 fw-semibold fs-7">Or with email</span>
                </div>
                <div class="fv-row mb-8">
                  <input type="text" placeholder="Name" name="name" v-model="form.name" autocomplete="off" class="form-control bg-transparent rounded-md border-gray-400 py-3 shadow-md text-lg" />
                  <div v-if="form.errors.name" class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                    {{ form.errors.name }}
                  </div>
                </div>

                <div class="fv-row mb-8">
                  <input type="text" placeholder="Email" name="email" v-model="form.email" autocomplete="off" class="form-control bg-transparent rounded-md border-gray-400 py-3 shadow-md text-lg" />
                  <div v-if="form.errors.email" class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                    {{ form.errors.email }}
                  </div>
                </div>
                <div class="fv-row mb-8" data-kt-password-meter="true">
                  <div class="mb-1">
                    <div class="position-relative mb-3">
                      <input class="form-control bg-transparent rounded-md border-gray-400 py-3 shadow-md text-lg" type="password" placeholder="Password" name="password" v-model="form.password" autocomplete="off" />
                    </div>
                    <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                      <div class="flex-grow-1 bg-secondary rounded h-5px me-2" :class="passwordStrengthClass(form.password, 1)"></div>
                      <div class="flex-grow-1 bg-secondary rounded h-5px me-2" :class="passwordStrengthClass(form.password, 2)"></div>
                      <div class="flex-grow-1 bg-secondary rounded h-5px me-2" :class="passwordStrengthClass(form.password, 3)"></div>
                      <div class="flex-grow-1 bg-secondary rounded h-5px" :class="passwordStrengthClass(form.password, 4)"></div>
                    </div>
                    </div>
                  <div class="text-muted">Use 8 or more characters with a mix of letters, numbers & symbols.</div>
                  <div v-if="form.errors.password" class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                    {{ form.errors.password }}
                  </div>
                </div>
                <div class="fv-row mb-8">
                  <input placeholder="Repeat Password" name="password_confirmation" type="password" v-model="form.password_confirmation" autocomplete="off" class="form-control bg-transparent rounded-md border-gray-400 py-3 shadow-md text-lg" />
                  <div v-if="form.errors.password_confirmation" class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                    {{ form.errors.password_confirmation }}
                  </div>
                </div>
                
                <div class="d-grid mb-10">
                  <button type="submit" id="kt_sign_up_submit" class="btn btn-primary" :disabled="form.processing">
                    <span class="indicator-label" v-if="!form.processing">Sign up</span>
                    <span class="indicator-label" v-if="form.processing">
                      Loading...
                    </span>
                  </button>
                </div>
                <div class="text-gray-500 text-center fw-semibold fs-6">
                  Already have an Account?
                  <InertiaLink :href="route('login')" class="link-primary fw-semibold">Sign in</InertiaLink>
                </div>
                </form>
              </div>
            <div class="w-lg-500px d-flex flex-stack">
              <div class="me-10">
                <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                        @click="toggleLanguageMenu" aria-expanded="languageMenuOpen">
                  <img :src="currentLanguageFlag" class="w-20px h-20px rounded me-3" alt="" />
                  <span class="me-1">{{ currentLanguageName }}</span>
                  <i class="ki-duotone ki-down fs-5 text-muted rotate-180 m-0"></i>
                </button>
                <div :class="['menu', 'menu-sub', 'menu-sub-dropdown', 'menu-column', 'menu-rounded', 'menu-gray-800', 'menu-state-bg-light-primary', 'fw-semibold', 'w-200px', 'py-4', 'fs-7', { 'show': languageMenuOpen }]"
                     id="kt_auth_lang_menu" style="z-index: 1000;"
                     v-show="languageMenuOpen">
                  <div class="menu-item px-3" v-for="lang in languages" :key="lang.code">
                    <a href="#" class="menu-link d-flex px-5" @click.prevent="setLanguage(lang)">
                      <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" :src="lang.flag" alt="" />
                      </span>
                      <span>{{ lang.name }}</span>
                    </a>
                  </div>
                </div>
                </div>
              <div class="d-flex fw-semibold text-primary fs-base gap-5">
                <InertiaLink target="_blank">Terms</InertiaLink>
                <InertiaLink target="_blank">Plans</InertiaLink>
                <InertiaLink target="_blank">Contact Us</InertiaLink>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

