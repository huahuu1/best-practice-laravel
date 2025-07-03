import React, { useState, useEffect } from 'react';
import axios from 'axios';
import QRCode from '../components/QRCode';
import { Link } from 'react-router-dom';

// Define base URL for the application using environment variables if available
const BASE_URL = 'http://172.16.0.24:9999';
const API_URL = window.env?.API_URL || 'http://localhost:9999/api';

const TableManagementPage = () => {
    const [tables, setTables] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Fetch tables on component mount
    useEffect(() => {
        const fetchTables = async () => {
            try {
                // Use the full URL with the IP address
                const response = await axios.get(`${API_URL}/tables`);
                setTables(response.data.tables);
                setLoading(false);
            } catch (error) {
                console.error('Error fetching tables:', error);
                setError('Failed to load tables. Please try again later.');
                setLoading(false);

                // For demo purposes, set some sample data
                setTables([
                    { id: 1, name: 'Table 1', capacity: 4, status: 'available' },
                    { id: 2, name: 'Table 2', capacity: 2, status: 'occupied' },
                    { id: 3, name: 'Table 3', capacity: 6, status: 'available' },
                    { id: 4, name: 'Table 4', capacity: 4, status: 'available' },
                    { id: 5, name: 'Table 5', capacity: 8, status: 'reserved' }
                ]);
            }
        };

        fetchTables();
    }, []);

    // Handle QR code scan event
    const handleQRScan = (tableId) => {
        console.log(`QR code for table ${tableId} was scanned`);
        // Additional handling if needed
    };

    // Show loading state
    if (loading) {
        return (
            <div className="container mt-4">
                <div className="text-center">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                    <p className="mt-2">Loading tables...</p>
                </div>
            </div>
        );
    }

    // Show error state if there was an error
    if (error) {
        return (
            <div className="container mt-4">
                <div className="alert alert-danger" role="alert">
                    {error}
                </div>
            </div>
        );
    }

    return (
        <div className="container">
            <div className="top-header text-center mt-4 mb-4">
                <h1>Restaurant Table Management</h1>
                <p className="lead">Scan table QR codes for digital menu ordering</p>
            </div>

            <div className="row">
                {tables.map(table => (
                    <div className="col-md-4" key={table.id}>
                        <div className={`card mb-4 border-left-${table.status === 'available' ? 'success' :
                                         table.status === 'occupied' ? 'danger' : 'warning'}`}>
                            <div className="card-header">
                                <h5 className="mb-0">{table.name}</h5>
                            </div>
                            <div className="card-body">
                                <div className="d-flex justify-content-between mb-3">
                                    <span>Capacity: {table.capacity}</span>
                                    <span className={`badge bg-${
                                        table.status === 'available' ? 'success' :
                                        table.status === 'occupied' ? 'danger' : 'warning'
                                    }`}>
                                        {table.status.charAt(0).toUpperCase() + table.status.slice(1)}
                                    </span>
                                </div>

                                <QRCode table={table} onScan={handleQRScan} />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="row mt-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header bg-primary text-white">
                            <h5 className="mb-0">Kitchen Display</h5>
                        </div>
                        <div className="card-body">
                            <p>View and manage all incoming orders from tables:</p>
                            <a href={`${BASE_URL}/react/kitchen`} className="btn btn-lg btn-primary">
                                Open Kitchen Display
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TableManagementPage;
