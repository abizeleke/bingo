import React, { useState, useEffect } from 'react';

const Users = () => {
  const [users, setUsers] = useState<any[]>([]);
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin/users');
      const data = await response.json();
      if (data.status === 'success') {
        setUsers(data.data);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const filteredUsers = users.filter((user: any) =>
    user.telegram_username?.toLowerCase().includes(search.toLowerCase()) ||
    user.phone?.includes(search)
  );

  return (
    <div className="admin-page">
      <div className="page-header">
        <h1>👥 Users</h1>
        <input
          type="text"
          className="search-input"
          placeholder="Search users..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      <div className="table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredUsers.length === 0 ? (
              <tr>
                <td colSpan={6} style={{ textAlign: 'center', color: '#5a6680' }}>
                  No users found
                </td>
              </tr>
            ) : (
              filteredUsers.map((user: any) => (
                <tr key={user.id}>
                  <td>#{user.id}</td>
                  <td>{user.telegram_username || '-'}</td>
                  <td>{user.phone || '-'}</td>
                  <td>
                    <span className={`status-badge ${user.is_active ? 'active' : 'inactive'}`}>
                      {user.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td>{new Date(user.created_at).toLocaleDateString()}</td>
                  <td>
                    <button className="btn-view">View</button>
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

export default Users;