import React, { useState, useEffect } from 'react';

const Deposits = () => {
  const [deposits, setDeposits] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchDeposits();
  }, []);

  const fetchDeposits = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/deposits');
      const data = await response.json();
      if (data.status === 'success') {
        setDeposits(data.data);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleStatus = async (id: number, status: string) => {
    if (!confirm(`Are you sure you want to ${status} this deposit?`)) return;
    
    setLoading(true);
    try {
      const response = await fetch(`http://localhost:8080/api/admin/deposits/${id}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status })
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert(`Deposit ${status} successfully!`);
        fetchDeposits();
      } else {
        alert(data.message || 'Error updating deposit');
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
        <h1>💰 Deposits</h1>
        <span className="total-count">Total: {deposits.length}</span>
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Account</th>
              <th>Reference</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {deposits.length === 0 ? (
              <tr>
                <td colSpan={9} style={{ textAlign: 'center', color: '#5a6680' }}>
                  No deposits found
                </td>
              </tr>
            ) : (
              deposits.map((deposit: any) => (
                <tr key={deposit.id}>
                  <td>#{deposit.id}</td>
                  <td>{deposit.telegram_username || 'Unknown'}</td>
                  <td><strong>{deposit.amount} ETB</strong></td>
                  <td>{deposit.method_name || '-'}</td>
                  <td>{deposit.account_name || '-'}</td>
                  <td>{deposit.transaction_number || '-'}</td>
                  <td>
                    <span className={`status-badge ${deposit.status}`}>
                      {deposit.status}
                    </span>
                  </td>
                  <td>{new Date(deposit.requested_at).toLocaleDateString()}</td>
                  <td>
                    {deposit.status === 'pending' && (
                      <div className="action-buttons">
                        <button
                          className="btn-approve"
                          onClick={() => handleStatus(deposit.id, 'approved')}
                          disabled={loading}
                        >
                          Approve
                        </button>
                        <button
                          className="btn-reject"
                          onClick={() => handleStatus(deposit.id, 'rejected')}
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

export default Deposits;