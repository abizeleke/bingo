import React, { useState, useEffect } from 'react';

const PaymentAccounts = () => {
  const [accounts, setAccounts] = useState<any[]>([]);
  const [methods, setMethods] = useState<any[]>([]);
  const [showModal, setShowModal] = useState(false);
  const [loading, setLoading] = useState(false);
  const [newAccount, setNewAccount] = useState({
    payment_method_id: '',
    account_name: '',
    account_number: ''
  });

  useEffect(() => {
    fetchAccounts();
    fetchMethods();
  }, []);

  const fetchAccounts = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/payment-accounts');
      const data = await response.json();
      if (data.status === 'success') {
        setAccounts(data.data);
      }
    } catch (error) {
      console.error('Error fetching accounts:', error);
    }
  };

  const fetchMethods = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/payment-methods');
      const data = await response.json();
      if (data.status === 'success') {
        setMethods(data.data);
      }
    } catch (error) {
      console.error('Error fetching methods:', error);
    }
  };

  const handleAddAccount = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8080/api/admin/payment-accounts', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          payment_method_id: parseInt(newAccount.payment_method_id),
          account_name: newAccount.account_name,
          account_number: newAccount.account_number
        })
      });
      const data = await response.json();
      if (data.status === 'success') {
        setShowModal(false);
        setNewAccount({ payment_method_id: '', account_name: '', account_number: '' });
        fetchAccounts();
        alert('Account added successfully!');
      } else {
        alert(data.message || 'Error adding account');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Connection error');
    } finally {
      setLoading(false);
    }
  };

  const toggleStatus = async (id: number, currentStatus: string) => {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    try {
      const response = await fetch(`http://localhost:8080/api/admin/payment-accounts/${id}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      });
      const data = await response.json();
      if (data.status === 'success') {
        fetchAccounts();
      }
    } catch (error) {
      console.error('Error updating status:', error);
    }
  };

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>🏦 Payment Accounts</h1>
        <button className="btn-primary" onClick={() => setShowModal(true)}>
          + Add Account
        </button>
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Payment Method</th>
              <th>Account Name</th>
              <th>Account Number</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {accounts.length === 0 ? (
              <tr>
                <td colSpan={6} style={{ textAlign: 'center', color: '#5a6680' }}>
                  No payment accounts found
                </td>
              </tr>
            ) : (
              accounts.map((account: any) => (
                <tr key={account.id}>
                  <td>{account.id}</td>
                  <td>
                    <span className="method-tag">
                      {account.method_name || account.payment_method?.name || '-'}
                    </span>
                  </td>
                  <td>{account.account_name}</td>
                  <td>{account.account_number}</td>
                  <td>
                    <span className={`status-badge ${account.status}`}>
                      {account.status}
                    </span>
                  </td>
                  <td>
                    <button
                      className={`btn-status ${account.status}`}
                      onClick={() => toggleStatus(account.id, account.status)}
                    >
                      {account.status === 'active' ? 'Deactivate' : 'Activate'}
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Add Account Modal */}
      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h2>Add Payment Account</h2>
            <form onSubmit={handleAddAccount}>
              <select
                value={newAccount.payment_method_id}
                onChange={(e) => setNewAccount({ ...newAccount, payment_method_id: e.target.value })}
                required
              >
                <option value="">Select Payment Method</option>
                {methods.map((method: any) => (
                  <option key={method.id} value={method.id}>{method.name}</option>
                ))}
              </select>
              <input
                type="text"
                placeholder="Account Name"
                value={newAccount.account_name}
                onChange={(e) => setNewAccount({ ...newAccount, account_name: e.target.value })}
                required
              />
              <input
                type="text"
                placeholder="Account Number"
                value={newAccount.account_number}
                onChange={(e) => setNewAccount({ ...newAccount, account_number: e.target.value })}
                required
              />
              <div className="modal-actions">
                <button type="button" onClick={() => setShowModal(false)}>Cancel</button>
                <button type="submit" disabled={loading}>
                  {loading ? 'Adding...' : 'Add Account'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentAccounts;