import React, { useState, useEffect } from 'react';
import './App.css';

// Pages
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import GameConfig from './pages/GameConfig';
import Bots from './pages/Bots';
import PaymentMethods from './pages/PaymentMethods';
import PaymentAccounts from './pages/PaymentAccounts';
import Deposits from './pages/Deposits';
import Withdrawals from './pages/Withdrawals';
import Users from './pages/Users';

// Components
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import ProtectedRoute from './components/ProtectedRoute';

function App() {
  const [currentPage, setCurrentPage] = useState('dashboard');
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('admin_token');
    setIsLoggedIn(!!token);
  }, []);

  if (!isLoggedIn && window.location.pathname !== '/admin/login') {
    return <Login />;
  }

  if (window.location.pathname === '/admin/login') {
    return <Login />;
  }

  const renderPage = () => {
    switch(currentPage) {
      case 'dashboard':
        return <Dashboard />;
      case 'game-config':
        return <GameConfig />;
      case 'bots':
        return <Bots />;
      case 'payment-methods':
        return <PaymentMethods />;
      case 'payment-accounts':
        return <PaymentAccounts />;
      case 'deposits':
        return <Deposits />;
      case 'withdrawals':
        return <Withdrawals />;
      case 'users':
        return <Users />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <ProtectedRoute>
      <div className="admin-app">
        <Sidebar currentPage={currentPage} setCurrentPage={setCurrentPage} />
        <div className="admin-main">
          <Header />
          <div className="admin-content">
            {renderPage()}
          </div>
        </div>
      </div>
    </ProtectedRoute>
  );
}

export default App;