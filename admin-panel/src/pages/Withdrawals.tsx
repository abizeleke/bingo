import React, { useState, useEffect } from 'react';

const Withdrawals = () => {
  const [withdrawals, setWithdrawals] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchWithdrawals();
  }, []);

  const fetchWithdrawals = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/withdrawals');
      const data = await response.json();
      if (data.status === 'success') {
        setWithdrawals(data.data);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleStatus = async (id: number, status: string) => {
    if (!confirm(`Are you sure you want to ${status} this withdrawal?`)) return;
    
    setLoading(true);
    try {
      const response = await fetch(`http://localhost:8080/api/admin/withdrawals/${id}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status })
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert(`Withdrawal ${status} successfully!`);
        fetchWithdrawals();
      } else {
        alert(data.message || 'Error updating withdrawal');
      }
    } catch (error) {
      alert('Connection error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>💸 Withdrawals</h1>
        <span className="total-count">Total: {withdrawals.length}</span>
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Requested</th>
              <th>Fee</th>
              <th>Net</th>
              <th>Method</th>
              <th>Account</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {withdrawals.length === 0 ? (
              <tr>
                <td colSpan={10} style={{ textAlign: 'center', color: '#5a6680' }}>
                  No withdrawals found
                </td>
              </tr>
            ) : (
              withdrawals.map((w: any) => (
                <tr key={w.id}>
                  <td>#{w.id}</td>
                  <td>{w.telegram_username || 'Unknown'}</td>
                  <td><strong>{w.requested_amount} ETB</strong></td>
                  <td>{w.fee_amount} ETB</td>
                  <td style={{ color: '#13b96d' }}>{w.received_amount} ETB</td>
                  <td>{w.method_name || '-'}</td>
                  <td>{w.account_number || '-'}</td>
                  <td>
                    <span className={`status-badge ${w.status}`}>
                      {w.status}
                    </span>
                  </td>
                  <td>{new Date(w.requested_at).toLocaleDateString()}</td>
                  <td>
                    {w.status === 'pending' && (
                      <div className="action-buttons">
                        <button
                          className="btn-approve"
                          onClick={() => handleStatus(w.id, 'approved')}
                          disabled={loading}
                        >
                          Approve
                        </button>
                        <button
                          className="btn-reject"
                          onClick={() => handleStatus(w.id, 'rejected')}
                          disabled={loading}
                        >
                          Reject
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default Withdrawals;