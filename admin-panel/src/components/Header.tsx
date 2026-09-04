import React from 'react';

const Header = () => {
  const handleLogout = () => {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin');
    window.location.href = '/admin/login';
  };

  const admin = JSON.parse(localStorage.getItem('admin') || '{}');

  return (
    <div className="admin-header">
      <h1>Welcome, {admin.username || 'Admin'}</h1>
      <button className="btn-logout" onClick={handleLogout}>
        Logout
      </button>
    </div>
  );
};

export default Header;