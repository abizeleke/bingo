import React, { useState, useEffect } from 'react';

interface PaymentMethod {
  id: number;
  name: string;
  status: string;
  created_at: string;
}

const PaymentMethods = () => {
  const [methods, setMethods] = useState<PaymentMethod[]>([]);
  const [showModal, setShowModal] = useState(false);
  const [newMethod, setNewMethod] = useState({ name: '' });

  useEffect(() => {
    // Fetch payment methods from API
    fetchMethods();
  }, []);

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

  const handleAddMethod = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await fetch('http://localhost:8080/api/admin/payment-methods', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newMethod)
      });
      const data = await response.json();
      if (data.status === 'success') {
        setShowModal(false);
        setNewMethod({ name: '' });
        fetchMethods();
      }
    } catch (error) {
      console.error('Error adding method:', error);
    }
  };

  const toggleStatus = async (id: number, currentStatus: string) => {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    try {
      const response = await fetch(`http://localhost:8080/api/admin/payment-methods/${id}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      });
      const data = await response.json();
      if (data.status === 'success') {
        fetchMethods();
      }
    } catch (error) {
      console.error('Error updating status:', error);
    }
  };

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>💳 Payment Methods</h1>
        <button className="btn-primary" onClick={() => setShowModal(true)}>
          + Add Payment Method
        </button>
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {methods.map(method => (
              <tr key={method.id}>
                <td>{method.id}</td>
                <td>{method.name}</td>
                <td>
                  <span className={`status-badge ${method.status}`}>
                    {method.status}
                  </span>
                </td>
                <td>{new Date(method.created_at).toLocaleDateString()}</td>
                <td>
                  <button
                    className={`btn-status ${method.status}`}
                    onClick={() => toggleStatus(method.id, method.status)}
                  >
                    {method.status === 'active' ? 'Deactivate' : 'Activate'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Modal */}
      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h2>Add Payment Method</h2>
            <form onSubmit={handleAddMethod}>
              <input
                type="text"
                placeholder="Method Name (e.g., Telebirr, Bank Transfer)"
                value={newMethod.name}
                onChange={(e) => setNewMethod({ name: e.target.value })}
                required
              />
              <div className="modal-actions">
                <button type="button" onClick={() => setShowModal(false)}>Cancel</button>
                <button type="submit">Add Method</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentMethods;