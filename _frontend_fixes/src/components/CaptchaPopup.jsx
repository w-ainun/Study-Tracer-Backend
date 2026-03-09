import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './CaptchaPopup.css';

const CaptchaPopup = ({ isOpen, onClose, onSuccess }) => {
  const [captchaImage, setCaptchaImage] = useState('');
  const [captchaInput, setCaptchaInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Fetch CAPTCHA when popup opens
  useEffect(() => {
    if (isOpen) {
      fetchCaptcha();
      setCaptchaInput('');
      setError('');
    }
  }, [isOpen]);

  const fetchCaptcha = async () => {
    try {
      const response = await axios.get('/api/captcha/generate');
      if (response.data.success) {
        setCaptchaImage(response.data.captcha.image);
      }
    } catch (err) {
      console.error('Failed to fetch CAPTCHA:', err);
      setError('Gagal memuat CAPTCHA. Silakan refresh halaman.');
    }
  };

  const handleRefresh = () => {
    fetchCaptcha();
    setCaptchaInput('');
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!captchaInput.trim()) {
      setError('Silakan masukkan kode CAPTCHA');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await axios.post('/api/captcha/verify', {
        captcha: captchaInput
      });

      if (response.data.success) {
        // CAPTCHA verified successfully
        onSuccess();
        onClose();
      }
    } catch (err) {
      if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Verifikasi gagal. Silakan coba lagi.');
      }
      // Refresh CAPTCHA on failure
      fetchCaptcha();
      setCaptchaInput('');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="captcha-overlay">
      <div className="captcha-modal">
        <div className="captcha-header">
          <h2>Verifikasi CAPTCHA</h2>
          <button className="close-btn" onClick={onClose} disabled={loading}>
            &times;
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="captcha-body">
            <p className="captcha-instruction">
              Silakan masukkan kode yang terlihat pada gambar di bawah ini
            </p>

            <div className="captcha-image-container">
              {captchaImage ? (
                <img src={captchaImage} alt="CAPTCHA" className="captcha-image" />
              ) : (
                <div className="captcha-loading">Loading...</div>
              )}
              <button 
                type="button" 
                className="refresh-btn" 
                onClick={handleRefresh}
                disabled={loading}
                title="Muat ulang CAPTCHA"
              >
                &#x21bb;
              </button>
            </div>

            <input
              type="text"
              className="captcha-input"
              placeholder="Masukkan kode CAPTCHA"
              value={captchaInput}
              onChange={(e) => setCaptchaInput(e.target.value)}
              disabled={loading}
              autoFocus
            />

            {error && (
              <div className="captcha-error">
                {error}
              </div>
            )}
          </div>

          <div className="captcha-footer">
            <button 
              type="button" 
              className="btn-secondary" 
              onClick={onClose}
              disabled={loading}
            >
              Batal
            </button>
            <button 
              type="submit" 
              className="btn-primary" 
              disabled={loading || !captchaInput.trim()}
            >
              {loading ? 'Memverifikasi...' : 'Verifikasi'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CaptchaPopup;
