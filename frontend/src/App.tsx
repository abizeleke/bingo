import React, { useState } from 'react';
import GamePage from './pages/GamePage';
import WalletPage from './pages/WalletPage';
import ProfilePage from './pages/ProfilePage';
import './App.css';

function App() {
  const [activeTab, setActiveTab] = useState('game');

  const renderPage = () => {
    switch (activeTab) {
      case 'game':
        return <GamePage />;
      case 'wallet':
        return <WalletPage />;
      case 'profile':
        return <ProfilePage />;
      default:
        return <GamePage />;
    }
  };

  return (
    <div className="app">
      <div className="main-content">
        {renderPage()}
      </div>

      <div className="bottom-nav">
        <button
          className={`nav-item ${activeTab === 'game' ? 'active' : ''}`}
          onClick={() => setActiveTab('game')}
        >
          <span className="nav-icon">🎯</span>
          <span className="nav-label">Game</span>
        </button>

        <button
          className={`nav-item ${activeTab === 'wallet' ? 'active' : ''}`}
          onClick={() => setActiveTab('wallet')}
        >
          <span className="nav-icon">💰</span>
          <span className="nav-label">Wallet</span>
        </button>

        <button
          className={`nav-item ${activeTab === 'profile' ? 'active' : ''}`}
          onClick={() => setActiveTab('profile')}
        >
          <span className="nav-icon">👤</span>
          <span className="nav-label">Profile</span>
        </button>
      </div>
    </div>
  );
}

export default App;