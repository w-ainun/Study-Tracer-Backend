<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="isOpen" class="captcha-overlay" @click.self="handleClose">
        <div class="captcha-modal">
          <!-- Header -->
          <div class="captcha-header">
            <h2>Verifikasi CAPTCHA</h2>
            <button 
              class="close-btn" 
              @click="handleClose" 
              :disabled="loading"
            >
              &times;
            </button>
          </div>

          <!-- Body -->
          <form @submit.prevent="handleSubmit">
            <div class="captcha-body">
              <p class="captcha-instruction">
                Silakan masukkan kode yang terlihat pada gambar di bawah ini
              </p>

              <!-- CAPTCHA Image -->
              <div class="captcha-image-container">
                <img 
                  v-if="captchaImage" 
                  :src="captchaImage" 
                  alt="CAPTCHA" 
                  class="captcha-image" 
                />
                <div v-else class="captcha-loading">Loading...</div>
                
                <button 
                  type="button" 
                  class="refresh-btn" 
                  @click="handleRefresh"
                  :disabled="loading"
                  title="Muat ulang CAPTCHA"
                >
                  &#x21bb;
                </button>
              </div>

              <!-- Input -->
              <input
                v-model="captchaInput"
                type="text"
                class="captcha-input"
                placeholder="Masukkan kode CAPTCHA"
                :disabled="loading"
                ref="inputRef"
              />

              <!-- Error Message -->
              <div v-if="error" class="captcha-error">
                {{ error }}
              </div>
            </div>

            <!-- Footer -->
            <div class="captcha-footer">
              <button 
                type="button" 
                class="btn-secondary" 
                @click="handleClose"
                :disabled="loading"
              >
                Batal
              </button>
              <button 
                type="submit" 
                class="btn-primary" 
                :disabled="loading || !captchaInput.trim()"
              >
                {{ loading ? 'Memverifikasi...' : 'Verifikasi' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
});

// Emits
const emit = defineEmits(['close', 'success']);

// State
const captchaImage = ref('');
const captchaInput = ref('');
const loading = ref(false);
const error = ref('');
const inputRef = ref(null);

// Watch for popup open
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    fetchCaptcha();
    captchaInput.value = '';
    error.value = '';
    
    // Auto-focus input after popup animation
    nextTick(() => {
      inputRef.value?.focus();
    });
  }
});

// Fetch CAPTCHA image
const fetchCaptcha = async () => {
  try {
    const response = await axios.get('/api/captcha/generate');
    if (response.data.success) {
      captchaImage.value = response.data.captcha.image;
    }
  } catch (err) {
    console.error('Failed to fetch CAPTCHA:', err);
    error.value = 'Gagal memuat CAPTCHA. Silakan refresh halaman.';
  }
};

// Refresh CAPTCHA
const handleRefresh = () => {
  fetchCaptcha();
  captchaInput.value = '';
  error.value = '';
};

// Submit verification
const handleSubmit = async () => {
  if (!captchaInput.value.trim()) {
    error.value = 'Silakan masukkan kode CAPTCHA';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post('/api/captcha/verify', {
      captcha: captchaInput.value
    });

    if (response.data.success) {
      // CAPTCHA verified successfully
      emit('success');
      emit('close');
    }
  } catch (err) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Verifikasi gagal. Silakan coba lagi.';
    }
    
    // Refresh CAPTCHA on failure
    fetchCaptcha();
    captchaInput.value = '';
  } finally {
    loading.value = false;
  }
};

// Close popup
const handleClose = () => {
  if (!loading.value) {
    emit('close');
  }
};
</script>

<style scoped>
/* Overlay untuk popup CAPTCHA */
.captcha-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

/* Modal container */
.captcha-modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  width: 90%;
  max-width: 450px;
  overflow: hidden;
}

/* Header */
.captcha-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.captcha-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-btn:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.close-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Body */
.captcha-body {
  padding: 24px;
}

.captcha-instruction {
  margin: 0 0 20px 0;
  color: #6b7280;
  font-size: 14px;
  text-align: center;
}

/* CAPTCHA Image Container */
.captcha-image-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  min-height: 80px;
}

.captcha-image {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
}

.captcha-loading {
  color: #9ca3af;
  font-size: 14px;
}

.refresh-btn {
  position: absolute;
  right: 8px;
  top: 8px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 20px;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.refresh-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
  transform: rotate(180deg);
}

.refresh-btn:active {
  transform: rotate(180deg) scale(0.95);
}

.refresh-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Input */
.captcha-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.2s;
  box-sizing: border-box;
}

.captcha-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.captcha-input:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

/* Error message */
.captcha-error {
  margin-top: 12px;
  padding: 12px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #dc2626;
  font-size: 14px;
  text-align: center;
}

/* Footer */
.captcha-footer {
  display: flex;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.captcha-footer button {
  flex: 1;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db !important;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af !important;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-active .captcha-modal {
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 480px) {
  .captcha-modal {
    width: 95%;
    margin: 0 10px;
  }

  .captcha-header,
  .captcha-body,
  .captcha-footer {
    padding: 16px;
  }

  .captcha-footer {
    flex-direction: column;
  }

  .captcha-footer button {
    width: 100%;
  }
}
</style>
