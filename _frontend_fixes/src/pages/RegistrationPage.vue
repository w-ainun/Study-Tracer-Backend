<template>
  <div class="registration-container">
    <div class="registration-card">
      <h1>Registrasi Alumni</h1>
      <p class="subtitle">Lengkapi data diri Anda untuk mendaftar</p>

      <form @submit.prevent="handleSelesai">
        <!-- Nama Lengkap -->
        <div class="form-group">
          <label for="nama">Nama Lengkap <span class="required">*</span></label>
          <input
            id="nama"
            v-model="formData.nama"
            type="text"
            placeholder="Masukkan nama lengkap"
            required
          />
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email <span class="required">*</span></label>
          <input
            id="email"
            v-model="formData.email"
            type="email"
            placeholder="email@example.com"
            required
          />
        </div>

        <!-- NIM -->
        <div class="form-group">
          <label for="nim">NIM <span class="required">*</span></label>
          <input
            id="nim"
            v-model="formData.nim"
            type="text"
            placeholder="Masukkan NIM"
            required
          />
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password <span class="required">*</span></label>
          <input
            id="password"
            v-model="formData.password"
            type="password"
            placeholder="Minimal 8 karakter"
            required
          />
        </div>

        <!-- Konfirmasi Password -->
        <div class="form-group">
          <label for="password_confirmation">
            Konfirmasi Password <span class="required">*</span>
          </label>
          <input
            id="password_confirmation"
            v-model="formData.password_confirmation"
            type="password"
            placeholder="Ulangi password"
            required
          />
        </div>

        <!-- Tahun Lulus -->
        <div class="form-group">
          <label for="tahun_lulus">Tahun Lulus <span class="required">*</span></label>
          <input
            id="tahun_lulus"
            v-model="formData.tahun_lulus"
            type="number"
            min="1900"
            :max="new Date().getFullYear()"
            placeholder="2024"
            required
          />
        </div>

        <!-- No Telp -->
        <div class="form-group">
          <label for="no_telp">No. Telepon <span class="required">*</span></label>
          <input
            id="no_telp"
            v-model="formData.no_telp"
            type="tel"
            placeholder="08xxxxxxxxxx"
            required
          />
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="success-alert">
          {{ successMessage }}
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Memproses...' : 'Selesai' }}
        </button>

        <!-- Login Link -->
        <p class="login-link">
          Sudah punya akun? <router-link to="/login">Login di sini</router-link>
        </p>
      </form>
    </div>

    <!-- CAPTCHA Popup -->
    <CaptchaPopup
      :is-open="showCaptcha"
      @close="handleCaptchaClose"
      @success="handleCaptchaSuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import CaptchaPopup from '@/components/CaptchaPopup.vue';

const router = useRouter();

// State
const formData = reactive({
  nama: '',
  email: '',
  nim: '',
  password: '',
  password_confirmation: '',
  tahun_lulus: '',
  no_telp: ''
});

const showCaptcha = ref(false);
const registrationData = ref(null);
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Validate form
const validateForm = () => {
  errorMessage.value = '';

  // Check required fields
  if (!formData.nama || !formData.email || !formData.nim || 
      !formData.password || !formData.password_confirmation || 
      !formData.tahun_lulus || !formData.no_telp) {
    errorMessage.value = 'Mohon lengkapi semua field yang wajib diisi';
    return false;
  }

  // Check password length
  if (formData.password.length < 8) {
    errorMessage.value = 'Password minimal 8 karakter';
    return false;
  }

  // Check password match
  if (formData.password !== formData.password_confirmation) {
    errorMessage.value = 'Konfirmasi password tidak cocok';
    return false;
  }

  // Check email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.email)) {
    errorMessage.value = 'Format email tidak valid';
    return false;
  }

  // Check phone format
  const phoneRegex = /^[0-9]{10,13}$/;
  if (!phoneRegex.test(formData.no_telp)) {
    errorMessage.value = 'No. telepon harus 10-13 digit angka';
    return false;
  }

  return true;
};

// Handle submit (show CAPTCHA)
const handleSelesai = () => {
  if (validateForm()) {
    // Save registration data temporarily
    registrationData.value = { ...formData };
    
    // Show CAPTCHA popup
    showCaptcha.value = true;
  }
};

// Handle CAPTCHA success (proceed with registration)
const handleCaptchaSuccess = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.post('/api/register', registrationData.value);

    if (response.data.success) {
      successMessage.value = 'Registrasi berhasil! Mengarahkan ke halaman login...';
      
      // Redirect to login after 2 seconds
      setTimeout(() => {
        router.push('/login');
      }, 2000);
    }
  } catch (error) {
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else if (error.response?.data?.errors) {
      // Handle validation errors
      const errors = Object.values(error.response.data.errors).flat();
      errorMessage.value = errors.join(', ');
    } else {
      errorMessage.value = 'Registrasi gagal. Silakan coba lagi.';
    }
    
    // Close CAPTCHA popup on error
    showCaptcha.value = false;
  } finally {
    isSubmitting.value = false;
  }
};

// Handle CAPTCHA close (user cancelled)
const handleCaptchaClose = () => {
  showCaptcha.value = false;
};
</script>

<style scoped>
.registration-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.registration-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 500px;
}

h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  text-align: center;
}

.subtitle {
  margin: 0 0 32px 0;
  color: #6b7280;
  text-align: center;
  font-size: 14px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.required {
  color: #ef4444;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.error-alert {
  padding: 12px 16px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-size: 14px;
  margin-bottom: 20px;
}

.success-alert {
  padding: 12px 16px;
  background-color: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  color: #16a34a;
  font-size: 14px;
  margin-bottom: 20px;
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-submit:active {
  transform: translateY(0);
}

.btn-submit:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.login-link {
  margin-top: 24px;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}

.login-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.login-link a:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 600px) {
  .registration-card {
    padding: 24px;
  }

  h1 {
    font-size: 24px;
  }

  .form-group input {
    font-size: 16px; /* Prevent zoom on iOS */
  }
}
</style>
