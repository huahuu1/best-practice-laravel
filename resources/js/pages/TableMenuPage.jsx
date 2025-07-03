import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import axios from 'axios';
import {
    Layout,
    Typography,
    Card,
    Button,
    Spin,
    Alert,
    InputNumber,
    Badge,
    Drawer,
    List,
    Divider,
    Affix,
    Menu,
    Space,
    Empty,
    message,
    Modal
} from 'antd';
import {
    ShoppingCartOutlined,
    MinusOutlined,
    PlusOutlined,
    DeleteOutlined,
    MessageOutlined
} from '@ant-design/icons';
import ChatComponent from '../components/ChatComponent';

const { Header, Content, Footer } = Layout;
const { Title, Text, Paragraph } = Typography;

// Define base URL for the application using environment variables if available
const BASE_URL = 'http://172.16.0.24:9999';
const API_URL = 'http://172.16.0.24:9999/api';

const TableMenuPage = () => {
    const { tableId } = useParams();
    const [tableName, setTableName] = useState('');
    const [menuItems, setMenuItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [cart, setCart] = useState([]);
    const [showCart, setShowCart] = useState(false);
    const [categories, setCategories] = useState([]);
    const [messageApi, contextHolder] = message.useMessage();
    const [activeCategory, setActiveCategory] = useState('');
    const [userName, setUserName] = useState('Guest');
    const [showChat, setShowChat] = useState(false);

    // Group menu items by category for easier rendering
    const menuByCategory = menuItems.reduce((acc, item) => {
        const category = item.category || 'Uncategorized';
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category].push(item);
        return acc;
    }, {});

    // Fetch table and menu data
    useEffect(() => {
        const fetchTableData = async () => {
            try {
                console.log('Fetching table data for tableId:', tableId);

                // Get table info
                const tableResponse = await axios.get(`${API_URL}/tables/${tableId}`);
                console.log('Table response:', tableResponse.data);
                setTableName(tableResponse.data.name);

                // Get menu items
                const menuResponse = await axios.get(`${API_URL}/menu-items`);
                console.log('Menu response:', menuResponse.data);

                // Debug image paths
                menuResponse.data.forEach(item => {
                    console.log(`Menu item ${item.name} image path:`, item.image_path);
                });

                // Extract unique categories from menu items
                const uniqueCategories = [...new Set(menuResponse.data.map(item => item.category))];
                console.log('Unique categories:', uniqueCategories);
                setCategories(uniqueCategories);

                if (uniqueCategories.length > 0) {
                    setActiveCategory(uniqueCategories[0]);
                }

                setMenuItems(menuResponse.data);
                setLoading(false);
            } catch (error) {
                console.error('Error fetching data:', error);
                setError('Failed to load menu. Please try again later.');
                setLoading(false);
            }
        };

        fetchTableData();
    }, [tableId]);

    // Item quantity management
    const [quantities, setQuantities] = useState({});

    const increaseQuantity = (itemId) => {
        setQuantities({
            ...quantities,
            [itemId]: (quantities[itemId] || 0) + 1
        });
    };

    const decreaseQuantity = (itemId) => {
        if (quantities[itemId] > 0) {
            setQuantities({
                ...quantities,
                [itemId]: quantities[itemId] - 1
            });
        }
    };

    // Add item to cart
    const addToCart = (item) => {
        const quantity = quantities[item.id] || 0;

        if (quantity <= 0) return;

        // Check if item is already in cart
        const existingItem = cart.find(cartItem => cartItem.id === item.id);

        if (existingItem) {
            // Update quantity if already in cart
            setCart(cart.map(cartItem =>
                cartItem.id === item.id
                    ? { ...cartItem, quantity: cartItem.quantity + quantity }
                    : cartItem
            ));
        } else {
            // Add new item to cart
            setCart([...cart, { ...item, quantity }]);
        }

        // Reset quantity
        setQuantities({
            ...quantities,
            [item.id]: 0
        });

        messageApi.success(`Added ${quantity} ${item.name} to your order`);
    };

    // Remove item from cart
    const removeFromCart = (itemId) => {
        setCart(cart.filter(item => item.id !== itemId));
    };

    // Calculate cart total
    const cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);

    // Place order
    const placeOrder = async () => {
        if (cart.length === 0) {
            messageApi.warning('Your cart is empty!');
            return;
        }

        try {
            // Add debugging information
            console.log('Placing order for table:', tableId);
            console.log('Order items:', cart);
            console.log('Using URL:', `${BASE_URL}/tables/${tableId}/order`);

            const response = await axios.post(`${BASE_URL}/tables/${tableId}/order`, {
                items: cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    quantity: item.quantity,
                    price: item.price
                }))
            });

            console.log('Order response:', response.data);

            if (response.data.success) {
                messageApi.success(`Order placed successfully! Order ID: ${response.data.order_id}`);
                setCart([]);
                setShowCart(false);
            } else {
                messageApi.error(`Failed to place order: ${response.data.message}`);
            }
        } catch (error) {
            console.error('Error placing order:', error);
            console.error('Error details:', error.response?.data || 'No response data');
            messageApi.error('Failed to place order. Please try again.');
        }
    };

    const handleCategoryClick = (category) => {
        setActiveCategory(category);
        const element = document.getElementById(category.toLowerCase().replace(/\s+/g, '-'));
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    };

    // Show loading state
    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '50px' }}>
                <Spin size="large" />
                <div style={{ marginTop: '20px' }}>Loading menu...</div>
            </div>
        );
    }

    // Show error state
    if (error) {
        return (
            <div style={{ padding: '20px' }}>
                <Alert
                    message="Error"
                    description={error}
                    type="error"
                    showIcon
                />
            </div>
        );
    }

    return (
        <Layout className="menu-page">
            {contextHolder}
            <Header style={{
                background: '#fff',
                position: 'sticky',
                top: 0,
                zIndex: 1,
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                padding: '0 20px'
            }}>
                <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    height: '100%'
                }}>
                    <Title level={3} style={{ margin: 0 }}>{tableName}</Title>
                    <Text strong>Digital Menu</Text>
                </div>
            </Header>

            <Affix offsetTop={64}>
                <div style={{
                    background: '#fff',
                    padding: '10px 0',
                    borderBottom: '1px solid #f0f0f0'
                }}>
                    <Menu
                        mode="horizontal"
                        selectedKeys={[activeCategory]}
                        style={{
                            display: 'flex',
                            justifyContent: 'center'
                        }}
                    >
                        {categories.map(category => (
                            <Menu.Item
                                key={category}
                                onClick={() => handleCategoryClick(category)}
                            >
                                {category}
                            </Menu.Item>
                        ))}
                    </Menu>
                </div>
            </Affix>

            <Content style={{ padding: '20px', paddingBottom: '80px' }}>
                {categories.map(category => (
                    <div
                        id={category.toLowerCase().replace(/\s+/g, '-')}
                        key={category}
                        style={{ marginBottom: '30px' }}
                    >
                        <Title level={4} style={{ marginTop: '20px' }}>{category}</Title>
                        <Divider style={{ margin: '16px 0' }} />

                        <div style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
                            gap: '16px'
                        }}>
                            {(menuByCategory[category] || []).map(item => (
                                <Card
                                    key={item.id}
                                    hoverable
                                    style={{ height: '100%' }}
                                    cover={
                                        <div style={{
                                            height: '200px',
                                            overflow: 'hidden',
                                            position: 'relative',
                                            backgroundColor: '#f5f5f5',
                                            display: 'flex',
                                            justifyContent: 'center',
                                            alignItems: 'center'
                                        }}>
                                            <img
                                                alt={item.name}
                                                src={item.image_path ? `${BASE_URL}/${item.image_path}` : `${BASE_URL}/images/menu/default-food.jpg`}
                                                style={{
                                                    width: '100%',
                                                    height: '100%',
                                                    objectFit: 'cover',
                                                    objectPosition: 'center'
                                                }}
                                                onError={(e) => {
                                                    console.error(`Failed to load image for ${item.name}:`, e);
                                                    console.log('Attempted URL:', item.image_path ? `${BASE_URL}/${item.image_path}` : `${BASE_URL}/images/menu/default-food.jpg`);
                                                    e.target.src = `${BASE_URL}/images/menu/default-food.jpg`;
                                                }}
                                            />
                                        </div>
                                    }
                                >
                                    <Card.Meta
                                        title={
                                            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                                <Text strong>{item.name}</Text>
                                                <Text type="secondary">${parseFloat(item.price).toFixed(2)}</Text>
                                            </div>
                                        }
                                        description={
                                            <Paragraph ellipsis={{ rows: 2 }}>
                                                {item.description}
                                            </Paragraph>
                                        }
                                    />
                                    <div style={{
                                        marginTop: '16px',
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center'
                                    }}>
                                        <Space>
                                            <Button
                                                icon={<MinusOutlined />}
                                                onClick={() => decreaseQuantity(item.id)}
                                                shape="circle"
                                            />
                                            <InputNumber
                                                min={0}
                                                max={99}
                                                value={quantities[item.id] || 0}
                                                style={{ width: '60px' }}
                                                readOnly
                                            />
                                            <Button
                                                icon={<PlusOutlined />}
                                                onClick={() => increaseQuantity(item.id)}
                                                shape="circle"
                                            />
                                        </Space>
                                        <Button
                                            type="primary"
                                            onClick={() => addToCart(item)}
                                            disabled={(quantities[item.id] || 0) === 0}
                                        >
                                            Add
                                        </Button>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </div>
                ))}

                {categories.length === 0 && (
                    <Empty
                        description="No menu items available"
                        style={{ margin: '100px auto' }}
                    />
                )}
            </Content>

            <Affix style={{ position: 'fixed', bottom: '20px', right: '20px' }}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', alignItems: 'flex-end' }}>
                    <Badge count={cart.reduce((total, item) => total + item.quantity, 0)} overflowCount={99}>
                        <Button
                            type="primary"
                            shape="circle"
                            icon={<ShoppingCartOutlined />}
                            size="large"
                            onClick={() => setShowCart(!showCart)}
                            style={{ width: '60px', height: '60px', fontSize: '24px' }}
                        />
                    </Badge>

                    <Button
                        type="primary"
                        shape="circle"
                        icon={<MessageOutlined />}
                        size="large"
                        onClick={() => setShowChat(true)}
                        style={{ width: '60px', height: '60px', fontSize: '24px', backgroundColor: '#52c41a' }}
                    />
                </div>
            </Affix>

            <Drawer
                title="Your Order"
                placement="right"
                onClose={() => setShowCart(false)}
                open={showCart}
                width={350}
                footer={
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            fontWeight: 'bold',
                            fontSize: '16px',
                            padding: '10px 0'
                        }}>
                            <span>Total:</span>
                            <span>${cartTotal.toFixed(2)}</span>
                        </div>
                        <Button
                            type="primary"
                            block
                            onClick={placeOrder}
                            disabled={cart.length === 0}
                        >
                            Place Order
                        </Button>
                    </div>
                }
            >
                {cart.length > 0 ? (
                    <List
                        itemLayout="horizontal"
                        dataSource={cart}
                        renderItem={item => (
                            <List.Item
                                actions={[
                                    <Button
                                        type="text"
                                        danger
                                        icon={<DeleteOutlined />}
                                        onClick={() => removeFromCart(item.id)}
                                    />
                                ]}
                            >
                                <List.Item.Meta
                                    title={<Text strong>{item.name}</Text>}
                                    description={
                                        <div>
                                            <Text type="secondary">
                                                {item.quantity} x ${parseFloat(item.price).toFixed(2)}
                                            </Text>
                                        </div>
                                    }
                                    avatar={
                                        <img
                                            src={item.image_path ? `${BASE_URL}/${item.image_path}` : `${BASE_URL}/images/menu/default-food.jpg`}
                                            alt={item.name}
                                            style={{
                                                width: '50px',
                                                height: '50px',
                                                borderRadius: '4px',
                                                objectFit: 'cover'
                                            }}
                                            onError={(e) => {
                                                e.target.src = `${BASE_URL}/images/menu/default-food.jpg`;
                                            }}
                                        />
                                    }
                                />
                                <div>${(item.quantity * item.price).toFixed(2)}</div>
                            </List.Item>
                        )}
                    />
                ) : (
                    <Empty description="Your cart is empty" />
                )}
            </Drawer>

            <Modal
                title={`Chat - Table #${tableId}`}
                open={showChat}
                onCancel={() => setShowChat(false)}
                footer={null}
                width={400}
                bodyStyle={{ padding: 0 }}
            >
                <div style={{ padding: '10px' }}>
                    <ChatComponent tableId={tableId} userName={userName} />
                </div>
            </Modal>
        </Layout>
    );
};

export default TableMenuPage;
