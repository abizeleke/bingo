import React, { useState, useEffect } from 'react';
import './WalletPage.css';

const WalletPage = () => {
  const [balance, setBalance] = useState(0);
  const [transactions, setTransactions] = useState<any[]>([]);
  const [showDeposit, setShowDeposit] = useState(false);
  const [showWithdraw, setShowWithdraw] = useState(false);
  const [paymentMethods, setPaymentMethods] = useState<any[]>([]);
  const [paymentAccounts, setPaymentAccounts] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [depositData, setDepositData] = useState({
    amount: '',
    payment_method_id: '',
    payment_account_id: '',
    transaction_number: ''
  });

  const [withdrawData, setWithdrawData] = useState({
    amount: '',
    payment_method_id: '',
    account_number: ''
  });

  const getUserInfo = () => {
    try {
      const userStr = localStorage.getItem('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        return user.user_id || user.id || 1;
      }
      return 1;
    } catch (e) {
      return 1;
    }
  };

  const userId = getUserInfo();

  useEffect(() => {
    fetchWallet();
    fetchTransactions();
    fetchPaymentMethods();
  }, []);

  const fetchWallet = async () => {
    try {
      const response = await fetch(`http://localhost:8080/api/wallet/${userId}`);
      const data = await response.json();
      if (data.status === 'success') {
        const bal = parseFloat(data.data?.balance) || 0;
        setBalance(bal);
      }
    } catch (error) {
      console.error('Error fetching wallet:', error);
      setBalance(0);
    }
  };

  const fetchTransactions = async () => {
    try {
      const response = await fetch(`http://localhost:8080/api/wallet/${userId}/transactions`);
      const data = await response.json();
      if (data.status === 'success') {
        setTransactions(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching transactions:', error);
    }
  };

  const fetchPaymentMethods = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/payment-methods');
      const data = await response.json();
      if (data.status === 'success') {
        setPaymentMethods(data.data || []);
        if (data.data && data.data.length > 0) {
          fetchPaymentAccounts(data.data[0].id);
        }
      }
    } catch (error) {
      console.error('Error fetching payment methods:', error);
    }
  };

  const fetchPaymentAccounts = async (methodId: number) => {
    try {
      const response = await fetch(`http://localhost:8080/api/payment-methods/${methodId}/accounts`);
      const data = await response.json();
      if (data.status === 'success') {
        setPaymentAccounts(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching payment accounts:', error);
    }
  };

  const handleDeposit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const response = await fetch('http://localhost:8080/api/deposits', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          amount: parseFloat(depositData.amount),
          payment_method_id: parseInt(depositData.payment_method_id),
          payment_account_id: parseInt(depositData.payment_account_id),
          transaction_number: depositData.transaction_number
        })
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert('Deposit request submitted! Wait for admin approval.');
        setShowDeposit(false);
        setDepositData({ amount: '', payment_method_id: '', payment_account_id: '', transaction_number: '' });
        fetchWallet();
        fetchTransactions();
      } else {
        setError(data.message || 'Deposit failed');
      }
    } catch (error) {
      setError('Connection error');
    } finally {
      setLoading(false);
    }
  };

  const handleWithdraw = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const response = await fetch('http://localhost:8080/api/withdrawals', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          amount: parseFloat(withdrawData.amount),
          payment_method_id: parseInt(withdrawData.payment_method_id),
          account_number: withdrawData.account_number
        })
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert('Withdrawal request submitted! Wait for admin approval.');
        setShowWithdraw(false);
        setWithdrawData({ amount: '', payment_method_id: '', account_number: '' });
        fetchWallet();
        fetchTransactions();
      } else {
        setError(data.message || 'Withdrawal failed');
      }
    } catch (error) {
      setError('Connection error');
    } finally {
      setLoading(false);
    }
  };

  const getTransactionIcon = (type: string) => {
    switch(type) {
      case 'deposit': return '💰';
      case 'withdrawal': return '💸';
      case 'entry_fee': return '🎯';
      case 'winning': return '🏆';
      case 'refund': return '↩️';
      default: return '📝';
    }
  };

  const getTransactionColor = (type: string) => {
    switch(type) {
      case 'deposit': return '#13b96d';
      case 'winning': return '#f59e0b';
      case 'refund': return '#3b82f6';
      case 'withdrawal': return '#ef4444';
      case 'entry_fee': return '#6b7280';
      default: return '#8892a0';
    }
  };

  return (
    <div className="wallet-page">
      <div className="balance-card">
        <div className="balance-icon">💎</div>
        <div className="balance-amount">{Number(balance).toFixed(2)} ETB</div>
        <div className="balance-label">Available Balance</div>
        <div className="balance-actions">
          <button className="btn-deposit" onClick={() => setShowDeposit(true)}>
            💰 Deposit
          </button>
          <button className="btn-withdraw" onClick={() => setShowWithdraw(true)}>
            💳 Withdraw
          </button>
        </div>
      </div>

      <div className="transactions-section">
        <h3>📋 Transaction History</h3>
        <div className="transactions-list">
          {transactions.length === 0 ? (
            <div className="no-transactions">No transactions yet</div>
          ) : (
            transactions.map((tx: any) => (
              <div key={tx.id} className="transaction-item">
                <div className="tx-icon">{getTransactionIcon(tx.transaction_type)}</div>
                <div className="tx-info">
                  <div className="tx-type">{tx.transaction_type}</div>
                  <div className="tx-date">{new Date(tx.created_at).toLocaleDateString()}</div>
                </div>
                <div 
                  className="tx-amount"
                  style={{ color: getTransactionColor(tx.transaction_type) }}
                >
                  {tx.transaction_type === 'deposit' || tx.transaction_type === 'winning' ? '+' : '-'}
                  {tx.amount} ETB
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {showDeposit && (
        <div className="modal-overlay" onClick={() => setShowDeposit(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowDeposit(false)}>✕</button>
            <h3>💰 Deposit Funds</h3>
            {error && <div className="error-message">{error}</div>}
            <form onSubmit={handleDeposit}>
              <input
                type="number"
                placeholder="Amount (ETB)"
                value={depositData.amount}
                onChange={(e) => setDepositData({ ...depositData, amount: e.target.value })}
                required
                min="1"
              />
              <select
                value={depositData.payment_method_id}
                onChange={(e) => {
                  setDepositData({ ...depositData, payment_method_id: e.target.value, payment_account_id: '' });
                  if (e.target.value) {
                    fetchPaymentAccounts(parseInt(e.target.value));
                  }
                }}
                required
              >
                <option value="">Select Payment Method</option>
                {paymentMethods.map((method: any) => (
                  <option key={method.id} value={method.id}>{method.name}</option>
                ))}
              </select>
              <select
                value={depositData.payment_account_id}
                onChange={(e) => setDepositData({ ...depositData, payment_account_id: e.target.value })}
                required
                disabled={!depositData.payment_method_id}
              >
                <option value="">Select Account</option>
                {paymentAccounts.map((account: any) => (
                  <option key={account.id} value={account.id}>
                    {account.account_name} - {account.account_number}
                  </option>
                ))}
              </select>
              <input
                type="text"
                placeholder="Transaction Reference Number"
                value={depositData.transaction_number}
                onChange={(e) => setDepositData({ ...depositData, transaction_number: e.target.value })}
                required
              />
              <button type="submit" disabled={loading}>
                {loading ? 'Submitting...' : 'Submit Deposit Request'}
              </button>
            </form>
          </div>
        </div>
      )}

      {showWithdraw && (
        <div className="modal-overlay" onClick={() => setShowWithdraw(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowWithdraw(false)}>✕</button>
            <h3>💳 Withdraw Funds</h3>
            {error && <div className="error-message">{error}</div>}
            <form onSubmit={handleWithdraw}>
              <input
                type="number"
                placeholder="Amount (ETB)"
                value={withdrawData.amount}
                onChange={(e) => setWithdrawData({ ...withdrawData, amount: e.target.value })}
                required
                min="1"
                max={balance}
              />
              <select
                value={withdrawData.payment_method_id}
                onChange={(e) => setWithdrawData({ ...withdrawData, payment_method_id: e.target.value })}
                required
              >
                <option value="">Select Withdrawal Method</option>
                {paymentMethods.map((method: any) => (
                  <option key={method.id} value={method.id}>{method.name}</option>
                ))}
              </select>
              <input
                type="text"
                placeholder="Your Account Number"
                value={withdrawData.account_number}
                onChange={(e) => setWithdrawData({ ...withdrawData, account_number: e.target.value })}
                required
              />
              <div className="withdraw-info">
                <span>Fee: 2%</span>
                <span>You will receive: {(parseFloat(withdrawData.amount || '0') * 0.98).toFixed(2)} ETB</span>
              </div>
              <button type="submit" disabled={loading}>
                {loading ? 'Submitting...' : 'Submit Withdrawal Request'}
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default WalletPage;