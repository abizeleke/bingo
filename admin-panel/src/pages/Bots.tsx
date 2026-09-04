import React, { useState, useEffect } from 'react';

const Bots = () => {
  const [bots, setBots] = useState<any[]>([]);
  const [showModal, setShowModal] = useState(false);
  const [newBot, setNewBot] = useState({ bot_name: '', status: 'active' });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchBots();
  }, []);

  const fetchBots = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/bots');
      const data = await response.json();
      if (data.status === 'success') {
        setBots(data.data);
      }
    } catch (error) {
      console.error('Error fetching bots:', error);
    }
  };

  const handleAddBot = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8080/api/admin/bots', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newBot)
      });
      const data = await response.json();
      if (data.status === 'success') {
        setShowModal(false);
        setNewBot({ bot_name: '', status: 'active' });
        fetchBots();
        alert('Bot created successfully!');
      } else {
        alert(data.message || 'Error creating bot');
      }
    } catch (error) {
      alert('Connection error');
    } finally {
      setLoading(false);
    }
  };

  const toggleStatus = async (id: number, currentStatus: string) => {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    if (!confirm(`Are you sure you want to ${newStatus} this bot?`)) return;
    
    try {
      const response = await fetch(`http://localhost:8080/api/admin/bots/${id}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      });
      const data = await response.json();
      if (data.status === 'success') {
        fetchBots();
      }
    } catch (error) {
      console.error('Error updating bot:', error);
    }
  };

  const deleteBot = async (id: number) => {
    if (!confirm('Are you sure you want to delete this bot?')) return;
    
    try {
      const response = await fetch(`http://localhost:8080/api/admin/bots/${id}`, {
        method: 'DELETE'
      });
      const data = await response.json();
      if (data.status === 'success') {
        fetchBots();
        alert('Bot deleted');
      }
    } catch (error) {
      console.error('Error deleting bot:', error);
    }
  };

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>🤖 Bot Management</h1>
        <button className="btn-primary" onClick={() => setShowModal(true)}>
          + Add Bot
        </button>
      </div>

      <div className="bots-stats">
        <div className="stat-box">
          <span>Total Bots</span>
          <strong>{bots.length}</strong>
        </div>
        <div className="stat-box">
          <span>Active</span>
          <strong>{bots.filter(b => b.status === 'active').length}</strong>
        </div>
        <div className="stat-box">
          <span>Inactive</span>
          <strong>{bots.filter(b => b.status !== 'active').length}</strong>
        </div>
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Bot Name</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {bots.length === 0 ? (
              <tr>
                <td colSpan={5} style={{ textAlign: 'center', color: '#5a6680' }}>
                  No bots created yet
                </td>
              </tr>
            ) : (
              bots.map((bot: any) => (
                <tr key={bot.id}>
                  <td>#{bot.id}</td>
                  <td>{bot.bot_name}</td>
                  <td>
                    <span className={`status-badge ${bot.status}`}>
                      {bot.status}
                    </span>
                  </td>
                  <td>{new Date(bot.created_at).toLocaleDateString()}</td>
                  <td>
                    <div className="action-buttons">
                      <button
                        className={`btn-status ${bot.status}`}
                        onClick={() => toggleStatus(bot.id, bot.status)}
                      >
                        {bot.status === 'active' ? 'Deactivate' : 'Activate'}
                      </button>
                      <button
                        className="btn-delete"
                        onClick={() => deleteBot(bot.id)}
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Add Bot Modal */}
      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowModal(false)}>✕</button>
            <h2>Add Bot</h2>
            <form onSubmit={handleAddBot}>
              <input
                type="text"
                placeholder="Bot Name (e.g., Bot Alpha)"
                value={newBot.bot_name}
                onChange={(e) => setNewBot({ ...newBot, bot_name: e.target.value })}
                required
              />
              <select
                value={newBot.status}
                onChange={(e) => setNewBot({ ...newBot, status: e.target.value })}
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
              <div className="modal-actions">
                <button type="button" onClick={() => setShowModal(false)}>Cancel</button>
                <button type="submit" disabled={loading}>
                  {loading ? 'Adding...' : 'Add Bot'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Bots;