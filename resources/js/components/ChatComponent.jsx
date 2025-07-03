import React, { useState, useEffect, useRef } from 'react';
import { io } from 'socket.io-client';
import axios from 'axios';

// Function to get storage key for a specific table
const getStorageKey = (tableId) => `table_chat_messages_${tableId}`;

// Generate a unique client ID to identify this browser session
const CLIENT_ID = `client-${Math.random().toString(36).substr(2, 9)}`;

// Define API URL using environment variables if available
const API_URL = window.env?.API_URL || 'http://localhost:9999/api';

// Define WebSocket URL using environment variables if available
const WS_URL = window.env?.WS_URL || 'http://localhost:3001';

const ChatComponent = ({ tableId, userName }) => {
  const [messages, setMessages] = useState(() => {
    // Load messages from localStorage on component mount
    const savedMessages = localStorage.getItem(getStorageKey(tableId));
    return savedMessages ? JSON.parse(savedMessages) : [];
  });
  const [message, setMessage] = useState('');
  const [connected, setConnected] = useState(false);
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const socketRef = useRef(null);
  const messagesEndRef = useRef(null);
  const sentMessagesRef = useRef(new Set()); // Track sent message timestamps to prevent duplicates
  const processedMessagesRef = useRef(new Set()); // Track all processed messages

  // Fetch orders for this table
  const fetchOrders = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/tables/${tableId}/orders`);

      if (response.data.success) {
        setOrders(response.data.orders);
      }
    } catch (error) {
      console.error(`Error fetching orders for table ${tableId}:`, error);
    } finally {
      setLoading(false);
    }
  };

  // Connect to the chat service
  useEffect(() => {
    // Fetch orders when component mounts
    fetchOrders();

    // Connect to the WebSocket server
    socketRef.current = io(WS_URL, {
      transports: ['websocket', 'polling'],
      query: { clientId: CLIENT_ID } // Send client ID with connection
    });

    // Set up event listeners
    socketRef.current.on('connect', () => {
      console.log('Connected to chat server at', WS_URL);
      setConnected(true);

      // Join the table's room
      socketRef.current.emit('joinRoom', { tableId, clientId: CLIENT_ID });

      // Request orders for this table
      socketRef.current.emit('getOrdersForTable', { tableId });
    });

    socketRef.current.on('disconnect', () => {
      console.log('Disconnected from chat server');
      setConnected(false);
    });

    socketRef.current.on('chatMessage', (data) => {
      // Only show messages for this table
      if (data.tableId == tableId) {
        // Create a unique key for this message to prevent duplicates
        const messageKey = `${data.sender}-${data.message}-${data.timestamp}`;

        // Only add the message if we haven't processed it before
        if (!processedMessagesRef.current.has(messageKey)) {
          processedMessagesRef.current.add(messageKey);

          // Add message to state
          setMessages(prevMessages => {
            // Double check we don't already have this message
            if (!prevMessages.some(m =>
              m.sender === data.sender &&
              m.message === data.message &&
              m.timestamp === data.timestamp
            )) {
              const updatedMessages = [...prevMessages, data];

              // Save to localStorage
              localStorage.setItem(getStorageKey(tableId), JSON.stringify(updatedMessages));

              return updatedMessages;
            }
            return prevMessages;
          });
        }
      }
    });

    // Listen for order updates
    socketRef.current.on('orderUpdate', (orderData) => {
      if (orderData && orderData.tableId == tableId) {
        setOrders(prevOrders => {
          // Check if this order already exists
          const existingOrderIndex = prevOrders.findIndex(o => o.id === orderData.id);

          if (existingOrderIndex >= 0) {
            // Update existing order
            const updatedOrders = [...prevOrders];
            updatedOrders[existingOrderIndex] = orderData;
            return updatedOrders;
          } else {
            // Add new order
            return [...prevOrders, orderData];
          }
        });
      }
    });

    // Listen for table orders response
    socketRef.current.on('tableOrders', (data) => {
      if (data.tableId == tableId && Array.isArray(data.orders)) {
        setOrders(data.orders);
      }
    });

    // Clean up on unmount
    return () => {
      if (socketRef.current) {
        socketRef.current.emit('leaveRoom', { tableId, clientId: CLIENT_ID });
        socketRef.current.disconnect();
      }
      sentMessagesRef.current.clear();
      processedMessagesRef.current.clear();
    };
  }, [tableId, userName]);

  // Scroll to bottom when messages change
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const sendMessage = (e) => {
    e.preventDefault();
    if (!message.trim() || !connected) return;

    const timestamp = new Date().toISOString();
    const messageData = {
      tableId,
      message: message.trim(),
      sender: userName || 'Guest',
      isFromKitchen: false,
      timestamp,
      clientId: CLIENT_ID // Add client ID to message data
    };

    // Create a unique key for this message
    const messageKey = `${messageData.sender}-${messageData.message}-${messageData.timestamp}`;

    // Add to sent messages set to prevent duplicate
    sentMessagesRef.current.add(messageKey);
    processedMessagesRef.current.add(messageKey);

    // Add message to local state immediately for better UX
    setMessages(prevMessages => {
      const updatedMessages = [...prevMessages, messageData];

      // Save to localStorage
      localStorage.setItem(getStorageKey(tableId), JSON.stringify(updatedMessages));

      return updatedMessages;
    });

    // Send to server
    socketRef.current.emit('sendMessage', messageData);
    setMessage('');
  };

  // Clear chat history
  const clearChatHistory = () => {
    setMessages([]);
    localStorage.removeItem(getStorageKey(tableId));
  };

  // Refresh orders
  const refreshOrders = () => {
    fetchOrders();
    socketRef.current.emit('getOrdersForTable', { tableId });
  };

  // Remove duplicate messages
  const uniqueMessages = messages.reduce((acc, current) => {
    const messageKey = `${current.sender}-${current.message}-${current.timestamp}`;
    const exists = acc.find(
      item => `${item.sender}-${item.message}-${item.timestamp}` === messageKey
    );
    if (!exists) {
      return [...acc, current];
    }
    return acc;
  }, []);

  // Render order summary
  const renderOrderSummary = () => {
    if (orders.length === 0) {
      return null;
    }

    return (
      <div className="order-summary">
        <h4>Your Orders</h4>
        {orders.map((order, index) => (
          <div key={order.id || index} className="order-item">
            <div className="order-header">
              <span className="order-id">Order #{order.order_id || order.id}</span>
              <span className={`order-status ${order.status}`}>
                {order.status?.toUpperCase()}
              </span>
            </div>
            <div className="order-details">
              <ul className="order-items-list">
                {order.items?.map((item, idx) => (
                  <li key={idx}>
                    <span className="item-quantity">{item.quantity}x</span>
                    <span className="item-name">{item.name}</span>
                    <span className="item-price">${(item.price * item.quantity).toFixed(2)}</span>
                  </li>
                ))}
              </ul>
              <div className="order-total">
                Total: ${order.total ||
                  (order.items?.reduce((sum, item) =>
                    sum + (item.price * item.quantity), 0) || 0).toFixed(2)
                }
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="chat-container">
      <div className="chat-header">
        <h3>Table #{tableId} Chat</h3>
        <div className="chat-actions">
          <button
            onClick={refreshOrders}
            className="refresh-button"
            type="button"
            disabled={loading}
          >
            {loading ? 'Loading...' : 'Refresh Orders'}
          </button>
          <button
            onClick={clearChatHistory}
            className="clear-button"
            type="button"
          >
            Clear History
          </button>
          <span className={`status ${connected ? 'online' : 'offline'}`}>
            {connected ? 'Connected' : 'Disconnected'}
          </span>
        </div>
      </div>

      {renderOrderSummary()}

      <div className="chat-messages">
        {uniqueMessages.length === 0 ? (
          <div className="empty-messages">No messages yet</div>
        ) : (
          uniqueMessages.map((msg, index) => (
            <div
              key={`${msg.sender}-${msg.timestamp}-${index}`}
              className={`message ${msg.isFromKitchen ? 'kitchen-message' : 'customer-message'}`}
            >
              {msg.isFromKitchen && (
                <div className="message-badge kitchen-badge">
                  Kitchen
                </div>
              )}
              <div className="message-content-wrapper">
                <div className="message-header">
                  <span className="sender">{msg.sender}</span>
                  <span className="timestamp">
                    {new Date(msg.timestamp).toLocaleTimeString()}
                  </span>
                </div>
                <div className="message-content">{msg.message}</div>
              </div>
            </div>
          ))
        )}
        <div ref={messagesEndRef} />
      </div>

      <form className="chat-input" onSubmit={sendMessage}>
        <input
          type="text"
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          placeholder="Type your message..."
          disabled={!connected}
        />
        <button type="submit" disabled={!connected || !message.trim()}>
          Send
        </button>
      </form>

      <style jsx>{`
        .chat-container {
          display: flex;
          flex-direction: column;
          height: 400px;
          border: 1px solid #ddd;
          border-radius: 8px;
          overflow: hidden;
        }

        .chat-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 10px 15px;
          background-color: #f5f5f5;
          border-bottom: 1px solid #ddd;
        }

        .chat-header h3 {
          margin: 0;
        }

        .chat-actions {
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .clear-button, .refresh-button {
          background: none;
          border: none;
          cursor: pointer;
          font-size: 12px;
          padding: 2px 8px;
          border-radius: 4px;
        }

        .clear-button {
          color: #f5222d;
        }

        .clear-button:hover {
          background-color: #fff1f0;
        }

        .refresh-button {
          color: #1890ff;
        }

        .refresh-button:hover {
          background-color: #e6f7ff;
        }

        .refresh-button:disabled {
          color: #ccc;
          cursor: not-allowed;
        }

        .status {
          padding: 5px 10px;
          border-radius: 20px;
          font-size: 12px;
        }

        .online {
          background-color: #4caf50;
          color: white;
        }

        .offline {
          background-color: #f44336;
          color: white;
        }

        .chat-messages {
          flex: 1;
          overflow-y: auto;
          padding: 15px;
          display: flex;
          flex-direction: column;
        }

        .empty-messages {
          text-align: center;
          color: #999;
          margin: auto;
        }

        .message {
          max-width: 80%;
          margin-bottom: 15px;
          display: flex;
          flex-direction: row;
          align-items: flex-start;
        }

        .message-badge {
          font-size: 10px;
          padding: 3px 6px;
          border-radius: 10px;
          margin-right: 8px;
          margin-top: 8px;
          font-weight: bold;
          white-space: nowrap;
        }

        .kitchen-badge {
          background-color: #52c41a;
          color: white;
        }

        .message-content-wrapper {
          padding: 10px;
          border-radius: 8px;
          flex: 1;
        }

        .kitchen-message {
          align-self: flex-start;
        }

        .kitchen-message .message-content-wrapper {
          background-color: #f6ffed;
          border-left: 4px solid #52c41a;
        }

        .customer-message {
          align-self: flex-end;
        }

        .customer-message .message-content-wrapper {
          background-color: #e6f7ff;
          border-right: 4px solid #1890ff;
        }

        .message-header {
          display: flex;
          justify-content: space-between;
          margin-bottom: 5px;
          font-size: 12px;
        }

        .sender {
          font-weight: bold;
        }

        .timestamp {
          color: #999;
        }

        .message-content {
          word-break: break-word;
        }

        .chat-input {
          display: flex;
          padding: 10px;
          border-top: 1px solid #ddd;
        }

        .chat-input input {
          flex: 1;
          padding: 8px;
          border: 1px solid #ddd;
          border-radius: 4px;
          margin-right: 10px;
        }

        .chat-input button {
          padding: 8px 15px;
          background-color: #1976d2;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
        }

        .chat-input button:disabled {
          background-color: #cccccc;
          cursor: not-allowed;
        }

        .order-summary {
          padding: 15px;
          background-color: #f9f9f9;
          border-bottom: 1px solid #ddd;
        }

        .order-summary h4 {
          margin-top: 0;
          margin-bottom: 10px;
          color: #333;
        }

        .order-item {
          border: 1px solid #eee;
          border-radius: 4px;
          margin-bottom: 10px;
          background-color: white;
        }

        .order-header {
          display: flex;
          justify-content: space-between;
          padding: 8px 12px;
          background-color: #fafafa;
          border-bottom: 1px solid #eee;
        }

        .order-id {
          font-weight: bold;
        }

        .order-status {
          padding: 2px 8px;
          border-radius: 10px;
          font-size: 11px;
          font-weight: bold;
        }

        .order-status.received {
          background-color: #e6f7ff;
          color: #1890ff;
        }

        .order-status.processing {
          background-color: #fff7e6;
          color: #fa8c16;
        }

        .order-status.ready {
          background-color: #f6ffed;
          color: #52c41a;
        }

        .order-status.completed {
          background-color: #f9f9f9;
          color: #999;
        }

        .order-details {
          padding: 10px 12px;
        }

        .order-items-list {
          list-style-type: none;
          padding: 0;
          margin: 0 0 10px 0;
        }

        .order-items-list li {
          display: flex;
          margin-bottom: 5px;
          font-size: 13px;
        }

        .item-quantity {
          width: 30px;
          font-weight: bold;
        }

        .item-name {
          flex: 1;
        }

        .item-price {
          text-align: right;
          min-width: 60px;
        }

        .order-total {
          text-align: right;
          font-weight: bold;
          border-top: 1px solid #eee;
          padding-top: 8px;
          margin-top: 8px;
        }
      `}</style>
    </div>
  );
};

export default ChatComponent;
