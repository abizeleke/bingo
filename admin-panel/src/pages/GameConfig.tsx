import React, { useState, useEffect } from 'react';

const GameConfig = () => {
  const [config, setConfig] = useState<any>({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchConfig();
  }, []);

  const fetchConfig = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/game-config');
      const data = await response.json();
      if (data.status === 'success') {
        setConfig(data.data);
      }
    } catch (error) {
      console.error('Error fetching config:', error);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8080/api/admin/game-config', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(config)
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert('Configuration updated successfully!');
      } else {
        alert(data.message || 'Error updating config');
      }
    } catch (error) {
      alert('Connection error');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setConfig({ ...config, [e.target.name]: parseFloat(e.target.value) });
  };

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>⚙️ Game Configuration</h1>
      </div>

      <div className="config-form-container">
        <form onSubmit={handleSubmit}>
          <div className="config-grid">
            <div className="config-item">
              <label>Card Number Range</label>
              <input
                type="number"
                name="card_number_max"
                value={config.card_number_max || 500}
                onChange={handleChange}
                required
              />
              <small>Maximum card number (e.g., 500)</small>
            </div>

            <div className="config-item">
              <label>Entry Fee (ETB)</label>
              <input
                type="number"
                name="entry_fee"
                value={config.entry_fee || 10}
                onChange={handleChange}
                step="0.5"
                required
              />
              <small>Cost per card</small>
            </div>

            <div className="config-item">
              <label>Required Cards</label>
              <input
                type="number"
                name="required_cards"
                value={config.required_cards || 10}
                onChange={handleChange}
                required
              />
              <small>Minimum cards to start game</small>
            </div>

            <div className="config-item">
              <label>Max Cards Per Player</label>
              <input
                type="number"
                name="max_cards_per_player"
                value={config.max_cards_per_player || 3}
                onChange={handleChange}
                required
              />
              <small>Maximum cards a player can select</small>
            </div>

            <div className="config-item">
              <label>Entry Duration (seconds)</label>
              <input
                type="number"
                name="entry_duration_seconds"
                value={config.entry_duration_seconds || 50}
                onChange={handleChange}
                required
              />
              <small>Time allowed for card selection</small>
            </div>

            <div className="config-item">
              <label>Bot Check Interval (seconds)</label>
              <input
                type="number"
                name="bot_check_interval_seconds"
                value={config.bot_check_interval_seconds || 10}
                onChange={handleChange}
                required
              />
              <small>How often bots check to join</small>
            </div>

            <div className="config-item">
              <label>Commission (%)</label>
              <input
                type="number"
                name="commission_percent"
                value={config.commission_percent || 10}
                onChange={handleChange}
                step="0.5"
                required
              />
              <small>Platform commission percentage</small>
            </div>
          </div>

          <button type="submit" className="btn-primary" disabled={loading}>
            {loading ? 'Saving...' : 'Save Configuration'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default GameConfig;