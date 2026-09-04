import React, { useState, useEffect } from 'react';
import './ProfilePage.css';

const ProfilePage = () => {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [user, setUser] = useState<any>(null);
  const [showLogin, setShowLogin] = useState(false);
  const [showRegister, setShowRegister] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    if (token && userData) {
      setIsLoggedIn(true);
      setUser(JSON.parse(userData));
    }
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setIsLoggedIn(false);
    setUser(null);
  };

  return (
    <div className="profile-page">
      <div className="profile-header">
        <div className="profile-avatar">
          {isLoggedIn ? user?.username?.charAt(0).toUpperCase() || 'U' : '👤'}
        </div>
        <h2>{isLoggedIn ? user?.username || 'User' : 'Guest'}</h2>
        <p>{isLoggedIn ? user?.phone || '' : 'Please login or register'}</p>
      </div>

      {isLoggedIn && (
        <div className="profile-stats">
          <div className="stat-item">
            <span className="stat-value">0</span>
            <span className="stat-label">Games Played</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">0</span>
            <span className="stat-label">Games Won</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">0 ETB</span>
            <span className="stat-label">Total Winnings</span>
          </div>
        </div>
      )}

      {!isLoggedIn ? (
        <div className="auth-buttons">
          <button className="btn-login" onClick={() => setShowLogin(true)}>
            Login
          </button>
          <button className="btn-register" onClick={() => setShowRegister(true)}>
            Register
          </button>
        </div>
      ) : (
        <button className="btn-logout" onClick={handleLogout}>
          Logout
        </button>
      )}

      <div className="profile-menu">
        <div className="menu-item">
          <span className="menu-icon">📋</span>
          <span>Game History</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">🔔</span>
          <span>Notifications</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">🎁</span>
          <span>Referral Program</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">🌐</span>
          <span>Language</span>
          <span className="menu-value">English</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">🔊</span>
          <span>Sound</span>
          <span className="menu-value">On</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">🌙</span>
          <span>Dark Mode</span>
          <span className="menu-value">On</span>
          <span className="menu-arrow">›</span>
        </div>
        <div className="menu-item">
          <span className="menu-icon">ℹ️</span>
          <span>About</span>
          <span className="menu-arrow">›</span>
        </div>
      </div>

      <div className="profile-version">Bingo App v1.0.0</div>

      {showLogin && (
        <div className="modal-overlay" onClick={() => setShowLogin(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowLogin(false)}>✕</button>
            <h3>Welcome Back</h3>
            <LoginForm onClose={() => setShowLogin(false)} setUser={setUser} setIsLoggedIn={setIsLoggedIn} />
          </div>
        </div>
      )}

      {showRegister && (
        <div className="modal-overlay" onClick={() => setShowRegister(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowRegister(false)}>✕</button>
            <h3>Create Account</h3>
            <RegisterForm onClose={() => setShowRegister(false)} setUser={setUser} setIsLoggedIn={setIsLoggedIn} />
          </div>
        </div>
      )}
    </div>
  );
};

// Login Form
const LoginForm = ({ onClose, setUser, setIsLoggedIn }: any) => {
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8080/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone_number: phone, password })
      });
      const data = await response.json();
      if (data.status === 'success') {
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data));
        setUser(data.data);
        setIsLoggedIn(true);
        onClose();
      } else {
        alert(data.message);
      }
    } catch (error) {
      alert('Connection error. Make sure backend is running.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="Phone Number"
        value={phone}
        onChange={(e) => setPhone(e.target.value)}
        required
      />
      <input
        type="password"
        placeholder="Password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        required
      />
      <button type="submit" disabled={loading}>
        {loading ? 'Logging in...' : 'Login'}
      </button>
    </form>
  );
};

// Register Form
const RegisterForm = ({ onClose, setUser, setIsLoggedIn }: any) => {
  const [formData, setFormData] = useState({
    telegram_username: '',
    phone_number: '',
    password: '',
    confirmPassword: ''
  });
  const [loading, setLoading] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.password !== formData.confirmPassword) {
      alert('Passwords do not match');
      return;
    }
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8080/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          phone_number: formData.phone_number,
          password: formData.password,
          telegram_username: formData.telegram_username
        })
      });
      const data = await response.json();
      if (data.status === 'success') {
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data));
        setUser(data.data);
        setIsLoggedIn(true);
        onClose();
      } else {
        alert(data.message);
      }
    } catch (error) {
      alert('Connection error. Make sure backend is running.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        name="telegram_username"
        placeholder="Username"
        value={formData.telegram_username}
        onChange={handleChange}
        required
      />
      <input
        type="text"
        name="phone_number"
        placeholder="Phone Number"
        value={formData.phone_number}
        onChange={handleChange}
        required
      />
      <input
        type="password"
        name="password"
        placeholder="Password (min 6 characters)"
        value={formData.password}
        onChange={handleChange}
        required
        minLength={6}
      />
      <input
        type="password"
        name="confirmPassword"
        placeholder="Confirm Password"
        value={formData.confirmPassword}
        onChange={handleChange}
        required
      />
      <button type="submit" disabled={loading}>
        {loading ? 'Creating account...' : 'Create Account'}
      </button>
    </form>
  );
};

export default ProfilePage;