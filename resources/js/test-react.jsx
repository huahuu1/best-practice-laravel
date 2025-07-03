import React from 'react';
import { createRoot } from 'react-dom/client';

const TestApp = () => {
    return (
        <div style={{ textAlign: 'center', marginTop: '100px' }}>
            <h1>React is working!</h1>
            <p>If you see this message, React is properly set up.</p>
        </div>
    );
};

// Mount to element with ID 'react-app'
const element = document.getElementById('react-app');
if (element) {
    const root = createRoot(element);
    root.render(<TestApp />);
}
