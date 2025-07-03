import React, { useState, useEffect, useRef } from 'react';
import { io } from 'socket.io-client';
import { Tabs, Badge, Input, Button, List, Avatar, Typography, Empty, notification, Tag, Divider } from 'antd';
import axios from 'axios';

const { TabPane } = Tabs;
const { Text } = Typography;

// Key for storing messages in localStorage
const STORAGE_KEY = 'kitchen_chat_messages';

// Generate a unique client ID to identify this browser session
const CLIENT_ID = `kitchen-${Math.random().toString(36).substr(2, 9)}`;

// Define API URL using environment variables if available
const API_URL = window.env?.API_URL || 'http://localhost:9999/api';

// Define WebSocket URL using environment variables if available
const WS_URL = window.env?.WS_URL || 'http://localhost:3001';

const KitchenChatComponent = ({ onNewMessage }) => {
  const [messages, setMessages] = useState(() => {
    // Load messages from localStorage on component mount
    const savedMessages = localStorage.getItem(STORAGE_KEY);
    return savedMessages ? JSON.parse(savedMessages) : {};
  });
  const [activeTable, setActiveTable] = useState(null);
  const [message, setMessage] = useState('');
  const [connected, setConnected] = useState(false);
  const [unreadMessages, setUnreadMessages] = useState({});
  const [orders, setOrders] = useState({});
  const [loading, setLoading] = useState(false);
  const socketRef = useRef(null);
  const messagesEndRef = useRef(null);
  const processedMessagesRef = useRef(new Set()); // Track processed messages

  // Fetch orders from Laravel API
  const fetchOrders = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/kitchen/orders`);

      if (response.data.success) {
        // Group orders by table ID
        const ordersByTable = {};
        response.data.orders.forEach(order => {
          if (!ordersByTable[order.table_id]) {
            ordersByTable[order.table_id] = [];
          }
          ordersByTable[order.table_id].push(order);
        });

        setOrders(ordersByTable);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
      notification.error({
        message: 'Failed to fetch orders',
        description: error.message,
      });
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
      console.log('Kitchen connected to chat server at', WS_URL);
      setConnected(true);

      // Join the kitchen staff room
      socketRef.current.emit('joinRoom', { isKitchenStaff: true, clientId: CLIENT_ID });
    });

    socketRef.current.on('disconnect', () => {
      console.log('Kitchen disconnected from chat server');
      setConnected(false);
    });

    socketRef.current.on('chatMessage', (data) => {
      const tableId = data.tableId;

      // Create a unique key for this message to prevent duplicates
      const messageKey = `${data.sender}-${data.message}-${data.timestamp}`;

      // Only add the message if we haven't processed it before
      if (!processedMessagesRef.current.has(messageKey)) {
        processedMessagesRef.current.add(messageKey);

        // Update messages for this table
        setMessages(prevMessages => {
          const tableMessages = prevMessages[tableId] || [];

          // Double check we don't already have this message
          if (!tableMessages.some(m =>
            m.sender === data.sender &&
            m.message === data.message &&
            m.timestamp === data.timestamp
          )) {
            const updatedMessages = {
              ...prevMessages,
              [tableId]: [...tableMessages, data]
            };

            // Save to localStorage
            localStorage.setItem(STORAGE_KEY, JSON.stringify(updatedMessages));

            return updatedMessages;
          }
          return prevMessages;
        });

        // If message is from a customer and not from the active table, increment unread count
        if (!data.isFromKitchen && activeTable !== tableId) {
          setUnreadMessages(prev => ({
            ...prev,
            [tableId]: (prev[tableId] || 0) + 1
          }));

          // Show notification for new message
          notification.info({
            message: `New message from Table #${tableId}`,
            description: `${data.sender}: ${data.message}`,
            placement: 'topRight',
          });

          // Call the onNewMessage callback if provided
          if (onNewMessage) {
            onNewMessage();
          }
        }
      }
    });

    // Listen for order updates
    socketRef.current.on('orderUpdate', (orderData) => {
      if (orderData && orderData.tableId) {
        // Update orders for this table
        setOrders(prevOrders => {
          const tableOrders = prevOrders[orderData.tableId] || [];

          // Check if this order already exists
          const existingOrderIndex = tableOrders.findIndex(o => o.id === orderData.id);

          if (existingOrderIndex >= 0) {
            // Update existing order
            const updatedTableOrders = [...tableOrders];
            updatedTableOrders[existingOrderIndex] = orderData;

            return {
              ...prevOrders,
              [orderData.tableId]: updatedTableOrders
            };
          } else {
            // Add new order
            return {
              ...prevOrders,
              [orderData.tableId]: [...tableOrders, orderData]
            };
          }
        });

        // Show notification for new order
        notification.success({
          message: `New order for Table #${orderData.tableId}`,
          description: `Order ${orderData.id} has been received`,
          placement: 'topRight',
        });
      }
    });

    // Clean up on unmount
    return () => {
      if (socketRef.current) {
        socketRef.current.emit('leaveRoom', { isKitchenStaff: true, clientId: CLIENT_ID });
        socketRef.current.disconnect();
      }
      processedMessagesRef.current.clear();
    };
  }, [activeTable, onNewMessage]);

  // Scroll to bottom when messages change
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, activeTable]);

  const sendMessage = (e) => {
    e.preventDefault();
    if (!message.trim() || !connected || !activeTable) return;

    const messageData = {
      tableId: activeTable,
      message: message.trim(),
      sender: 'Kitchen Staff',
      isFromKitchen: true,
      timestamp: new Date().toISOString(),
      clientId: CLIENT_ID // Add client ID to message data
    };

    // Create a unique key for this message
    const messageKey = `${messageData.sender}-${messageData.message}-${messageData.timestamp}`;
    processedMessagesRef.current.add(messageKey);

    socketRef.current.emit('sendMessage', messageData);
    setMessage('');
  };

  const handleTabChange = (tableId) => {
    setActiveTable(tableId);
    // Clear unread count for this table
    setUnreadMessages(prev => ({
      ...prev,
      [tableId]: 0
    }));
  };

  // Clear chat history for a specific table
  const clearChatHistory = (tableId) => {
    setMessages(prevMessages => {
      const updatedMessages = { ...prevMessages };
      delete updatedMessages[tableId];

      // Save to localStorage
      localStorage.setItem(STORAGE_KEY, JSON.stringify(updatedMessages));

      return updatedMessages;
    });

    notification.success({
      message: `Chat history cleared`,
      description: `Chat history for Table #${tableId} has been cleared.`,
      placement: 'topRight',
    });
  };

  // Refresh orders from API
  const refreshOrders = () => {
    fetchOrders();
    notification.info({
      message: 'Refreshing orders',
      description: 'Fetching latest orders from the server',
      placement: 'topRight',
    });
  };

  // Get all unique table IDs from messages and orders
  const getUniqueTableIds = () => {
    const messageTableIds = Object.keys(messages);
    const orderTableIds = Object.keys(orders);

    // Combine and deduplicate
    const allTableIds = [...new Set([...messageTableIds, ...orderTableIds])];

    // Sort numerically
    return allTableIds.sort((a, b) => Number(a) - Number(b));
  };

  const tableIds = getUniqueTableIds();

  // If no active table is selected but we have tables with messages, select the first one
  useEffect(() => {
    if (!activeTable && tableIds.length > 0) {
      setActiveTable(tableIds[0]);
    }
  }, [tableIds, activeTable]);

  // Render order summary for a table
  const renderOrderSummary = (tableId) => {
    const tableOrders = orders[tableId] || [];

    if (tableOrders.length === 0) {
      return null;
    }

    return (
      <div className="order-summary">
        <Divider orientation="left">Active Orders</Divider>
        <List
          size="small"
          dataSource={tableOrders}
          renderItem={order => (
            <List.Item>
              <List.Item.Meta
                title={`Order #${order.order_id}`}
                description={
                  <>
                    <Tag color={
                      order.status === 'received' ? 'blue' :
                      order.status === 'processing' ? 'orange' :
                      order.status === 'ready' ? 'green' :
                      'default'
                    }>
                      {order.status.toUpperCase()}
                    </Tag>
                    <Text type="secondary">
                      {order.items?.length || 0} items - $
                      {order.total ||
                        (order.items?.reduce((sum, item) =>
                          sum + (item.price * item.quantity), 0) || 0).toFixed(2)
                      }
                    </Text>
                  </>
                }
              />
            </List.Item>
          )}
        />
      </div>
    );
  };

  return (
    <div className="kitchen-chat-container">
      <div className="chat-header">
        <h3>Kitchen Chat</h3>
        <div className="header-actions">
          <Button
            type="primary"
            size="small"
            onClick={refreshOrders}
            loading={loading}
          >
            Refresh Orders
          </Button>
          <span className={`status ${connected ? 'online' : 'offline'}`}>
            {connected ? 'Connected' : 'Disconnected'}
          </span>
        </div>
      </div>

      {tableIds.length === 0 ? (
        <Empty description="No active chats or orders" />
      ) : (
        <Tabs
          activeKey={activeTable || tableIds[0]}
          onChange={handleTabChange}
          type="card"
          className="chat-tabs"
        >
          {tableIds.map(tableId => (
            <TabPane
              tab={
                <span>
                  Table #{tableId}
                  {unreadMessages[tableId] > 0 && (
                    <Badge count={unreadMessages[tableId]} style={{ marginLeft: 8 }} />
                  )}
                </span>
              }
              key={tableId}
            >
              <div className="chat-header-actions">
                <Button
                  type="text"
                  danger
                  size="small"
                  onClick={() => clearChatHistory(tableId)}
                >
                  Clear History
                </Button>
              </div>

              {renderOrderSummary(tableId)}

              <div className="chat-messages">
                {(!messages[tableId] || messages[tableId]?.length === 0) ? (
                  <div className="empty-messages">No messages yet</div>
                ) : (
                  <List
                    itemLayout="horizontal"
                    dataSource={messages[tableId] || []}
                    renderItem={(msg) => (
                      <List.Item
                        className={`message ${msg.isFromKitchen ? 'kitchen-message' : 'customer-message'}`}
                      >
                        <List.Item.Meta
                          avatar={
                            <Avatar
                              style={{
                                backgroundColor: msg.isFromKitchen ? '#52c41a' : '#1890ff'
                              }}
                            >
                              {msg.isFromKitchen ? 'K' : 'C'}
                            </Avatar>
                          }
                          title={
                            <div className="message-header">
                              <div>
                                <Text strong>{msg.sender}</Text>
                                {msg.isFromKitchen ? (
                                  <Tag color="green" style={{ marginLeft: 8 }}>Kitchen</Tag>
                                ) : (
                                  <Tag color="blue" style={{ marginLeft: 8 }}>Customer</Tag>
                                )}
                              </div>
                              <Text type="secondary">
                                {new Date(msg.timestamp).toLocaleTimeString()}
                              </Text>
                            </div>
                          }
                          description={
                            <div className="message-content">
                              {msg.message}
                            </div>
                          }
                        />
                      </List.Item>
                    )}
                  />
                )}
                <div ref={messagesEndRef} />
              </div>

              <Divider style={{ margin: '10px 0' }} />

              <div className="chat-input">
                <Input
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  onPressEnter={sendMessage}
                  placeholder="Type your reply..."
                  disabled={!connected}
                  size="large"
                  prefix={<Avatar size="small" style={{ backgroundColor: '#52c41a' }}>K</Avatar>}
                />
                <Button
                  type="primary"
                  onClick={sendMessage}
                  disabled={!connected || !message.trim()}
                  size="large"
                >
                  Send
                </Button>
              </div>
            </TabPane>
          ))}
        </Tabs>
      )}

      <style jsx>{`
        .kitchen-chat-container {
          display: flex;
          flex-direction: column;
          height: 600px;
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

        .header-actions {
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .chat-header-actions {
          display: flex;
          justify-content: flex-end;
          padding: 5px 10px;
          background-color: #fafafa;
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

        .chat-tabs {
          flex: 1;
          display: flex;
          flex-direction: column;
        }

        .chat-messages {
          flex: 1;
          overflow-y: auto;
          padding: 15px;
          height: 350px;
        }

        .empty-messages {
          text-align: center;
          color: #999;
          margin: auto;
        }

        .message {
          margin-bottom: 10px;
          padding: 10px;
          border-radius: 8px;
        }

        .kitchen-message {
          background-color: #f6ffed;
          border-left: 4px solid #52c41a;
          align-self: flex-start;
          margin-right: auto;
        }

        .customer-message {
          background-color: #e6f7ff;
          border-left: 4px solid #1890ff;
          align-self: flex-end;
          margin-left: auto;
        }

        .message-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .message-content {
          margin-top: 5px;
          word-break: break-word;
        }

        .chat-input {
          display: flex;
          padding: 10px;
          border-top: 1px solid #ddd;
        }

        .chat-input input {
          flex: 1;
          margin-right: 10px;
        }

        .order-summary {
          margin: 10px 0;
          padding: 10px;
          background-color: #f9f9f9;
          border-radius: 4px;
        }
      `}</style>
    </div>
  );
};

export default KitchenChatComponent;
