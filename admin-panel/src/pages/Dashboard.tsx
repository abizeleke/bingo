import React from 'react';

const Dashboard = () => {
  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>📊 Dashboard</h1>
      </div>
      
      <div className="dashboard-stats">
        <div className="stat-card">
          <div className="stat-icon">💰</div>
          <div className="stat-info">
            <span className="stat-value">0 ETB</span>
            <span className="stat-label">Total Revenue</span>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">👥</div>
          <div className="stat-info">
            <span className="stat-value">0</span>
            <span className="stat-label">Total Users</span>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">🎯</div>
          <div className="stat-info">
            <span className="stat-value">0</span>
            <span className="stat-label">Games Played</span>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">🏆</div>
          <div className="stat-info">
            <span className="stat-value">0 ETB</span>
            <span className="stat-label">Total Prizes</span>
          </div>
        </div>
      </div>

      <div className="dashboard-recent">
        <h3>Recent Activity</h3>
        <div className="activity-list">
          <p className="no-activity">No recent activity</p>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;