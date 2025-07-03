import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
    Layout,
    Typography,
    Card,
    Button,
    List,
    Tag,
    Divider,
    Badge,
    message,
    Spin,
    Space,
    Statistic,
    Row,
    Col,
    Alert,
    Modal,
    Descriptions,
    Progress,
    Empty,
    theme,
    Drawer,
    Tooltip
} from 'antd';
import './KitchenDisplayPage.css';
import {
    ClockCircleOutlined,
    CheckCircleOutlined,
    FireOutlined,
    CoffeeOutlined,
    ReloadOutlined,
    ArrowLeftOutlined,
    ShopOutlined,
    BarChartOutlined,
    DashboardOutlined,
    ExclamationCircleOutlined,
    MessageOutlined
} from '@ant-design/icons';
import KitchenChatComponent from '../components/KitchenChatComponent';

const { Header, Content } = Layout;
const { Title, Text } = Typography;
const { Countdown } = Statistic;

// Define base URL for the application using environment variables if available
const BASE_URL = 'http://172.16.0.24:9999';
const API_URL = window.env?.API_URL || 'http://localhost:9999/api';

const KitchenDisplayPage = () => {
    const [orders, setOrders] = useState([]);
    const [statistics, setStatistics] = useState(null);
    const [loading, setLoading] = useState(true);
    const [statsLoading, setStatsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [messageApi, contextHolder] = message.useMessage();
    const [orderDetailVisible, setOrderDetailVisible] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [showChat, setShowChat] = useState(false);
    const [newMessages, setNewMessages] = useState(0);

    // Filter orders by status
    const receivedOrders = orders.filter(order => order.status === 'received');
    const processingOrders = orders.filter(order => order.status === 'processing');
    const readyOrders = orders.filter(order => order.status === 'ready');
    const completedOrders = orders.filter(order => order.status === 'completed').slice(0, 5); // Only show latest 5 completed orders

    // Event source for server-sent events
    let eventSource = null;

    // Fetch orders and set up SSE listener
    useEffect(() => {
        const fetchOrders = async () => {
            try {
                console.log('Fetching orders from:', `${API_URL}/kitchen/orders`);
                const response = await axios.get(`${API_URL}/kitchen/orders`);

                if (response.data.success) {
                    console.log('Orders fetched successfully:', response.data.orders);
                    setOrders(response.data.orders);
                } else {
                    throw new Error('Failed to fetch orders');
                }
                setLoading(false);
            } catch (error) {
                console.error('Error fetching orders:', error);
                setError('Failed to load orders. Please try again later.');
                setLoading(false);

                // Instead of creating fake orders, just set an empty array
                setOrders([]);

                // Show error message
                messageApi.error('Failed to load orders. Please check your connection and try again.');
            }
        };

        // Fetch statistics
        const fetchStatistics = async () => {
            try {
                console.log('Fetching statistics from:', `${API_URL}/kitchen/statistics`);
                const response = await axios.get(`${API_URL}/kitchen/statistics`);

                if (response.data.success) {
                    console.log('Statistics fetched successfully:', response.data.statistics);
                    setStatistics(response.data.statistics);
                } else {
                    throw new Error('Failed to fetch statistics');
                }
                setStatsLoading(false);
            } catch (error) {
                console.error('Error fetching statistics:', error);
                setStatsLoading(false);
                // Set default empty statistics instead of fake data
                setStatistics({
                    received_count: 0,
                    processing_count: 0,
                    ready_count: 0,
                    completed_count: 0,
                    avg_preparation_time: 0,
                    total_orders_today: 0
                });

                // Don't show error message for statistics as it's not critical
            }
        };

        fetchOrders();
        fetchStatistics();

        // Set up event source for real-time updates
        try {
            console.log('Setting up EventSource connection to:', `${API_URL}/kitchen/events`);
            eventSource = new EventSource(`${API_URL}/kitchen/events`);

        eventSource.onmessage = (event) => {
                console.log('Event received:', event.data);
            const data = JSON.parse(event.data);

            if (data.type === 'new_order') {
                    messageApi.info(`New order received: ${data.order.order_id}`);
                setOrders(prevOrders => [data.order, ...prevOrders]);
                    fetchStatistics(); // Refresh statistics
            } else if (data.type === 'status_update') {
                    messageApi.success(`Order ${data.order_id} status updated to ${data.status}`);
                setOrders(prevOrders =>
                    prevOrders.map(order =>
                        order.order_id === data.order_id
                            ? { ...order, status: data.status }
                            : order
                    )
                );
                    fetchStatistics(); // Refresh statistics
            }
        };

        eventSource.onerror = (error) => {
            console.error('EventSource error:', error);
                messageApi.error('Lost connection to server. Please refresh the page.');
            eventSource.close();
        };
        } catch (error) {
            console.error('Failed to set up EventSource:', error);
        }

        // Clean up on component unmount
        return () => {
            if (eventSource) {
                console.log('Closing EventSource connection');
                eventSource.close();
            }
        };
    }, [messageApi]);

    // Update order status
    const updateOrderStatus = async (orderId, newStatus) => {
        try {
            console.log(`Updating order ${orderId} status to ${newStatus}`);

            // Check if this is a valid order ID before proceeding
            if (!orderId || !orderId.startsWith('ORD-')) {
                messageApi.error(`Invalid order ID format: ${orderId}`);
                return;
            }

            const response = await axios.post(`${API_URL}/kitchen/orders/${orderId}/status`, {
                status: newStatus
            });

            if (response.data.success) {
                messageApi.success(`Order ${orderId} status updated to ${newStatus}`);

                // Update local state with the new status
                setOrders(prevOrders =>
                    prevOrders.map(order =>
                        order.order_id === orderId
                            ? { ...order, status: newStatus }
                            : order
                    )
                );

                // Update statistics
                const fetchStatistics = async () => {
                    try {
                        const statsResponse = await axios.get(`${API_URL}/kitchen/statistics`);
                        if (statsResponse.data.success) {
                            setStatistics(statsResponse.data.statistics);
                        }
                    } catch (error) {
                        console.error('Error refreshing statistics:', error);
                        // Don't show error message for statistics refresh failure
                    }
                };

                fetchStatistics();

                // Close modal if open
                if (selectedOrder && selectedOrder.order_id === orderId) {
                    setSelectedOrder({...selectedOrder, status: newStatus});
                }
            } else {
                throw new Error(response.data.message || 'Failed to update status');
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            messageApi.error(`Failed to update order status: ${error.message || 'Unknown error'}`);

            // Refresh data to ensure UI is in sync with backend
            setTimeout(() => {
                refreshData();
            }, 2000);
        }
    };

    // Refresh all data
    const refreshData = async () => {
        setLoading(true);
        setStatsLoading(true);

        try {
            const [ordersResponse, statsResponse] = await Promise.all([
                axios.get(`${API_URL}/kitchen/orders`),
                axios.get(`${API_URL}/kitchen/statistics`)
            ]);

            if (ordersResponse.data.success) {
                setOrders(ordersResponse.data.orders);
            }

            if (statsResponse.data.success) {
                setStatistics(statsResponse.data.statistics);
            }

            messageApi.success('Data refreshed successfully');
        } catch (error) {
            console.error('Error refreshing data:', error);
            messageApi.error('Failed to refresh data');
        }

        setLoading(false);
        setStatsLoading(false);
    };

    // Show order details modal
    const showOrderDetails = (order) => {
        setSelectedOrder(order);
        setOrderDetailVisible(true);
    };

    // Calculate time elapsed since order timestamp
    const formatTimeElapsed = (timestamp) => {
        const orderTime = new Date(timestamp);
        const now = new Date();
        const diffMs = now - orderTime;
        const diffMins = Math.round(diffMs / 60000);

        if (diffMins < 1) {
            return 'Just now';
        } else if (diffMins === 1) {
            return '1 min ago';
        } else if (diffMins < 60) {
            return `${diffMins} mins ago`;
        } else {
            const hours = Math.floor(diffMins / 60);
            const mins = diffMins % 60;
            return `${hours}h ${mins}m ago`;
        }
    };

    // Get status tag color
    const getStatusColor = (status) => {
        switch (status) {
            case 'received':
                return 'red';
            case 'processing':
                return 'orange';
            case 'ready':
                return 'green';
            case 'completed':
                return 'gray';
            default:
                return 'blue';
        }
    };

    // Get status icon
    const getStatusIcon = (status) => {
        switch (status) {
            case 'received':
                return <ClockCircleOutlined />;
            case 'processing':
                return <FireOutlined />;
            case 'ready':
                return <CoffeeOutlined />;
            case 'completed':
                return <CheckCircleOutlined />;
            default:
                return null;
        }
    };

    // Calculate urgency based on time elapsed
    const getUrgencyLevel = (timestamp) => {
        const orderTime = new Date(timestamp);
        const now = new Date();
        const diffMins = Math.round((now - orderTime) / 60000);

        if (diffMins < 5) return 'low';
        if (diffMins < 15) return 'medium';
        return 'high';
    };

    // Render order list for a specific status
    const renderOrdersList = (statusOrders, status) => {
        return (
            <List
                className="order-list"
                header={
                    <div className={`status-header status-header-${status}`}>
                        <Space align="center">
                            {getStatusIcon(status)}
                            <Text strong style={{ fontSize: '16px', color: 'white' }}>
                                {status === 'received' && 'New Orders'}
                                {status === 'processing' && 'In Progress'}
                                {status === 'ready' && 'Ready to Serve'}
                                {status === 'completed' && 'Recently Completed'}
                            </Text>
                        </Space>
                        <Badge count={statusOrders.length} style={{ backgroundColor: 'white', color: getStatusColor(status) }} />
                    </div>
                }
                bordered
                dataSource={statusOrders}
                renderItem={order => {
                    const urgencyLevel = getUrgencyLevel(order.timestamp);
                    const isUrgent = urgencyLevel === 'high';

                    return (
                        <List.Item>
                            <Card
                                hoverable
                                onClick={() => showOrderDetails(order)}
                                title={
                                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                        <span>Order #{order.order_id}</span>
                                        <Tag color={getStatusColor(order.status)} icon={getStatusIcon(order.status)}>
                                            {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                                        </Tag>
                                    </div>
                                }
                                className={`order-card order-card-${order.status} ${isUrgent && order.status === 'received' ? 'order-card-urgent' : ''}`}
                                extra={<Text>Table {order.table_id}</Text>}
                            >
                                <List
                                    size="small"
                                    dataSource={order.items.slice(0, 2)} // Show only the first 2 items
                                    renderItem={item => (
                                        <List.Item className="order-list-item-meta">
                                            <Space>
                                                <Badge count={item.quantity} style={{ backgroundColor: '#52c41a', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }} />
                                                <Text strong>{item.name}</Text>
                                            </Space>
                                            <Text type="secondary">${(item.price * item.quantity).toFixed(2)}</Text>
                                        </List.Item>
                                    )}
                                    footer={order.items.length > 2 ?
                                        <Text type="secondary">+ {order.items.length - 2} more items</Text> : null}
                                />

                                <Divider style={{ margin: '12px 0' }} />

                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    {order.status === 'processing' ? (
                                        <div>
                                            <Progress
                                                percent={Math.min(100, getUrgencyLevel(order.timestamp) === 'high' ? 90 : getUrgencyLevel(order.timestamp) === 'medium' ? 60 : 30)}
                                                size="small"
                                                strokeColor={{
                                                    '0%': '#52c41a',
                                                    '100%': '#ff4d4f',
                                                }}
                                                showInfo={false}
                                                style={{ width: 100, marginRight: 8 }}
                                            />
                                            <Text type="secondary">{formatTimeElapsed(order.timestamp)}</Text>
                                        </div>
                                    ) : (
                                        <Text type="secondary">{formatTimeElapsed(order.timestamp)}</Text>
                                    )}

                                    {order.status === 'received' && (
                                        <Button
                                            type="primary"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                updateOrderStatus(order.order_id, 'processing')
                                            }}
                                            icon={<FireOutlined />}
                                            className="action-button action-button-start"
                                        >
                                            Start Cooking
                                        </Button>
                        )}

                        {order.status === 'processing' && (
                                        <Button
                                            type="primary"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                updateOrderStatus(order.order_id, 'ready')
                                            }}
                                            icon={<CoffeeOutlined />}
                                            className="action-button action-button-ready"
                            >
                                Mark Ready
                                        </Button>
                        )}

                        {order.status === 'ready' && (
                                        <Button
                                            type="default"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                updateOrderStatus(order.order_id, 'completed')
                                            }}
                                            icon={<CheckCircleOutlined />}
                                            className="action-button"
                                        >
                                            Complete
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        </List.Item>
                    )
                }}
                locale={{
                    emptyText: <Empty
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                        description={<span>No {status} orders</span>}
                        className="empty-state"
                    />
                }}
            />
        );
    };

    // Render statistics cards
    const renderStatistics = () => {
        if (!statistics) return null;

        return (
            <Row gutter={[16, 16]} className="statistics-container">
                <Col xs={24} sm={12} md={8} lg={6} xl={6}>
                    <Card bordered={false} className="stats-card statistic-card">
                        <Statistic
                            title="New Orders"
                            value={statistics.received_count}
                            valueStyle={{ color: '#ff4d4f', fontSize: '28px' }}
                            prefix={<ClockCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={6} xl={6}>
                    <Card bordered={false} className="stats-card statistic-card">
                        <Statistic
                            title="In Progress"
                            value={statistics.processing_count}
                            valueStyle={{ color: '#faad14', fontSize: '28px' }}
                            prefix={<FireOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={6} xl={6}>
                    <Card bordered={false} className="stats-card statistic-card">
                        <Statistic
                            title="Ready to Serve"
                            value={statistics.ready_count}
                            valueStyle={{ color: '#52c41a', fontSize: '28px' }}
                            prefix={<CoffeeOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={6} xl={6}>
                    <Card bordered={false} className="stats-card statistic-card">
                        <Statistic
                            title="Avg. Prep Time"
                            value={statistics.avg_preparation_time}
                            suffix="min"
                            valueStyle={{ color: '#1890ff', fontSize: '28px' }}
                            prefix={<DashboardOutlined />}
                        />
                    </Card>
                </Col>
            </Row>
        );
    };

    // Order details modal
    const renderOrderDetailsModal = () => {
        if (!selectedOrder) return null;

        return (
            <Modal
                title={
                    <Space>
                        <Text strong>Order Details: {selectedOrder.order_id}</Text>
                        <Tag color={getStatusColor(selectedOrder.status)} icon={getStatusIcon(selectedOrder.status)}>
                            {selectedOrder.status.charAt(0).toUpperCase() + selectedOrder.status.slice(1)}
                        </Tag>
                    </Space>
                }
                open={orderDetailVisible}
                onCancel={() => setOrderDetailVisible(false)}
                footer={[
                    <Button key="close" onClick={() => setOrderDetailVisible(false)} className="action-button">
                        Close
                    </Button>,
                    selectedOrder.status === 'received' && (
                        <Button
                            key="start"
                            type="primary"
                            icon={<FireOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.order_id, 'processing')}
                            className="action-button action-button-start"
                        >
                            Start Cooking
                        </Button>
                    ),
                    selectedOrder.status === 'processing' && (
                        <Button
                            key="ready"
                            type="primary"
                            icon={<CoffeeOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.order_id, 'ready')}
                            className="action-button action-button-ready"
                        >
                            Mark Ready
                        </Button>
                    ),
                    selectedOrder.status === 'ready' && (
                        <Button
                            key="complete"
                            type="default"
                            icon={<CheckCircleOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.order_id, 'completed')}
                            className="action-button"
                            >
                                Complete Order
                        </Button>
                    )
                ].filter(Boolean)}
                width={700}
                className="order-detail-modal"
            >
                <Descriptions bordered column={1}>
                    <Descriptions.Item label="Table">Table {selectedOrder.table_id}</Descriptions.Item>
                    <Descriptions.Item label="Order Time">{new Date(selectedOrder.timestamp).toLocaleString()}</Descriptions.Item>
                    <Descriptions.Item label="Time Elapsed">{formatTimeElapsed(selectedOrder.timestamp)}</Descriptions.Item>
                    <Descriptions.Item label="Total Order Amount">${selectedOrder.total || selectedOrder.items.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2)}</Descriptions.Item>
                </Descriptions>

                <Divider>Order Items</Divider>

                <List
                    bordered
                    dataSource={selectedOrder.items}
                    renderItem={item => (
                        <List.Item>
                            <List.Item.Meta
                                className="order-list-item-meta"
                                avatar={<Badge count={item.quantity} style={{ backgroundColor: '#52c41a' }} />}
                                title={item.name}
                                description={item.special_instructions ? `Note: ${item.special_instructions}` : null}
                            />
                            <div>${(item.price * item.quantity).toFixed(2)}</div>
                        </List.Item>
                    )}
                />

                {selectedOrder.status === 'received' && (
                    <Alert
                        message="New Order"
                        description="This order is waiting to be processed"
                        type="warning"
                        showIcon
                        style={{ marginTop: '20px' }}
                    />
                )}

                {selectedOrder.status === 'processing' && (
                    <Alert
                        message="In Progress"
                        description="This order is currently being prepared in the kitchen"
                        type="info"
                        showIcon
                        style={{ marginTop: '20px' }}
                    />
                )}

                {selectedOrder.status === 'ready' && (
                    <Alert
                        message="Ready to Serve"
                        description="This order is ready for pickup by the waiter"
                        type="success"
                        showIcon
                        style={{ marginTop: '20px' }}
                    />
                )}
            </Modal>
        );
    };

    // Show loading state
    if (loading) {
        return (
            <div className="loading-container">
                <Spin size="large" className="loading-spinner" />
                <Text>Loading kitchen orders...</Text>
            </div>
        );
    }

    return (
        <Layout className="kitchen-layout" style={{ minHeight: '100vh' }}>
            {contextHolder}
            {renderOrderDetailsModal()}

            <Header className="kitchen-header">
                <div style={{ display: 'flex', alignItems: 'center' }}>
                    <ShopOutlined style={{ color: 'white', fontSize: '24px', marginRight: '10px' }} />
                    <Title level={3} style={{ color: 'white', margin: 0 }}>Kitchen Display System</Title>
                </div>
                <Space>
                    <Tooltip title="Customer Messages">
                        <Badge count={newMessages} offset={[-5, 5]}>
                            <Button
                                icon={<MessageOutlined />}
                                onClick={() => {
                                    setShowChat(true);
                                    setNewMessages(0);
                                }}
                                className="action-button"
                                style={{ backgroundColor: newMessages > 0 ? '#52c41a' : undefined, color: newMessages > 0 ? 'white' : undefined }}
                            >
                                Chat
                            </Button>
                        </Badge>
                    </Tooltip>
                    <Button
                        icon={<ReloadOutlined />}
                        onClick={refreshData}
                        loading={loading}
                        className="action-button"
                    >
                        Refresh
                    </Button>
                    <Button
                        type="primary"
                        icon={<ArrowLeftOutlined />}
                        href={`${BASE_URL}/react/tables`}
                        className="action-button"
                    >
                        Back to Tables
                    </Button>
                </Space>
            </Header>

            <Content className="kitchen-content">
                {error && (
                    <Alert
                        message="Error"
                        description={error}
                        type="error"
                        showIcon
                        style={{ marginBottom: '20px' }}
                    />
                )}

                <Card
                    title={
                        <Space>
                            <BarChartOutlined />
                            <span>Today's Kitchen Statistics</span>
                        </Space>
                    }
                    className="stats-card"
                    extra={<Text type="secondary">Orders Today: {statistics?.total_orders_today || 0}</Text>}
                    loading={statsLoading}
                >
                    {renderStatistics()}
                </Card>

                <Row gutter={[16, 16]}>
                    <Col xs={24} sm={24} md={12} lg={6}>
                        {renderOrdersList(receivedOrders, 'received')}
                    </Col>

                    <Col xs={24} sm={24} md={12} lg={6}>
                        {renderOrdersList(processingOrders, 'processing')}
                    </Col>

                    <Col xs={24} sm={24} md={12} lg={6}>
                        {renderOrdersList(readyOrders, 'ready')}
                    </Col>

                    <Col xs={24} sm={24} md={12} lg={6}>
                        {renderOrdersList(completedOrders, 'completed')}
                    </Col>
                </Row>
            </Content>

            <Drawer
                title="Customer Messages"
                placement="right"
                onClose={() => setShowChat(false)}
                open={showChat}
                width={500}
                bodyStyle={{ padding: 0 }}
            >
                <KitchenChatComponent onNewMessage={() => setNewMessages(prev => prev + 1)} />
            </Drawer>
        </Layout>
    );
};

export default KitchenDisplayPage;
