import React from 'react';

interface SidebarProps {
  currentPage: string;
  setCurrentPage: (page: string) => void;
}

const Sidebar: React.FC<SidebarProps> = ({ currentPage, setCurrentPage }) => {
  const menuItems = [
  { id: 'dashboard', label: '📊 Dashboard', icon: '📊' },
  { id: 'game-config', label: '⚙️ Game Config', icon: '⚙️' },
  { id: 'bots', label: '🤖 Bots', icon: '🤖' },
  { id: 'payment-methods', label: '💳 Payment Methods', icon: '💳' },
  { id: 'payment-accounts', label: '🏦 Payment Accounts', icon: '🏦' },
  { id: 'deposits', label: '💰 Deposits', icon: '💰' },
  { id: 'withdrawals', label: '💸 Withdrawals', icon: '💸' },
  { id: 'users', label: '👥 Users', icon: '👥' },
];

  return (
    <div className="admin-sidebar">
      <div className="sidebar-logo">🎯 BINGO ADMIN</div>
      <nav className="sidebar-nav">
        {menuItems.map(item => (
          <button
            key={item.id}
            className={`sidebar-item ${currentPage === item.id ? 'active' : ''}`}
            onClick={() => setCurrentPage(item.id)}
          >
            <span className="sidebar-icon">{item.icon}</span>
            <span className="sidebar-label">{item.label}</span>
          </button>
        ))}
      </nav>
    </div>
  );
};

export default Sidebar;