import './bootstrap';
import 'antd/dist/reset.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';

// Import React components
import TableManagementPage from './pages/TableManagementPage';
import TableMenuPage from './pages/TableMenuPage';
import KitchenDisplayPage from './pages/KitchenDisplayPage';

// Check if we have the react-app element in the page
const reactAppElement = document.getElementById('react-app');

// If the element exists, render the React app
if (reactAppElement) {
    const root = createRoot(reactAppElement);

    root.render(
        <React.StrictMode>
            <BrowserRouter>
                <Routes>
                    <Route path="/react/tables" element={<TableManagementPage />} />
                    <Route path="/react/tables/:tableId/menu" element={<TableMenuPage />} />
                    <Route path="/react/kitchen" element={<KitchenDisplayPage />} />
                </Routes>
            </BrowserRouter>
        </React.StrictMode>
    );
}
